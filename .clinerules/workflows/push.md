---
description: "Workflow otimizado para executar git push com verificações de segurança, status e sincronização com o repositório remoto."
---

## Guia: Push Inteligente e Seguro para Repositório Remoto

Este guia executa `git push` de forma segura, garantindo que o repositório local esteja sincronizado, que não haja secrets sendo enviados e que o usuário tenha total controle sobre a ação.

### 1. Preparação e Sincronização Inicial
Primeiro, vamos sincronizar com o repositório remoto para obter o estado mais recente de todas as branches. Isso é crucial para evitar conflitos e garantir que nossas verificações sejam precisas.

<execute_command>
<command>git fetch</command>
<# Sincroniza o estado do repositório remoto sem alterar seus arquivos locais. #>
<requires_approval>false</requires_approval>
</execute_command>

Depois, verificamos o status atual e a branch para garantir que estamos no lugar certo.

<execute_command>
<command>git status</command>
<# O status agora mostrará se sua branch está à frente, atrás ou se divergiu da remota. #>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Análise dos Commits a Serem Enviados
Vamos listar os commits que existem localmente mas ainda não foram enviados para o repositório remoto.

**Nota:** Se a sua branch ainda não existe no remoto, este comando mostrará todos os commits da branch.

<execute_command>
<command>git log @{u}..HEAD | cat</command>
<# Lista os commits pendentes de push. Se der erro, significa que o "upstream" não está configurado (primeiro push). #>
<requires_approval>false</requires_approval>
</execute_command>

### 3. Revisão de Segurança: Verificação de Alterações e Secrets
Esta é a etapa mais crítica. Vamos inspecionar **todas as alterações** que serão enviadas. Verifique cuidadosamente se há chaves de API, senhas, arquivos `.env` ou qualquer outra informação sensível.

<execute_command>
<command>git diff --stat @{u}..HEAD | cat && git diff @{u}..HEAD | cat</command>
<# Mostra um resumo das alterações (diff --stat) e depois o diff completo para uma revisão detalhada. #>
<requires_approval>false</requires_approval>
</execute_command>

> **⚠️ Ação Necessária:** Revise a saída do comando acima. Se encontrar qualquer dado sensível, **NÃO CONTINUE**. Interrompa o workflow, remova os dados do histórico do Git (usando `git rebase -i` ou `git reset`) e faça um novo commit.

### 4. Push para o Repositório Remoto (com Aprovação)
Agora, vamos executar o push. O comando é inteligente:
- Se for o primeiro push desta branch, ele a publicará e configurará para rastrear a branch remota (`--set-upstream`).
- Se a branch já existir, ele simplesmente enviará os novos commits.

Por ser uma ação que modifica o repositório remoto, ela **requer sua aprovação explícita**.

<execute_command>
<command>
# Detecta a branch atual
CURRENT_BRANCH=$(git branch --show-current)

# Verifica se a branch remota já existe
if git show-ref --verify --quiet refs/remotes/origin/$CURRENT_BRANCH; then
  # Se já existe, faz um push normal
  git push
else
  # Se for a primeira vez, configura o upstream
  echo "Primeiro push para a branch '$CURRENT_BRANCH'. Configurando upstream..."
  git push --set-upstream origin $CURRENT_BRANCH
fi
</command>
<# Este bloco de script executa a lógica de push inteligente. #>
<requires_approval>true</requires_approval>
</execute_command>

### 5. Confirmação Pós-Push
Para finalizar, vamos confirmar que a sua branch local e a branch remota estão perfeitamente sincronizadas. Os dois códigos (hashes) exibidos devem ser idênticos.

<execute_command>
<command>echo "Hash Local (HEAD):" && git rev-parse HEAD && echo "\nHash Remoto (@{u}):" && git rev-parse @{u}</command>
<# Compara o hash do último commit local com o da branch remota. #>
<requires_approval>false</requires_approval>
</execute_command>

<execute_command>
<command>git status</command>
<# O status deve informar: "Your branch is up to date with 'origin/...'". #>
<requires_approval>false</requires_approval>
</execute_command>