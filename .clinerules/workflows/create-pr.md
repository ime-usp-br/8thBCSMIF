---
description: "Guia para criar um Pull Request no GitHub seguindo as melhores práticas do projeto e identificando commits que serão mergeados."
---

## Guia: Criação de Pull Request

O Cline deve executar este workflow completo para criação de PR seguindo melhores práticas e identificando exatamente quais commits serão mergeados no branch principal.

### 1. Análise de Branch e Commits

O Cline deve detectar o número da issue a partir do nome da branch atual (se seguir o padrão `feat/X`) e identificar a branch principal (main/master). Em seguida, deve listar os commits que serão incluídos no PR.

<execute_command>
<command>
CURRENT_BRANCH=$(git branch --show-current)
if [[ "$CURRENT_BRANCH" =~ feat/([0-9]+) ]]; then
    ISSUE_NUMBER="${BASH_REMATCH[1]}"
    echo "✅ Issue detectada: #$ISSUE_NUMBER"
else
    echo "⚠️ Branch não segue padrão feat/X - forneça ISSUE_NUMBER manualmente"
    # O Cline deve ser instruído a perguntar ao usuário ou inferir o ISSUE_NUMBER se esta mensagem for exibida.
    # Para este guia, assumimos que o ISSUE_NUMBER será fornecido ou detectado.
fi

MAIN_BRANCH=$(git remote show origin | grep 'HEAD branch' | cut -d' ' -f5)
echo "📋 Branch principal: $MAIN_BRANCH"

echo "🔍 Commits que serão incluídos no PR:"
git log --oneline $MAIN_BRANCH..HEAD

echo "📊 Total de commits: $(git rev-list --count $MAIN_BRANCH..HEAD)"
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Verificações de Qualidade Obrigatórias

O Cline deve executar os quality checks finais e verificar se a branch está atualizada com a branch principal antes de criar o PR.

<execute_command>
<command>
echo "🔧 Executando quality checks..."
vendor/bin/pint
vendor/bin/phpstan analyse
php artisan test
pytest -v --live

git fetch origin
MAIN_BRANCH=$(git remote show origin | grep 'HEAD branch' | cut -d' ' -f5) # Re-detectar MAIN_BRANCH para garantir
BEHIND_COUNT=$(git rev-list --count HEAD..origin/$MAIN_BRANCH)
if [ "$BEHIND_COUNT" -gt 0 ]; then
    echo "⚠️ Branch está $BEHIND_COUNT commits atrás de $MAIN_BRANCH"
    echo "❌ Faça rebase antes de criar PR: git rebase origin/$MAIN_BRANCH"
    # O Cline deve ser instruído a parar e pedir ao usuário para fazer o rebase.
else
    echo "✅ Branch está atualizada"
fi
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 3. Análise da Issue para Título e Descrição

O Cline deve carregar os detalhes da issue e verificar os ACs completados através dos comentários para construir o título e a descrição do PR.

<execute_command>
<command>
ISSUE_NUMBER_FROM_BRANCH=$(git branch --show-current | sed -n 's/feat\/\([0-9]\+\).*/\1/p')
ISSUE_NUMBER=${ISSUE_NUMBER_FROM_BRANCH:-$ISSUE_NUMBER} # Usa o detectado ou o fornecido

echo "📋 Analisando Issue #$ISSUE_NUMBER..."
ISSUE_DATA=$(gh issue view $ISSUE_NUMBER --json title,body,labels)
ISSUE_TITLE=$(echo "$ISSUE_DATA" | jq -r '.title')

echo "🔍 Verificando ACs completados..."
COMPLETED_ACS=$(gh api repos/ime-usp-br/8thBCSMIF/issues/$ISSUE_NUMBER/comments | jq -r '.[] | select(.body | contains("foi **Atendido**")) | .body' | grep -o "AC[0-9]\+" | sort -u)

if [ -z "$COMPLETED_ACS" ]; then
    echo "⚠️ Nenhum AC foi validado com analyze-ac"
    echo "❌ Execute validação dos ACs antes de criar PR"
    # O Cline deve ser instruído a parar e pedir ao usuário para validar os ACs.
else
    echo "✅ ACs completados: $COMPLETED_ACS"
fi
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Criação do PR com Template Inteligente

O Cline deve gerar o corpo do Pull Request dinamicamente, incluindo um resumo, os ACs implementados, a lista de commits e um checklist final.

<execute_command>
<command>
ISSUE_NUMBER_FROM_BRANCH=$(git branch --show-current | sed -n 's/feat\/\([0-9]\+\).*/\1/p')
ISSUE_NUMBER=${ISSUE_NUMBER_FROM_BRANCH:-$ISSUE_NUMBER} # Usa o detectado ou o fornecido
ISSUE_DATA=$(gh issue view $ISSUE_NUMBER --json title,body,labels)
ISSUE_TITLE=$(echo "$ISSUE_DATA" | jq -r '.title')
COMPLETED_ACS=$(gh api repos/ime-usp-br/8thBCSMIF/issues/$ISSUE_NUMBER/comments | jq -r '.[] | select(.body | contains("foi **Atendido**")) | .body' | grep -o "AC[0-9]\+" | sort -u)
MAIN_BRANCH=$(git remote show origin | grep 'HEAD branch' | cut -d' ' -f5)

PR_TITLE="$ISSUE_TITLE (#$ISSUE_NUMBER)"

cat > /tmp/pr_body.txt << 'EOF'
## Resumo

Implementação dos critérios de aceite da issue com foco nas melhores práticas de desenvolvimento e qualidade de código.

## Critérios de Aceite Implementados

EOF

echo "$COMPLETED_ACS" | while read -r AC; do
    echo "- [x] $AC: Validado e implementado" >> /tmp/pr_body.txt
done

cat >> /tmp/pr_body.txt << 'EOF'

## Commits Incluídos

Os seguintes commits serão mergeados no branch main:

EOF

git log --pretty=format:"- %h: %s" $MAIN_BRANCH..HEAD >> /tmp/pr_body.txt

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

echo "" >> /tmp/pr_body.txt
echo "Closes #$ISSUE_NUMBER" >> /tmp/pr_body.txt

echo "📝 Template do PR gerado"
</command>
<requires_approval>false</requires_approval>
</execute_command>

### 5. Execução da Criação do PR

O Cline deve executar o comando `gh pr create` para criar o Pull Request.

<execute_command>
<command>
ISSUE_NUMBER_FROM_BRANCH=$(git branch --show-current | sed -n 's/feat\/\([0-9]\+\).*/\1/p')
ISSUE_NUMBER=${ISSUE_NUMBER_FROM_BRANCH:-$ISSUE_NUMBER} # Usa o detectado ou o fornecido
ISSUE_DATA=$(gh issue view $ISSUE_NUMBER --json title,body,labels)
ISSUE_TITLE=$(echo "$ISSUE_DATA" | jq -r '.title')
MAIN_BRANCH=$(git remote show origin | grep 'HEAD branch' | cut -d' ' -f5)

PR_TITLE="$ISSUE_TITLE (#$ISSUE_NUMBER)"

gh pr create \
    --title "$PR_TITLE" \
    --body-file /tmp/pr_body.txt \
    --base "$MAIN_BRANCH" \
    --head "$(git branch --show-current)" \
    --label "feature" \
    --assignee "@me"

PR_URL=$(gh pr view --json url -q .url)
echo "✅ PR criado: $PR_URL"
</command>
<requires_approval>true</requires_approval>
</execute_command>

### 6. Pós-Criação: Validações e Monitoring

Após a criação do PR, o Cline deve aguardar o início do CI/CD e verificar o status dos checks, fornecendo um resumo final do Pull Request.

<execute_command>
<command>
PR_URL=$(gh pr view --json url -q .url) # Re-capturar PR_URL caso não esteja disponível
ISSUE_NUMBER_FROM_BRANCH=$(git branch --show-current | sed -n 's/feat\/\([0-9]\+\).*/\1/p')
ISSUE_NUMBER=${ISSUE_NUMBER_FROM_BRANCH:-$ISSUE_NUMBER} # Usa o detectado ou o fornecido
ISSUE_DATA=$(gh issue view $ISSUE_NUMBER --json title,body,labels)
ISSUE_TITLE=$(echo "$ISSUE_DATA" | jq -r '.title')
COMPLETED_ACS=$(gh api repos/ime-usp-br/8thBCSMIF/issues/$ISSUE_NUMBER/comments | jq -r '.[] | select(.body | contains("foi **Atendido**")) | .body' | grep -o "AC[0-9]\+" | sort -u)
MAIN_BRANCH=$(git remote show origin | grep 'HEAD branch' | cut -d' ' -f5)

echo "⏳ Aguardando CI/CD iniciar..."
sleep 10

echo "🔍 Status dos checks:"
gh pr checks $(git branch --show-current) || echo "CI/CD ainda não iniciado"

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

rm -f /tmp/pr_body.txt
</command>
<requires_approval>false</requires_approval>
</execute_command>

### Pré-requisitos:
- Branch deve estar atualizada com main
- Quality checks devem passar
- Pelo menos um AC deve estar validado com analyze-ac
- GitHub CLI (gh) deve estar configurado
