---
description: "Workflow para resolver um Critério de Aceite (AC) específico de uma issue, focando na análise, planejamento e implementação direta."
---

**Nota Importante:** Ao executar comandos manualmente ou adicionar novos comandos a este workflow, se o comando puder gerar uma saída que precise ser exibida ou que possa travar o terminal, utilize `| cat` ao final do comando. Exemplo: `seu-comando-aqui | cat`.

## Guia: Resolução Focada de um Critério de Aceite (AC)

Este workflow guia o assistente de IA no processo de resolver um único Critério de Aceite (AC). O processo enfatiza a coleta de informações, o planejamento prévio com o usuário e a implementação focada, sem utilizar os scripts de automação do projeto.

**Argumentos esperados:** `{issue_number}` `{ac_number}`

### 1. Coleta de Informações Essenciais

O assistente DEVE, primeiramente, obter o contexto da tarefa e do ambiente do projeto.

**A. Obter Detalhes da Issue:**
O assistente DEVE ler o conteúdo completo da issue para entender o escopo do trabalho.

`<execute_command>`
`<command># O assistente de IA DEVE substituir {issue_number} pelo número real da issue.
gh issue view {issue_number} --json title,body,labels | cat
</command>`
`<# Busca o conteúdo da issue para análise contextual. #>`
`<requires_approval>false</requires_approval>`
`</execute_command>`

**B. Verificar Versões de Dependências:**
O assistente DEVE inspecionar o `composer.lock` para verificar as versões das principais bibliotecas que podem estar envolvidas na resolução do AC.

`<read_file>`
`<path>composer.lock</path>`
`</read_file>`

**Instruções para o Assistente de IA:**
1.  Analise o conteúdo do `composer.lock`.
2.  Identifique as bibliotecas chave para a tarefa (ex: `laravel/framework`, `livewire/livewire`, `spatie/laravel-permission`).
3.  Compare a versão da biblioteca com a sua data de corte de conhecimento.

**C. Consultar Documentação Externa (Condicional):**
Se uma versão de biblioteca for posterior à sua data de corte de conhecimento, o assistente DEVE usar a ferramenta `context7` para obter documentação atualizada.

**Exemplo de uso da ferramenta `context7` (MCP):**
```xml
<use_mcp_tool>
  <server_name>github.com/upstash/context7-mcp</server_name>
  <tool_name>get-library-docs</tool_name>
  <arguments>
  {
    "context7CompatibleLibraryID": "/livewire/livewire",
    "topic": "Events"
  }
  </arguments>
</use_mcp_tool>
```
**Nota:** O assistente DEVE primeiro usar `resolve-library-id` se não tiver certeza do ID exato.

**D. Leitura de Documentação Interna:**
O assistente DEVE ler os guias de desenvolvimento e padrões de código para garantir que a implementação esteja alinhada com as práticas do projeto.

`<read_file>`
`<path>docs/guia_de_desenvolvimento.md</path>`
`</read_file>`

`<read_file>`
`<path>docs/padroes_codigo_boas_praticas.md</path>`
`</read_file>`

### 2. Fase de Planejamento (Interação com o Usuário)

Com as informações coletadas, o assistente **NÃO DEVE** começar a codificar imediatamente. Em vez disso, **DEVE** entrar em um modo de planejamento.

**Instruções para o Assistente de IA (Ação Obrigatória):**
1.  Sintetize todas as informações: os requisitos da issue, o AC específico, e o conhecimento adquirido sobre as bibliotecas.
2.  Formule um plano de implementação detalhado. O plano DEVE descrever:
    *   Quais arquivos você pretende criar ou modificar.
    *   As principais alterações lógicas que você fará.
    *   Como você abordará os requisitos específicos do AC.
3.  Apresente este plano ao usuário para aprovação.
4.  **Aguarde a aprovação explícita do usuário antes de prosseguir.**

### 3. Implementação e Codificação

Após a aprovação do plano pelo usuário, o assistente DEVE executar a implementação.

**Instruções para o Assistente de IA:**
1.  **Modificar ou Criar Arquivos:** Escreva ou edite o código (PHP, Blade, etc.) conforme o plano aprovado. Utilize as ferramentas `<write_to_file>` ou `<replace_in_file>` para realizar as alterações.
2.  **Foco Atômico:** As alterações DEVEM se limitar estritamente ao escopo do AC e do plano aprovado.

### 4. Validação (Testes Pertinentes)

Se for pertinente para a alteração realizada, o assistente DEVE executar os testes unitários ou de feature relevantes para garantir que a nova funcionalidade está correta e que não houve regressões.

`<execute_command>`
`<command># O assistente DEVE especificar um arquivo, filtro ou grupo para rodar apenas os testes relevantes.
# Exemplo: ./vendor/bin/phpunit --filter=NomeDoTesteRelevanteTest
./vendor/bin/phpunit | cat
</command>`
`<# Executa testes específicos para validar a implementação do AC. #>`
`<requires_approval>false</requires_approval>`
`</execute_command>`

Ao final desta etapa, a resolução do Critério de Aceite está concluída. O workflow não cobre os passos de commit ou pull request.
