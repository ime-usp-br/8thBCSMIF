
---
description: "Workflow para implementar um AC específico autonomamente usando as ferramentas do Cline."
---

## Workflow Completo Cline (Autônomo)

**Argumentos esperados:** `<ISSUE_NUMBER> <AC_NUMBER>`

### 1. Análise Completa da Issue
O Cline deve analisar a issue para entender os requisitos exatos do AC especificado, prestando atenção especial a palavras-chave como "incremental", "adicionar", "modificar", e identificar dependências e padrões existentes no código.

<execute_command>
<command>gh issue view $ISSUE_NUMBER</command>
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

### 5. Commit Formatado
O Cline deve visualizar os últimos commits e criar um novo commit usando um formato HEREDOC, seguindo o padrão do projeto. O Cline deve gerar o conteúdo da mensagem de commit dinamicamente.

<execute_command>
<command>git log -5</command>
<requires_approval>false</requires_approval>
</execute_command>

<!-- Exemplo de como o Cline geraria o commit (o Cline construiria a string da mensagem de commit dinamicamente):
<execute_command>
<command>git commit -m "tipo(escopo): Descrição principal (#$ISSUE_NUMBER)

- Bullet point com mudança específica 1
- Bullet point com mudança específica 2
- Bullet point com mudança específica 3
- Atende AC$AC_NUMBER: Descrição do critério atendido
"</command>
<requires_approval>false</requires_approval>
</execute_command>
-->

### 6. Push para Remote
O Cline deve fazer o push das mudanças para o repositório remoto.

<execute_command>
<command>git push</command>
<requires_approval>false</requires_approval>
</execute_command>

### 7. Documentação GitHub Automática
O Cline deve capturar o hash do commit recém-criado, usar a saída do `analyze-ac` para criar um comentário e postá-lo na issue do GitHub.

<execute_command>
<command>
COMMIT_HASH=$(git rev-parse HEAD)
COMMIT_SHORT=$(git rev-parse --short HEAD)
LATEST_ANALYSIS=$(ls -t llm_outputs/analyze-ac/*.txt | head -1)
cp "$LATEST_ANALYSIS" /tmp/comment.txt
echo "" >> /tmp/comment.txt
echo "---" >> /tmp/comment.txt
echo "**Validação realizada no commit:** [$COMMIT_SHORT](https://github.com/ime-usp-br/8thBCSMIF/commit/$COMMIT_HASH)" >> /tmp/comment.txt
gh api repos/ime-usp-br/8thBCSMIF/issues/$ISSUE_NUMBER/comments -F body=@/tmp/comment.txt
</command>
<requires_approval>true</requires_approval>
</execute_command>
