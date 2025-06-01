# -*- coding: utf-8 -*-
"""
Script para a tarefa 'resolve-ac' de interação com LLM.
Gera código para resolver um Critério de Aceite (AC) específico de uma Issue GitHub.
"""

# --- START OF sys.path MODIFICATION ---
import sys
import os  # Para os.access
from pathlib import Path

# Adiciona o diretório raiz do projeto (PROJECT_ROOT) ao sys.path
# Isso permite que 'from scripts.llm_core import ...' funcione quando
# este script é executado diretamente.
# O caminho é: llm_task_resolve_ac.py -> tasks/ -> scripts/ -> PROJECT_ROOT
_project_root_dir_for_task = Path(__file__).resolve().parent.parent.parent
if str(_project_root_dir_for_task) not in sys.path:
    sys.path.insert(0, str(_project_root_dir_for_task))
# --- END OF sys.path MODIFICATION ---

import argparse
import time  # Adicionado, pois é usado em llm_interact_copy
import traceback
import json  # Adicionado, pois é usado em llm_interact_copy

# Importações do core usando o caminho absoluto a partir da raiz do projeto (scripts. ...)
from scripts.llm_core import config as core_config
from scripts.llm_core import args as core_args_module
from scripts.llm_core import api_client
from scripts.llm_core import context as core_context
from scripts.llm_core import prompts as core_prompts_module
from scripts.llm_core import io_utils
from scripts.llm_core import utils as core_utils  # Importando utils
from scripts.llm_core.exceptions import MissingEssentialFileAbort  # AC4.1

from google.genai import (
    types,
)  # Necessário para types.Part, types.GenerateContentConfig

# Constantes específicas da tarefa
TASK_NAME = "resolve-ac"
PROMPT_TEMPLATE_NAME = "prompt-resolve-ac.txt"  # Usado no llm_interact_copy
META_PROMPT_TEMPLATE_NAME = "meta-prompt-resolve-ac.txt"  # Usado no llm_interact_copy


def add_task_specific_args(parser: argparse.ArgumentParser):
    """Adiciona argumentos específicos da tarefa 'resolve-ac' ao parser."""
    parser.add_argument(
        "-i", "--issue", required=True, help="Número da Issue GitHub (obrigatório)."
    )
    parser.add_argument(
        "-a",
        "--ac",
        required=True,
        help="Número do Critério de Aceite (AC) a ser resolvido (obrigatório).",
    )
    # O argumento -o/--observation já é adicionado pelo get_common_arg_parser


def main_resolve_ac():
    """Função principal para a tarefa resolve-ac."""
    # Inicializa parser com argumentos comuns
    parser = core_args_module.get_common_arg_parser(  # Usando o get_common_arg_parser do core
        description=f"Executa a tarefa '{TASK_NAME}' para gerar código para um AC."
    )
    add_task_specific_args(parser)

    try:
        args = parser.parse_args()
    except SystemExit as e:
        # argparse já imprime a ajuda ou erro, então apenas saímos
        sys.exit(e.code)

    # Configura logging verboso
    verbose = args.verbose
    if verbose:
        print("Modo verbose ativado.")

    # Tenta inicializar os recursos da API (chaves, cliente, executor)
    if not api_client.startup_api_resources(verbose):
        print(
            "Erro fatal: Falha ao inicializar recursos da API. Saindo.", file=sys.stderr
        )
        sys.exit(1)

    try:
        # Lógica principal adaptada de llm_interact_copy.py
        if args.generate_context:
            print(
                f"\nExecutando script de geração de contexto: {core_config.CONTEXT_GENERATION_SCRIPT.relative_to(core_config.PROJECT_ROOT)}..."
            )
            if not core_config.CONTEXT_GENERATION_SCRIPT.is_file() or not os.access(
                core_config.CONTEXT_GENERATION_SCRIPT, os.X_OK
            ):
                print(
                    f"Erro: Script de contexto '{core_config.CONTEXT_GENERATION_SCRIPT.name}' não encontrado ou não executável.",
                    file=sys.stderr,
                )
                sys.exit(1)

            # Usando core_utils.run_command
            exit_code_ctx, _, stderr_ctx = core_utils.run_command(
                [sys.executable, str(core_config.CONTEXT_GENERATION_SCRIPT)],
                check=False,
                timeout=core_config.DEFAULT_CONTEXT_GENERATION_TIMEOUT,
            )
            if exit_code_ctx != 0:
                print(
                    f"Erro: Geração de contexto falhou (código: {exit_code_ctx}). Stderr:\n{stderr_ctx}",
                    file=sys.stderr,
                )
                sys.exit(1)
            print("Script de geração de contexto concluído.")

        task_variables: Dict[str, str] = {
            "NUMERO_DA_ISSUE": args.issue,
            "NUMERO_DO_AC": args.ac,
            "OBSERVACAO_ADICIONAL": args.observation,
        }

        if args.two_stage:
            template_path_to_load = (
                core_config.META_PROMPT_DIR / META_PROMPT_TEMPLATE_NAME
            )
            print(f"\nFluxo de Duas Etapas Selecionado")
            print(
                f"Usando Meta-Prompt: {template_path_to_load.relative_to(core_config.PROJECT_ROOT)}"
            )
        else:
            template_path_to_load = core_config.TEMPLATE_DIR / PROMPT_TEMPLATE_NAME
            print(f"\nFluxo Direto Selecionado")
            print(
                f"Usando Prompt: {template_path_to_load.relative_to(core_config.PROJECT_ROOT)}"
            )

        initial_prompt_content_original = core_prompts_module.load_and_fill_template(
            template_path_to_load, task_variables
        )
        if not initial_prompt_content_original:
            print(f"Erro ao carregar o prompt inicial. Saindo.", file=sys.stderr)
            sys.exit(1)

        initial_prompt_content_current = initial_prompt_content_original
        if args.web_search:
            initial_prompt_content_current += core_config.WEB_SEARCH_ENCOURAGEMENT_PT

        if args.only_meta and args.two_stage:
            print("\n--- Meta-Prompt Preenchido (--only-meta) ---")
            print(initial_prompt_content_current.strip())
            print("--- Fim ---")
            sys.exit(0)
        elif args.only_meta:
            print(
                "Aviso: --only-meta é aplicável apenas com --two-stage.",
                file=sys.stderr,
            )

        # AC1.1: Não sair prematuramente se --select-context também estiver ativo
        # Permite que a seleção de contexto seja executada antes de exibir o prompt
        if args.only_prompt and not args.two_stage and not args.select_context:
            print(f"\n--- Prompt Final (--only-prompt) ---")
            print(initial_prompt_content_current.strip())
            print("--- Fim ---")
            sys.exit(0)

        # Preparar contexto
        context_parts: List[types.Part] = []
        final_selected_files_for_context: Optional[List[str]] = None
        manifest_data_for_context_selection: Optional[Dict[str, Any]] = None
        load_default_context_after_selection_failure = False

        latest_context_dir_path = core_context.find_latest_context_dir(
            core_config.CONTEXT_DIR_BASE
        )
        latest_dir_name_for_essentials = (
            latest_context_dir_path.name if latest_context_dir_path else None
        )

        max_tokens_for_main_call = api_client.calculate_max_input_tokens(
            core_config.GEMINI_MODEL_RESOLVE, verbose=verbose
        )

        if args.select_context:
            print("\nSeleção de Contexto Preliminar Habilitada...")
            latest_manifest_path = core_context.find_latest_manifest_json(
                core_config.MANIFEST_DATA_DIR
            )
            if not latest_manifest_path:
                print(
                    "Erro: Não foi possível encontrar o manifesto para seleção de contexto. Tente gerar o manifesto primeiro.",
                    file=sys.stderr,
                )
                sys.exit(1)
            manifest_data_for_context_selection = core_context.load_manifest(
                latest_manifest_path
            )
            if (
                not manifest_data_for_context_selection
                or "files" not in manifest_data_for_context_selection
            ):
                print(
                    "Erro: Manifesto inválido ou vazio para seleção de contexto.",
                    file=sys.stderr,
                )
                sys.exit(1)
            if verbose:  # AC5.1
                print(
                    f"  AC5.1: Manifesto carregado para seleção: {latest_manifest_path.relative_to(core_config.PROJECT_ROOT)}"
                )

            context_selector_prompt_path = (
                core_prompts_module.find_context_selector_prompt(
                    TASK_NAME, args.two_stage
                )
            )
            if not context_selector_prompt_path:
                sys.exit(1)

            selector_prompt_content = core_prompts_module.load_and_fill_template(
                context_selector_prompt_path, task_variables
            )
            if not selector_prompt_content:
                print("Erro ao carregar prompt seletor de contexto.", file=sys.stderr)
                sys.exit(1)
            if verbose:  # AC5.1
                print(
                    f"  AC5.1: Usando Prompt Seletor: {context_selector_prompt_path.relative_to(core_config.PROJECT_ROOT)}"
                )

            # AC4.1: A exceção MissingEssentialFileAbort será capturada no bloco try...except geral
            preliminary_api_input_content = (
                core_context.prepare_payload_for_selector_llm(
                    TASK_NAME,
                    args,
                    latest_dir_name_for_essentials,
                    manifest_data_for_context_selection,
                    selector_prompt_content,
                    core_config.MAX_ESSENTIAL_TOKENS_FOR_SELECTOR_CALL,
                    verbose,  # Passa verbose para logging interno (AC5.1a, b, c)
                )
            )

            response_prelim_str: Optional[str] = None
            suggested_files_from_api: List[str] = []
            try:
                print(
                    f"  Chamando API preliminar ({core_config.GEMINI_MODEL_FLASH}) para seleção de contexto..."
                )
                response_prelim_str = api_client.execute_gemini_call(
                    core_config.GEMINI_MODEL_FLASH,
                    [types.Part.from_text(text=preliminary_api_input_content)],
                    config=types.GenerateContentConfig(
                        tools=(
                            [
                                types.Tool(
                                    google_search_retrieval=types.GoogleSearchRetrieval()
                                )
                            ]
                            if args.web_search
                            else []
                        )
                    ),
                    verbose=verbose,
                )

                cleaned_response_str = response_prelim_str.strip()
                if cleaned_response_str.startswith("```json"):
                    cleaned_response_str = cleaned_response_str[7:].strip()
                if cleaned_response_str.endswith("```"):
                    cleaned_response_str = cleaned_response_str[:-3].strip()
                parsed_response = json.loads(cleaned_response_str)
                if (
                    isinstance(parsed_response, dict)
                    and "relevant_files" in parsed_response
                    and isinstance(parsed_response["relevant_files"], list)
                ):
                    suggested_files_from_api = [
                        str(item)
                        for item in parsed_response["relevant_files"]
                        if isinstance(item, str)
                    ]
                else:
                    raise ValueError("Formato de 'relevant_files' inválido.")
                print(
                    f"    API preliminar retornou {len(suggested_files_from_api)} arquivos sugeridos."
                )

            except Exception as e:
                print(
                    f"\nErro fatal durante seleção de contexto preliminar: {type(e).__name__} - {e}",
                    file=sys.stderr,
                )
                if verbose:
                    traceback.print_exc()
                sys.exit(1)

            if not suggested_files_from_api:
                if not core_context.prompt_user_on_empty_selection():
                    sys.exit(1)
                load_default_context_after_selection_failure = True
            else:
                # AC1.2: Consultar usuário para confirmar/modificar lista (se -y não for usado)
                if args.yes:
                    # Se --yes está ativo, usar diretamente a lista sugerida pela LLM
                    final_selected_files_for_context = suggested_files_from_api
                    if verbose:
                        print(
                            f"  AC1.2: Flag --yes ativa, usando diretamente {len(suggested_files_from_api)} arquivos sugeridos pela LLM."
                        )
                else:
                    # Caso contrário, permitir confirmação/modificação pelo usuário
                    final_selected_files_for_context = (
                        core_context.confirm_and_modify_selection(
                            suggested_files_from_api,
                            manifest_data_for_context_selection,
                            max_tokens_for_main_call,  # AC3.2
                            verbose=verbose,  # AC5.1d, AC5.1e
                        )
                    )
                if final_selected_files_for_context is None:
                    load_default_context_after_selection_failure = True

        # AC5: Se -op e -sc estão juntas, preparar diretório temporário com arquivos selecionados
        if (
            args.only_prompt
            and args.select_context
            and final_selected_files_for_context is not None
        ):
            print(
                f"\nPreparando diretório temporário para uso manual (--only-prompt + --select-context)..."
            )

            # Limpar diretório temporário primeiro (AC4)
            if not io_utils.clean_temp_directory(
                core_config.TEMP_CONTEXT_COPY_DIR, verbose=verbose
            ):
                print(
                    "Erro: Falha ao limpar diretório temporário. Continuando sem cópia.",
                    file=sys.stderr,
                )
            else:
                # Obter arquivos essenciais para a tarefa (AC2)
                try:
                    essential_files_abs = core_context.get_essential_files_for_task(
                        TASK_NAME, args, latest_dir_name_for_essentials, verbose=verbose
                    )
                except Exception as e:
                    print(f"Erro ao obter arquivos essenciais: {e}", file=sys.stderr)
                    essential_files_abs = []

                # Converter arquivos selecionados para caminhos absolutos
                selected_files_abs = []
                for file_path_str in final_selected_files_for_context:
                    abs_path = (core_config.PROJECT_ROOT / file_path_str).resolve(
                        strict=False
                    )
                    selected_files_abs.append(abs_path)

                # Combinar listas e remover duplicatas (AC2)
                all_files_set = set(essential_files_abs + selected_files_abs)
                all_files_to_copy = list(all_files_set)

                if verbose:
                    print(
                        f"  Total de arquivos a copiar: {len(all_files_to_copy)} ({len(essential_files_abs)} essenciais + {len(selected_files_abs)} selecionados)"
                    )

                # Copiar arquivos para diretório temporário (AC5)
                success, copied_files = io_utils.copy_files_to_temp_directory(
                    all_files_to_copy,
                    core_config.TEMP_CONTEXT_COPY_DIR,
                    core_config.PROJECT_ROOT,
                    verbose=verbose,
                )

                if success:
                    # AC6: Informar usuário sobre arquivos copiados
                    print(f"\n✅ Arquivos copiados para diretório temporário:")
                    print(
                        f"   📁 {core_config.TEMP_CONTEXT_COPY_DIR.relative_to(core_config.PROJECT_ROOT)}"
                    )
                    print(
                        f"   📋 {len(copied_files)} arquivos copiados (extensão .txt para Google AI Studio)"
                    )
                    if verbose:
                        for filename in sorted(copied_files):
                            print(f"      - {filename}")
                else:
                    print(
                        "⚠️  Falha na cópia de alguns arquivos. Verifique as mensagens de erro acima.",
                        file=sys.stderr,
                    )

        # Lógica de carregamento de contexto, agora usando max_tokens_for_main_call
        if (
            final_selected_files_for_context is not None
            and not load_default_context_after_selection_failure
        ):
            context_parts = core_context.prepare_context_parts(
                primary_context_dir=None,  # Não usado se include_list é fornecido
                common_context_dir=None,  # Não usado se include_list é fornecido
                exclude_list=args.exclude_context,
                manifest_data=manifest_data_for_context_selection,  # Passar o manifesto completo
                include_list=final_selected_files_for_context,
                max_input_tokens_for_call=max_tokens_for_main_call,
                task_name_for_essentials=TASK_NAME,
                cli_args_for_essentials=args,
                latest_dir_name_for_essentials=latest_dir_name_for_essentials,
                verbose=verbose,
            )
        else:  # Carregamento padrão
            if not latest_context_dir_path:
                print(
                    "Erro fatal: Nenhum diretório de contexto encontrado para carregamento padrão. Execute generate_context.py.",
                    file=sys.stderr,
                )
                sys.exit(1)
            context_parts = core_context.prepare_context_parts(
                primary_context_dir=latest_context_dir_path,
                common_context_dir=core_config.COMMON_CONTEXT_DIR,
                exclude_list=args.exclude_context,
                manifest_data=manifest_data_for_context_selection,  # Pode ser None se -sc não foi usado
                max_input_tokens_for_call=max_tokens_for_main_call,
                task_name_for_essentials=TASK_NAME,
                cli_args_for_essentials=args,
                latest_dir_name_for_essentials=latest_dir_name_for_essentials,
                verbose=verbose,
            )

        if not context_parts and verbose:
            print(
                "Aviso: Nenhuma parte de contexto carregada. A LLM pode não ter informações suficientes.",
                file=sys.stderr,
            )

        final_prompt_to_send: Optional[str] = None
        if args.two_stage:
            print(
                "\nExecutando Fluxo de Duas Etapas (Etapa 1: Meta -> Prompt Final)..."
            )
            prompt_final_content: Optional[str] = None
            meta_prompt_current = initial_prompt_content_current
            while True:
                print(
                    f"\nEtapa 1: Enviando Meta-Prompt + Contexto ({len(context_parts)} partes)..."
                )
                contents_step1 = [
                    types.Part.from_text(text=meta_prompt_current)
                ] + context_parts
                try:
                    prompt_final_content = api_client.execute_gemini_call(
                        core_config.GEMINI_MODEL_RESOLVE,
                        contents_step1,
                        config=types.GenerateContentConfig(
                            tools=(
                                [
                                    types.Tool(
                                        google_search_retrieval=types.GoogleSearchRetrieval()
                                    )
                                ]
                                if args.web_search
                                else []
                            )
                        ),
                        verbose=verbose,
                    )
                    print("\n--- Prompt Final Gerado (Etapa 1) ---")
                    print(prompt_final_content.strip())
                    print("---")
                    if args.yes:
                        user_choice_step1, observation_step1 = "y", None
                    else:
                        user_choice_step1, observation_step1 = io_utils.confirm_step(
                            "Usar este prompt gerado para a Etapa 2?"
                        )

                    if user_choice_step1 == "y":
                        final_prompt_to_send = prompt_final_content
                        break
                    elif user_choice_step1 == "q":
                        sys.exit(0)
                    elif user_choice_step1 == "n" and observation_step1:
                        meta_prompt_current = (
                            core_prompts_module.modify_prompt_with_observation(
                                meta_prompt_current, observation_step1
                            )
                        )
                    else:
                        sys.exit(1)
                except Exception as e:
                    print(f"  Erro durante chamada API Etapa 1: {e}", file=sys.stderr)
                    if "Prompt bloqueado" in str(e):
                        sys.exit(1)
                    retry_choice, _ = io_utils.confirm_step(
                        "Chamada API Etapa 1 falhou. Tentar novamente?"
                    )
                    if retry_choice != "y":
                        sys.exit(1)

            if not final_prompt_to_send:
                sys.exit(1)
            if (
                args.web_search
                and core_config.WEB_SEARCH_ENCOURAGEMENT_PT not in final_prompt_to_send
            ):
                final_prompt_to_send += core_config.WEB_SEARCH_ENCOURAGEMENT_PT
        else:
            final_prompt_to_send = initial_prompt_content_current

        if args.only_prompt:
            print(f"\n--- Prompt Final Para Envio (--only-prompt) ---")

            # AC7: Se -op e -sc foram usadas juntas, adicionar referência aos arquivos temporários
            if args.select_context and core_config.TEMP_CONTEXT_COPY_DIR.exists():
                # Listar arquivos copiados para referência no prompt
                temp_files = sorted(core_config.TEMP_CONTEXT_COPY_DIR.glob("*.txt"))
                if temp_files:
                    # Adicionar contexto sobre os arquivos anexados ao prompt
                    enhanced_prompt = final_prompt_to_send.strip()
                    enhanced_prompt += "\n\n## Arquivos de Contexto Anexados\n"
                    enhanced_prompt += f"Os seguintes {len(temp_files)} arquivos foram selecionados e estão anexados a esta conversa para fornecer contexto relevante:\n\n"

                    for temp_file in temp_files:
                        # Remover extensão .txt para mostrar o nome original
                        original_name = temp_file.stem
                        enhanced_prompt += f"- **{original_name}**: "

                        # Tentar identificar o tipo/propósito do arquivo baseado no nome
                        if "Model" in original_name or "model" in original_name:
                            enhanced_prompt += "Modelo de dados/entidade"
                        elif (
                            "Controller" in original_name
                            or "controller" in original_name
                        ):
                            enhanced_prompt += "Controlador da aplicação"
                        elif "Service" in original_name or "service" in original_name:
                            enhanced_prompt += "Serviço de negócio"
                        elif (
                            "migration" in original_name or "Migration" in original_name
                        ):
                            enhanced_prompt += "Migração de banco de dados"
                        elif "test" in original_name.lower() or "Test" in original_name:
                            enhanced_prompt += "Arquivo de teste"
                        elif (
                            "config" in original_name.lower()
                            or "Config" in original_name
                        ):
                            enhanced_prompt += "Arquivo de configuração"
                        elif original_name.endswith("_details"):
                            enhanced_prompt += "Detalhes da issue GitHub"
                        elif (
                            "guia" in original_name.lower()
                            or "padrao" in original_name.lower()
                        ):
                            enhanced_prompt += "Documentação/guia do projeto"
                        else:
                            enhanced_prompt += "Arquivo do projeto"
                        enhanced_prompt += "\n"

                    enhanced_prompt += "\nPor favor, analise estes arquivos anexados juntamente com o prompt para fornecer a melhor solução possível."
                    print(enhanced_prompt)
                else:
                    print(final_prompt_to_send.strip())
            else:
                print(final_prompt_to_send.strip())

            print("--- Fim ---")
            sys.exit(0)

        final_response_content: Optional[str] = None
        final_prompt_current = final_prompt_to_send
        while True:
            step_name = "Etapa 2: Enviando" if args.two_stage else "Enviando"
            print(
                f"\n{step_name} Prompt Final + Contexto ({len(context_parts)} partes)..."
            )
            contents_final = [
                types.Part.from_text(text=final_prompt_current)
            ] + context_parts
            try:
                final_response_content = api_client.execute_gemini_call(
                    core_config.GEMINI_MODEL_RESOLVE,
                    contents_final,
                    config=types.GenerateContentConfig(
                        tools=(
                            [
                                types.Tool(
                                    google_search_retrieval=types.GoogleSearchRetrieval()
                                )
                            ]
                            if args.web_search
                            else []
                        )
                    ),
                    verbose=verbose,
                )
                print("\n--- Resposta Final ---")
                print(final_response_content.strip() if final_response_content else "")
                print("---")
                if args.yes:
                    user_choice_final, observation_final = "y", None
                else:
                    user_choice_final, observation_final = io_utils.confirm_step(
                        "Prosseguir com esta resposta final?"
                    )

                if user_choice_final == "y":
                    break
                elif user_choice_final == "q":
                    sys.exit(0)
                elif user_choice_final == "n" and observation_final:
                    final_prompt_current = (
                        core_prompts_module.modify_prompt_with_observation(
                            final_prompt_current, observation_final
                        )
                    )
                else:
                    sys.exit(1)
            except Exception as e:
                print(f"  Erro durante chamada API final: {e}", file=sys.stderr)
                if "Prompt bloqueado" in str(e):
                    sys.exit(1)
                retry_choice_final, _ = io_utils.confirm_step(
                    "Chamada API final falhou. Tentar novamente?"
                )
                if retry_choice_final != "y":
                    sys.exit(1)

        if final_response_content is None:
            print("Erro: Nenhuma resposta final obtida.", file=sys.stderr)
            sys.exit(1)

        if final_response_content.strip():
            save_confirm_choice, _ = io_utils.confirm_step(
                "Confirmar salvamento desta resposta?"
            )
            if save_confirm_choice == "y":
                print("\nSalvando Resposta Final...")
                io_utils.save_llm_response(TASK_NAME, final_response_content.strip())
            else:
                print("Salvamento cancelado.")
                sys.exit(0)
        else:
            print(
                "\nResposta final da LLM está vazia. Isso pode ser esperado se nenhuma alteração de código foi necessária."
            )
            print("Nenhum arquivo será salvo.")
    except MissingEssentialFileAbort as e:  # AC4.1d
        print(f"\nErro: {e}", file=sys.stderr)
        print("Fluxo de seleção de contexto interrompido.")
        sys.exit(1)
    except Exception as e:
        print(f"Erro inesperado na tarefa '{TASK_NAME}': {e}", file=sys.stderr)
        traceback.print_exc()
        sys.exit(1)
    finally:
        api_client.shutdown_api_resources(verbose)


if __name__ == "__main__":
    main_resolve_ac()
