---
description: "Workflow atômico e robusto para executar git push, com verificações de segurança e lógica de decisão gerenciada pelo Cline."
---

**Nota Importante:** Ao executar comandos manualmente ou adicionar novos comandos a este workflow, se o comando puder gerar uma saída que precise ser exibida ou que possa travar o terminal, utilize `| cat` ao final do comando. Exemplo: `seu-comando-aqui | cat`.

## Guia: Push Atômico e Seguro para Repositório Remoto

Este guia executa `git push` de forma incremental e segura. Cada passo é um comando simples e a decisão sobre qual comando de push usar é feita pelo Cline com base no status da branch.

### 1. Sincronização com o Repositório Remoto
Primeiro, vamos buscar as atualizações do repositório remoto para garantir que nossas verificações locais estejam baseadas na versão mais recente.

<execute_command>
<command>git fetch --verbose | cat</command>
<# Sincroniza o estado do remoto. Essencial para evitar um push cego. #>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Análise de Status e Commits Pendentes
Agora, o Cline irá obter o contexto do que está prestes a ser enviado.

**A. Verificação de Status:**

<execute_command>
<command>git status | cat</command>
<# Mostra se a branch está à frente, atrás ou divergiu da remota. '| cat' garante que não trave. #>
<requires_approval>false</requires_approval>
</execute_command>

**B. Listagem dos Commits a Serem Enviados:**

<execute_command>
<command>git log @{u}..HEAD --pretty=format:"%C(yellow)%h %C(reset)- %s %C(green)(%cr) %C(bold blue)<%an>%C(reset)" | cat</command>
<# Lista os commits que você tem e o remoto não. Se der erro, é o primeiro push, e o Cline saberá lidar com isso. #>
<requires_approval>false</requires_approval>
</execute_command>

### 3. Análise de Segurança Crítica (Executada pelo Cline)
O Cline irá obter o `diff` completo do que será enviado e analisá-lo internamente em busca de segredos.

<execute_command>
<command>git diff @{u}..HEAD | cat</command>
<# Fornece o conteúdo para o Cline analisar. Se for o primeiro push, o Cline deve comparar com a branch principal (ex: main ou master). #>
<requires_approval>false</requires_approval>
</execute_command>

**Instruções para o Cline (Sua Ação Obrigatória):**
1.  **Analise o `diff` acima.** Se o comando falhar (primeiro push), execute `git diff origin/main..HEAD | cat` (ou `origin/master`) para obter as alterações.
2.  **Procure por dados sensíveis** (chaves, senhas, tokens).
3.  **SE ENCONTRAR ALGO SUSPEITO:**
    *   **Pare o processo.**
    *   Informe o usuário sobre o risco e pergunte se deseja prosseguir. Se "não", encerre o workflow.

### 4. Verificação de Existência da Branch Remota
Este é o passo de decisão. O Cline executará um comando para saber se a branch já existe no `origin`. O resultado determinará o próximo passo.

<execute_command>
<command>git ls-remote --exit-code --heads origin $(git branch --show-current)</command>
<# Este comando simples falha (exit code 2) se a branch não existir no remoto, e tem sucesso (exit code 0) se existir. #>
<requires_approval>false</requires_approval>
</execute_command>

**Instruções para o Cline:**
*   Se o comando acima foi **bem-sucedido** (exit code 0), a branch já existe. **Pule para a Etapa 5A.**
*   Se o comando acima **falhou** (exit code diferente de 0), é o primeiro push. **Pule para a Etapa 5B.**

---
### 5A. OPÇÃO 1: Push para Branch Existente
*Execute esta etapa apenas se a verificação da Etapa 4 foi bem-sucedida.*

<execute_command>
<command>git push | cat</command>
<# Comando de push padrão para uma branch que já é rastreada. Requer sua aprovação. #>
<requires_approval>true</requires_approval>
</execute_command>

---
### 5B. OPÇÃO 2: Publicar Nova Branch (Primeiro Push)
*Execute esta etapa apenas se a verificação da Etapa 4 falhou.*

<execute_command>
<command>git push --set-upstream origin $(git branch --show-current) | cat</command>
<# Publica a branch no remoto e configura o rastreamento (upstream). Requer sua aprovação. #>
<requires_approval>true</requires_approval>
</execute_command>

---
### 6. Confirmação Final Pós-Push
Após o push ser aprovado e executado, vamos confirmar que tudo está sincronizado.

<execute_command>
<command>git status | cat</command>
<# O status final deve informar: "Your branch is up to date with 'origin/...'". #>
<requires_approval>false</requires_approval>
</execute_command>
