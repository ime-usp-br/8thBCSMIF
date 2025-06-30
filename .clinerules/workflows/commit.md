---
description: "A fully automated workflow to create secure, standardized commits, with automatic staging and integration with GitHub issues."
---

## Guide: Proactive, Secure, and Integrated Commits

This workflow automates the entire commit process: it stages all current changes, performs a security check, gathers context from your history and open GitHub issues, and finally generates a standard commit message for your approval.

### 1. Preparation and Automatic Staging
The AI assistant will automatically add **all current changes** (modified and new files) to the staging area.

<execute_command>
<command>git add .</command>
<# Adds all modified and untracked files to the staging area. #>
<requires_approval>false</requires_approval>
</execute_command>

Now, let's confirm what has been staged.

<execute_command>
<command>git status</command>
<# Displays the final status so you can see exactly what will be included in the commit. #>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Critical Security Analysis (Performed by the AI Assistant)
This is a crucial step. The AI assistant will retrieve the list of all staged changes and analyze them internally for sensitive data.

<execute_command>
<command>git diff --cached | cat</command>
<# This command provides the AI assistant with the content to be analyzed. The '| cat' ensures the terminal does not hang. #>
<requires_approval>false</requires_approval>
</execute_command>

**Instructions for the AI Assistant (Mandatory Action):**
1.  **Analyze the output of the command above.**
2.  **Actively search for:**
    *   **High-risk keywords:** `password`, `secret`, `key`, `token`, `bearer`, `private`, `credential`, `.env`.
    *   **API key patterns:** (e.g., `sk_live_`, `pk_live_`, `ghp_`, long strings with high entropy).
    *   **Suspicious code comments:** (e.g., `// TODO: remove password before committing`).
3.  **IF ANYTHING SUSPICIOUS IS FOUND:**
    *   **Stop the process.**
    *   **Clearly inform the user, showing the problematic code snippet.**
    *   **Ask explicitly:** "I have detected what appears to be sensitive data in the code. Do you wish to proceed with the commit anyway? (yes/no)"
    *   If the answer is "no", **terminate the workflow immediately** and instruct the user to fix the issue.
4.  **IF NOTHING IS FOUND:**
    *   Inform the user: "✅ Security scan complete. No apparent secrets were found." and proceed to the next step.

### 3. Context Gathering: History and Open Issues
To create the best possible commit message, the AI assistant will analyze the project's style and ongoing tasks.

**A. Commit History for Style Patterns:**

<execute_command>
<command>git log -5 --pretty=format:"%C(yellow)%h %C(reset)- %s %n%b%n---" | cat</command>
<# The AI assistant will use this history to learn the project's 'type(scope)' format. #>
<requires_approval>false</requires_approval>
</execute_command>

<<<<<<< Updated upstream
**B. Issues Abertas no GitHub para Conexão de Tarefas (com corpo da issue):**
<!-- Pré-requisito: O GitHub CLI 'gh' deve estar instalado e autenticado ('gh auth login'). -->
<execute_command>
<command>gh issue list --state open --json number,title,labels,body | cat</command>
<# O Cline analisará esta lista para conectar o commit a uma tarefa existente, incluindo o corpo da issue para contexto adicional. #>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Geração da Mensagem de Commit (Ação do Cline)
Com todo o contexto coletado (diff, segurança, histórico, issues, **e o corpo das issues**), o Cline irá agora construir a mensagem de commit ideal.

**Instruções para o Cline:**
1.  Sintetize o `git diff` para entender a mudança.
2.  Use o histórico para definir o `tipo` e `escopo` corretos.
3.  **Cruze as informações do diff com a lista de issues do GitHub, incluindo o corpo das issues.** Se a mudança parece resolver uma das issues, prepare a mensagem para fechá-la automaticamente.
4.  Escreva uma descrição clara e imperativa.
5.  Se a mudança for complexa, adicione um corpo com bullet points, **incorporando informações relevantes do corpo da issue para maior clareza e contexto**.
6.  **IMPORTANTE:** Não use palavras-chave como `Closes #<numero>`, `Fixes #<numero>` ou `Resolves #<numero>` no corpo do commit. Neste projeto, o fechamento de issues é gerenciado pelo Pull Request (PR), não pelo commit.
7.  Use o formato **HEREDOC** para garantir a formatação correta.
=======
**B. Open GitHub Issues for Task Connection (with issue body):**
<!-- Prerequisite: The GitHub CLI 'gh' must be installed and authenticated ('gh auth login'). -->
<execute_command>
<command>gh issue list --state open --json number,title,labels,body | cat</command>
<# The AI assistant will analyze this list to connect the commit to an existing task, including the issue body for additional context. #>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Commit Message Generation (AI Assistant's Action)
With all the context gathered (diff, security analysis, history, issues, **and the issue bodies**), the AI assistant will now construct the ideal commit message.

**Instructions for the AI Assistant:**
1.  Synthesize the `git diff` to understand the change.
2.  Use the commit history to determine the correct `type` and `scope`.
3.  **Cross-reference the diff with the list of GitHub issues, including their bodies.** If the change appears to resolve one of the issues, prepare the message accordingly.
4.  Write a clear, imperative-mood subject line.
5.  If the change is complex, add a body with bullet points, **incorporating relevant information from the issue body for greater clarity and context**.
6.  **IMPORTANT:** Do not use keywords like `Closes #<number>`, `Fixes #<number>`, or `Resolves #<number>` in the commit message. In this project, issues are closed via Pull Requests (PRs), not commits.
7.  **CRITICAL: Avoid problematic characters in the commit message.** Characters such as single quotes (`'`), double quotes (`"`), and backticks (`` ` ``) can be misinterpreted by the terminal, causing issues. Rephrase the message to avoid using these characters or to ensure they do not cause conflict, prioritizing clarity and command-line compatibility.
8.  Use the **HEREDOC** format to ensure proper multi-line formatting.
>>>>>>> Stashed changes

### 5. Final Commit Execution (with User Approval)
The AI assistant will generate the complete `git commit` command. **Your only task is to review the message and approve the execution.**

<execute_command>
<command>
# The AI assistant will generate the 'git commit -m "..."' here.
# Example of a command the AI assistant might generate:
# git commit -m "fix(api): Correct authentication flow via token
#
# - Correctly validates JWT token expiration.
# - Returns a 401 error instead of 500 for invalid tokens.
#
# Ref: #134"
</command>
<# Review the commit message generated by the AI assistant. If it is correct, approve. #>
<requires_approval>true</requires_approval>
</execute_command>

### 6. Commit Verification and Confirmation

After the commit is executed, verify if the message was applied correctly and, if necessary, attempt to fix the problem.

**Instructions:**
1.  **Verify the last commit:** Compare the generated commit hash with the last `git log` hash.
2.  **Extract the message:** Get the message of the last commit.
3.  **Compare with the expected message:** Check if the commit message matches the generated message.
4.  **Retry Logic:**
    *   **If the message is incorrect:**
        *   Execute `git reset HEAD~1 --soft` to undo the commit, keeping the changes staged.
        *   Increment a retry counter (limit of 3).
        *   Inform the user about the error and the attempt to fix it.
        *   Re-execute the "Commit Message Generation" (Step 4) and "Final Commit Execution" (Step 5) steps.
    *   **After 3 failed attempts:**
        *   Stop the workflow.
        *   Inform the user: "Could not generate a valid commit after 3 attempts. Please review the changes and try again. The staging area has been kept intact."
        *   Instruct the user to fix it manually.
5.  **If the message is correct:**
    *   Inform the user: "✅ Commit verified and confirmed successfully."
    *   **End the workflow successfully.**
