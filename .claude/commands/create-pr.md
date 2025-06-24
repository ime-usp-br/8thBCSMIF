---
description: "Cria um Pull Request no GitHub seguindo as melhores práticas do projeto e identificando commits que serão mergeados."
---

## Tarefa: Criação de Pull Request

**Claude Code deve executar** este workflow completo para criação de PR seguindo melhores práticas e identificando exatamente quais commits serão mergeados no branch main.

### 1. **Análise de Branch e Commits**

**Claude Code deve executar:**
```bash
# Detectar issue da branch atual
CURRENT_BRANCH=$(git branch --show-current)
if [[ "$CURRENT_BRANCH" =~ feat/([0-9]+) ]]; then
    ISSUE_NUMBER="${BASH_REMATCH[1]}"
    echo "✅ Issue detectada: #$ISSUE_NUMBER"
else
    echo "⚠️ Branch não segue padrão feat/X - forneça ISSUE_NUMBER manualmente"
    exit 1
fi

# Verificar branch principal (main vs master)
MAIN_BRANCH=$(git remote show origin | grep 'HEAD branch' | cut -d' ' -f5)
echo "📋 Branch principal: $MAIN_BRANCH"

# 🎯 IDENTIFICAR COMMITS QUE SERÃO MERGEADOS
echo "🔍 Commits que serão incluídos no PR:"
git log --oneline $MAIN_BRANCH..HEAD

echo "📊 Total de commits: $(git rev-list --count $MAIN_BRANCH..HEAD)"
```

### 2. **Verificações de Qualidade Obrigatórias**

**Claude Code deve executar:**
```bash
# Quality checks finais antes do PR
echo "🔧 Executando quality checks..."
vendor/bin/pint                     # PSR-12 formatting
vendor/bin/phpstan analyse          # Static analysis  
php artisan test                    # PHPUnit tests
pytest -v --live                    # Python tests (se aplicável)

# Verificar se branch está atualizada
git fetch origin
BEHIND_COUNT=$(git rev-list --count HEAD..origin/$MAIN_BRANCH)
if [ "$BEHIND_COUNT" -gt 0 ]; then
    echo "⚠️ Branch está $BEHIND_COUNT commits atrás de $MAIN_BRANCH"
    echo "❌ Faça rebase antes de criar PR: git rebase origin/$MAIN_BRANCH"
    exit 1
fi
echo "✅ Branch está atualizada"
```

### 3. **Análise da Issue para Título e Descrição**

**Claude Code deve executar:**
```bash
# Carregar detalhes da issue
echo "📋 Analisando Issue #$ISSUE_NUMBER..."
ISSUE_DATA=$(gh issue view $ISSUE_NUMBER --json title,body,labels)
ISSUE_TITLE=$(echo "$ISSUE_DATA" | jq -r '.title')

# Verificar ACs completados via comentários
echo "🔍 Verificando ACs completados..."
COMPLETED_ACS=$(gh api repos/:owner/:repo/issues/$ISSUE_NUMBER/comments | jq -r '.[] | select(.body | contains("foi **Atendido**")) | .body' | grep -o "AC[0-9]\+" | sort -u)

if [ -z "$COMPLETED_ACS" ]; then
    echo "⚠️ Nenhum AC foi validado com analyze-ac"
    echo "❌ Execute validação dos ACs antes de criar PR"
    exit 1
fi

echo "✅ ACs completados: $COMPLETED_ACS"
```

### 4. **Criação do PR com Template Inteligente**

**Claude Code deve executar:**
```bash
# Título do PR baseado na issue
PR_TITLE="$ISSUE_TITLE (#$ISSUE_NUMBER)"

# Gerar template do PR
cat > /tmp/pr_body.txt << 'EOF'
## Resumo

Implementação dos critérios de aceite da issue com foco nas melhores práticas de desenvolvimento e qualidade de código.

## Critérios de Aceite Implementados

EOF

# Adicionar ACs completados dinamicamente
echo "$COMPLETED_ACS" | while read -r AC; do
    echo "- [x] $AC: Validado e implementado" >> /tmp/pr_body.txt
done

# Adicionar seção de commits
cat >> /tmp/pr_body.txt << 'EOF'

## Commits Incluídos

Os seguintes commits serão mergeados no branch main:

EOF

# Listar commits com detalhes
git log --pretty=format:"- %h: %s" $MAIN_BRANCH..HEAD >> /tmp/pr_body.txt

# Completar template
cat >> /tmp/pr_body.txt << 'EOF'

## Testes e Qualidade

- [x] Todos os testes passando (PHPUnit)
- [x] Análise estática aprovada (PHPStan)
- [x] Código formatado (PSR-12/Pint)
- [x] Testes Python executados (se aplicável)
- [x] ACs validados com analyze-ac

## Checklist Final

- [x] Quality checks executados
- [x] Branch atualizada com main
- [x] Commits seguem padrão do projeto
- [x] Sem secrets ou dados sensíveis
- [x] Documentação atualizada (se necessário)

EOF

# Adicionar footer para fechar a issue automaticamente
echo "" >> /tmp/pr_body.txt
echo "Closes #$ISSUE_NUMBER" >> /tmp/pr_body.txt

echo "📝 Template do PR gerado"
```

### 5. **Execução da Criação do PR**

**Claude Code deve executar:**
```bash
# Criar PR
echo "🚀 Criando Pull Request..."
gh pr create \
    --title "$PR_TITLE" \
    --body-file /tmp/pr_body.txt \
    --base "$MAIN_BRANCH" \
    --head "$(git branch --show-current)" \
    --label "feature" \
    --assignee "@me"

# Capturar URL do PR criado
PR_URL=$(gh pr view --json url -q .url)
echo "✅ PR criado: $PR_URL"
```

### 6. **Pós-Criação: Validações e Monitoring**

**Claude Code deve executar:**
```bash
# Aguardar CI/CD iniciar
echo "⏳ Aguardando CI/CD iniciar..."
sleep 10

# Verificar status dos checks
echo "🔍 Status dos checks:"
gh pr checks $(git branch --show-current) || echo "CI/CD ainda não iniciado"

# Resumo final
echo ""
echo "📋 RESUMO DO PULL REQUEST:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔗 URL: $PR_URL"
echo "📝 Título: $PR_TITLE"
echo "🌿 Branch: $(git branch --show-current) → $MAIN_BRANCH"
echo "📊 Commits: $(git rev-list --count $MAIN_BRANCH..HEAD) commits serão mergeados"
echo "🎯 Issue: #$ISSUE_NUMBER"
echo "✅ ACs: $COMPLETED_ACS"
echo "🚀 Status: $(gh pr view --json state -q .state)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Limpeza
rm -f /tmp/pr_body.txt
```

### 7. **Uso do Comando**

**Para usar este comando:**

```bash
# Automático (detecta issue da branch feat/X)
/create-pr

# Com issue específica (se branch não segue padrão)
ISSUE_NUMBER=51 /create-pr
```

**Pré-requisitos:**
- Branch deve estar atualizada com main
- Quality checks devem passar
- Pelo menos um AC deve estar validado com analyze-ac
- GitHub CLI (gh) deve estar configurado

**Exemplo de output:**
```
✅ Issue detectada: #51
📋 Branch principal: main
🔍 Commits que serão incluídos no PR:
a665843 test(payments): Implementa AC7 - testes automatizados abrangentes
4d1ada8 feat(payments): Implementa AC6 - mensagens de erro instruem contato
📊 Total de commits: 2
🔧 Executando quality checks...
✅ Branch está atualizada
📋 Analisando Issue #51...
✅ ACs completados: AC6 AC7
🚀 Criando Pull Request...
✅ PR criado: https://github.com/ime-usp-br/8thBCSMIF/pull/52
```