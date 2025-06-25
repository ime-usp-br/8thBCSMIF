---
description: "Executa workflow completo Claude Code para implementar um AC específico autonomamente"
---

## Comando: Implementação Autônoma de Acceptance Criteria (AC)

**BASEADO NO SUCESSO:** Este comando replica o workflow autônomo que funcionou perfeitamente na conversa AC1 Issue #50.

**Argumentos esperados:** `<ISSUE_NUMBER> <AC_NUMBER>`

### **Workflow Completo Claude Code (Autônomo)**

#### 0. **Pesquisa de Documentação Relevante**
**Claude Code deve pesquisar a documentação das tecnologias relevantes antes de qualquer modificação.**

<use_mcp_tool>
<server_name>github.com/upstash/context7-mcp</server_name>
<tool_name>get-library-docs</tool_name>
<arguments>
{
  "context7CompatibleLibraryID": "/laravel/docs",
  "tokens": 10000
}
</arguments>
</use_mcp_tool>

<use_mcp_tool>
<server_name>github.com/upstash/context7-mcp</server_name>
<tool_name>get-library-docs</tool_name>
<arguments>
{
  "context7CompatibleLibraryID": "/pytest-dev/pytest",
  "tokens": 10000
}
</arguments>
</use_mcp_tool>

<use_mcp_tool>
<server_name>github.com/upstash/context7-mcp</server_name>
<tool_name>get-library-docs</tool_name>
<arguments>
{
  "context7CompatibleLibraryID": "/alpinejs/alpine",
  "tokens": 10000
}
</arguments>
</use_mcp_tool>

<use_mcp_tool>
<server_name>github.com/upstash/context7-mcp</server_name>
<tool_name>get-library-docs</tool_name>
<arguments>
{
  "context7CompatibleLibraryID": "/tailwindlabs/tailwindcss.com",
  "tokens": 10000
}
</arguments>
</use_mcp_tool>

<use_mcp_tool>
<server_name>github.com/upstash/context7-mcp</server_name>
<tool_name>get-library-docs</tool_name>
<arguments>
{
  "context7CompatibleLibraryID": "/vitejs/vite",
  "tokens": 10000
}
</arguments>
</use_mcp_tool>

<use_mcp_tool>
<server_name>github.com/upstash/context7-mcp</server_name>
<tool_name>get-library-docs</tool_name>
<arguments>
{
  "context7CompatibleLibraryID": "/livewire/livewire",
  "tokens": 10000
}
</arguments>
</use_mcp_tool>

<use_mcp_tool>
<server_name>github.com/upstash/context7-mcp</server_name>
<tool_name>get-library-docs</tool_name>
<arguments>
{
  "context7CompatibleLibraryID": "/fakerphp/faker",
  "tokens": 10000
}
</arguments>
</use_mcp_tool>

<use_mcp_tool>
<server_name>github.com/upstash/context7-mcp</server_name>
<tool_name>get-library-docs</tool_name>
<arguments>
{
  "context7CompatibleLibraryID": "/php-cs-fixer/php-cs-fixer",
  "tokens": 10000
}
</arguments>
</use_mcp_tool>

<use_mcp_tool>
<server_name>github.com/upstash/context7-mcp</server_name>
<tool_name>get-library-docs</tool_name>
<arguments>
{
  "context7CompatibleLibraryID": "/phpstan/phpstan",
  "tokens": 10000
}
</arguments>
</use_mcp_tool>


#### 1. **TodoWrite para Tracking Transparente**
**Claude Code deve usar TodoWrite** para criar workflow transparente das tarefas.

#### 2. **Análise Completa da Issue**
**Claude Code deve executar:**
!gh issue view $ARGUMENT_1

**Claude Code deve identificar:**
- Requisitos exatos do AC especificado
- **ATENÇÃO ESPECIAL:** Palavras-chave "incremental", "adicionar", "modificar"
- Dependências e padrões existentes no código

#### 3. **Implementação com Validação de Requisitos**
**Claude Code deve implementar** as mudanças seguindo:
- Padrões do projeto existentes
- **CRÍTICO:** Para funcionalidades "incrementais", usar métodos aditivos (attach, create) NÃO substitutivos (sync, update)
- **SEMPRE** adicionar testes que comprovem a funcionalidade
- Verificar se implementação atende exatamente o comportamento descrito

#### 4. **Quality Checks Automáticos**
**Claude Code deve executar:**
!vendor/bin/pint && vendor/bin/phpstan analyse && php artisan test

**Todos devem passar antes de prosseguir.**

#### 5. **Validação Automática**
**Claude Code deve executar:**
!git add . && python3 scripts/generate_context.py --stages git && printf "y\ny\ny\n" | python3 scripts/tasks/llm_task_analyze_ac.py -i $ARGUMENT_1 -a $ARGUMENT_2 -sc

**⚠️ CRÍTICO:** Só avance se analyze-ac APROVAR. Se reprovar, corrija e repita.

#### 6. **Commit Formatado**
**Claude Code deve executar:**
!git log -5

**Claude Code deve criar commit** usando formato HEREDOC seguindo padrão projeto:
```bash
git commit -m "$(cat <<'EOF'
tipo(escopo): Descrição principal (#issue)

- Bullet point com mudança específica 1
- Bullet point com mudança específica 2
- Bullet point com mudança específica 3
- Atende ACX: Descrição do critério atendido
EOF
)"
```

#### 7. **Push para Remote**
**Claude Code deve executar:**
!git push

#### 8. **Documentação GitHub Automática**
**Claude Code deve executar:**
```bash
# Capturar hash do commit recém-criado
COMMIT_HASH=$(git rev-parse HEAD)
COMMIT_SHORT=$(git rev-parse --short HEAD)

# Criar comentário usando analyze-ac output (método aprovado)
LATEST_ANALYSIS=$(ls -t llm_outputs/analyze-ac/*.txt | head -1)
cp "$LATEST_ANALYSIS" /tmp/comment.txt
echo "" >> /tmp/comment.txt
echo "---" >> /tmp/comment.txt
echo "**Validação realizada no commit:** [$COMMIT_SHORT](https://github.com/ime-usp-br/8thBCSMIF/commit/$COMMIT_HASH)" >> /tmp/comment.txt

# Postar comentário
gh api repos/:owner/:repo/issues/$ARGUMENT_1/comments -F body=@/tmp/comment.txt
```

---

### **✅ RESULTADOS ESPERADOS:**
1. AC implementado corretamente
2. Todos os quality checks passando
3. Commit formatado seguindo padrão projeto
4. Comentário de validação postado no GitHub
5. **TodoWrite** com todas as tarefas marcadas como completed

### **🔴 LIÇÕES CRÍTICAS APLICADAS:**
- **Requisitos Incrementais:** Verificação especial para usar attach/create não sync/update
- **Commit Hash Correto:** Capturar hash do commit ATUAL, não anterior
- **Método de Comentário Aprovado:** cp + echo para evitar problemas de formatação
- **TodoWrite Transparente:** User pode acompanhar progresso em tempo real

**✅ APROVADO:** Este workflow funcionou 100% na conversa AC1 Issue #50.
