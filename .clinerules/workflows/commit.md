---
description: "Workflow totalmente automatizado para criar commits seguros e padronizados, com stage automático e integração com issues do GitHub."
---

## Guia: Commit Proativo, Seguro e Integrado

Este workflow automatiza todo o processo de commit: adiciona todos os arquivos, realiza uma verificação de segurança, busca contexto no histórico e em issues abertas no GitHub, e finalmente gera uma mensagem de commit padrão para sua aprovação.

### 1. Preparação e Stage Automático
O Cline irá adicionar automaticamente **todas as alterações atuais** (arquivos modificados e novos) ao stage.

<execute_command>
<command>git add .</command>
<# Adiciona todos os arquivos modificados e não rastreados ao stage. #>
<requires_approval>false</requires_approval>
</execute_command>

Agora, vamos confirmar o que foi adicionado ao stage.

<execute_command>
<command>git status</command>
<# Exibe o status final para você ver exatamente o que será incluído no commit. #>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Análise de Segurança Crítica (Executada pelo Cline)
Esta é a etapa mais importante. O Cline irá obter a lista de todas as alterações prontas para o commit e analisá-las internamente em busca de dados sensíveis.

<execute_command>
<command>git diff --cached | cat</command>
<# Este comando fornece ao Cline o conteúdo que será analisado. O '| cat' garante que o terminal não fique preso. #>
<requires_approval>false</requires_approval>
</execute_command>

**Instruções para o Cline (Sua Ação Obrigatória):**
1.  **Analise a saída do comando acima.**
2.  **Procure ativamente por:**
    *   **Palavras-chave de risco:** `senha`, `password`, `secret`, `key`, `token`, `bearer`, `private`, `credential`, `.env`.
    *   **Padrões de chaves de API:** (ex: `sk_live_`, `pk_live_`, `ghp_`, strings longas com alta entropia).
    *   **Comentários de código suspeitos:** (ex: `// TODO: remover senha antes de commitar`).
3.  **SE ENCONTRAR ALGO SUSPEITO:**
    *   **Pare o processo.**
    *   **Informe o usuário claramente, mostrando o trecho de código problemático.**
    *   **Pergunte explicitamente:** "Detectei o que parece ser um dado sensível no código. Deseja prosseguir com o commit mesmo assim? (sim/não)"
    *   Se a resposta for "não", **encerre o workflow imediatamente** e instrua o usuário a corrigir o problema.
4.  **SE NADA FOR ENCONTRADO:**
    *   Informe ao usuário: "✅ Análise de segurança concluída. Nenhum segredo aparente foi encontrado." e prossiga para a próxima etapa.

### 3. Coleta de Contexto: Histórico e Issues Abertas
Para criar a melhor mensagem de commit possível, o Cline irá analisar o estilo do projeto e as tarefas em andamento.

**A. Histórico de Commits para Padrão de Estilo:**

<execute_command>
<command>git log -5 --pretty=format:"%C(yellow)%h %C(reset)- %s %n%b%n---" | cat</command>
<# O Cline usará este histórico para aprender o formato de 'tipo(escopo)' do projeto. #>
<requires_approval>false</requires_approval>
</execute_command>

**B. Issues Abertas no GitHub para Conexão de Tarefas:**
<!-- Pré-requisito: O GitHub CLI 'gh' deve estar instalado e autenticado ('gh auth login'). -->
<execute_command>
<command>gh issue list --state open --json number,title,labels | cat</command>
<# O Cline analisará esta lista para conectar o commit a uma tarefa existente. #>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Geração da Mensagem de Commit (Ação do Cline)
Com todo o contexto coletado (diff, segurança, histórico, issues), o Cline irá agora construir a mensagem de commit ideal.

**Instruções para o Cline:**
1.  Sintetize o `git diff` para entender a mudança.
2.  Use o histórico para definir o `tipo` e `escopo` corretos.
3.  **Cruze as informações do diff com a lista de issues do GitHub.** Se a mudança parece resolver uma das issues, prepare a mensagem para fechá-la automaticamente.
4.  Escreva uma descrição clara e imperativa.
5.  Se a mudança for complexa, adicione um corpo com bullet points.
6.  Use palavras-chave como `Closes #<numero>`, `Fixes #<numero>` ou `Resolves #<numero>` no corpo do commit para vincular e fechar a issue no GitHub.
7.  Use o formato **HEREDOC** para garantir a formatação correta.

### 5. Execução do Commit Final (com Aprovação do Usuário)
O Cline irá gerar o comando `git commit` completo. **Sua única tarefa é revisar a mensagem e aprovar a execução.**

<execute_command>
<command>
# O Cline irá gerar o comando 'git commit -m "..."' aqui.
# Exemplo de comando que o Cline pode gerar:
# git commit -m "fix(api): Corrige o fluxo de autenticação via token
#
# - Valida a expiração do token JWT corretamente.
# - Retorna erro 401 em vez de 500 para tokens inválidos.
#
# Closes #134"
</command>
<# Revise a mensagem de commit gerada pelo Cline. Se estiver correta, aprove. #>
<requires_approval>true</requires_approval>
</execute_command>
