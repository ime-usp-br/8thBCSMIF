
---
description: "Workflow para implementar um AC específico autonomamente usando as ferramentas do Cline."
---

## Workflow Completo Cline (Autônomo)

**Argumentos esperados:** `<ISSUE_NUMBER> <AC_NUMBER>`

### 1. Análise Completa da Issue
O Cline deve analisar a issue para entender os requisitos exatos do AC especificado, prestando atenção especial a palavras-chave como "incremental", "adicionar", "modificar", e identificar dependências e padrões existentes no código.

<execute_command>
<command>gh issue view $ISSUE_NUMBER | cat</command>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Implementação com Validação de Requisitos
O Cline deve implementar as mudanças seguindo os padrões do projeto existentes. Para funcionalidades "incrementais", deve usar métodos aditivos (attach, create) e não substitutivos (sync, update). Sempre deve adicionar testes que comprovem a funcionalidade e verificar se a implementação atende exatamente o comportamento descrito.

### 3. Quality Checks Automáticos
O Cline deve executar os quality checks automáticos. Todos devem passar antes de prosseguir.

<execute_command>
<command>vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test</command>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Validação Automática
O Cline deve executar a validação automática. É CRÍTICO que só avance se o `analyze-ac` APROVAR. Se reprovar, o Cline deve corrigir e repetir.

<execute_command>
<command>git add . && python3 scripts/generate_context.py --stages git && printf "y\ny\ny\n" | python3 scripts/tasks/llm_task_analyze_ac.py -i $ISSUE_NUMBER -a $AC_NUMBER -sc</command>
<requires_approval>false</requires_approval>
</execute_command>
