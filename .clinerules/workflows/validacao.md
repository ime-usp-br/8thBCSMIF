---
description: "Guia para executar validação completa de Acceptance Criteria (AC) seguindo o workflow do Cline."
---

## Guia: Validação Completa de Acceptance Criteria (AC)

Este guia descreve o processo de validação de um Acceptance Criteria (AC) usando as ferramentas do Cline.

**Argumentos esperados:** `<ISSUE_NUMBER> <AC_NUMBER>`

### 1. Análise de Contexto da Issue
O Cline deve analisar a issue para entender o contexto e os requisitos do AC.

<execute_command>
<command>gh issue view $ISSUE_NUMBER</command>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Verificação de Requisitos "Incrementais"
O Cline deve verificar se a issue contém palavras-chave como "incremental", "adicionar", "modificar". Se sim, é CRÍTICO que a implementação use métodos aditivos (attach, create) e não substitutivos (sync, update), validando que a implementação não apaga/substitui dados existentes.

### 3. Quality Checks Obrigatórios
O Cline deve executar todos os quality checks obrigatórios.

<execute_command>
<command>vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test</command>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Atualização de Contexto
O Cline deve adicionar as mudanças ao stage e gerar o contexto atualizado.

<execute_command>
<command>git add . && python3 scripts/generate_context.py --stages git</command>
<requires_approval>false</requires_approval>
</execute_command>

### 5. Validação Automática com analyze-ac
O Cline deve executar a ferramenta `analyze-ac` para validar o AC.

<execute_command>
<command>printf "y\ny\ny\n" | python3 scripts/tasks/llm_task_analyze_ac.py -i $ISSUE_NUMBER -a $AC_NUMBER -sc</command>
<requires_approval>false</requires_approval>
</execute_command>

### 6. Verificação de Resultados
O Cline deve verificar os resultados da análise do `analyze-ac`. É crucial que só prossiga se o `analyze-ac` mostrar "foi **Atendido**". Se reprovar, o Cline deve corrigir e repetir a validação.

<execute_command>
<command>LATEST_ANALYSIS=$(ls -t llm_outputs/analyze-ac/*.txt | head -1) && echo "=== RESULTADO ANALYZE-AC ===" && tail -5 "$LATEST_ANALYSIS"</command>
<requires_approval>false</requires_approval>
</execute_command>

**✅ PRÓXIMOS PASSOS AUTOMÁTICOS** (apenas se APROVADO):
1. O Cline deve criar um commit com o padrão do projeto (usando o guia `commit.md`).
2. O Cline deve fazer o push para o repositório remoto (usando o guia `git-push.md`).
3. O Cline deve documentar no GitHub com o hash correto (usando o guia `postar-comentario.md`).
