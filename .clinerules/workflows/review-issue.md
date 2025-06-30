---
description: "A workflow to review a GitHub issue based on project context, documentation, and best practices. This workflow assumes the issue content is in Brazilian Portuguese (pt-BR)."
---

**Nota Importante:** Ao executar comandos manualmente ou adicionar novos comandos a este workflow, se o comando puder gerar uma saída que precise ser exibida ou que possa travar o terminal, utilize `| cat` ao final do comando. Exemplo: `seu-comando-aqui | cat`.

## Guide: Comprehensive GitHub Issue Review

This workflow guides the AI assistant in reviewing a specific GitHub issue. The goal is to ensure the issue is well-defined, actionable, and aligns with the project's current state and documentation. The review process SHALL use RFC2119 keywords to structure its feedback.

This workflow requires the issue number as a parameter.

### 1. Fetch Issue and Project Context
The AI assistant MUST first gather all necessary information about the issue and the project.

**A. Fetch the Issue Details (Critical Step):**
This step is **CRITICAL** for the success of the entire workflow. The assistant **MUST** obtain the full content of the issue before proceeding.

The assistant **SHALL** use a command-line tool like the `gh` CLI to retrieve the issue's title, body, and labels.

**Error Handling and Retry Policy:**
- If the command to fetch the issue fails for any reason (e.g., network error, invalid issue number), the assistant **MUST** retry the command up to a maximum of **3 times**.
- If all 3 attempts fail, the assistant **MUST NOT** proceed with the workflow. Instead, it **SHALL** ask the user for guidance on how to proceed.
- The assistant **MUST NOT** invent or assume the content of the issue.

`<execute_command>`
`<command># The AI assistant SHOULD replace {issue_number} with the actual issue number.`
`# gh issue view {issue_number} --json title,body,labels,comments | cat`
`</command>`
`<# This command fetches the primary content of the issue for analysis. It is a critical step. #>`
`<requires_approval>false</requires_approval>`
`</execute_command>`

**B. Gather Project Documentation:**
To understand the project's standards and current implementation, the assistant MUST review key documentation files.

`<execute_command>`
`<command># The AI assistant MAY review other documents in the /docs folder if relevant.`
`cat docs/guia_desenvolvimento.md docs/stack_tec.md docs/rfc2119_pt_BR.md | cat`
`</command>`
`<# Reads essential project documentation to provide context for the review. #>`
`<requires_approval>false</requires_approval>`
`</execute_command>`

**C. Analyze Current Project Structure:**
The assistant SHOULD get a list of the current files to understand the project's structure.

`<execute_command>`
`<command>ls -R | cat</command>`
`<# Provides a recursive listing of all files, offering a snapshot of the project's architecture. #>`
`<requires_approval>false</requires_approval>`
`</execute_command>`

**D. Fetch Issue Template based on Labels:**
The assistant MUST identify the primary category of the issue from its labels (e.g., `feature`, `bug`, `chore`) and read the corresponding template file from the `templates/issue_bodies/` directory.

`<execute_command>`
`<command># The AI assistant SHOULD determine the correct file to read based on the issue's labels.`
`# Example for a 'feature' label:`
`cat templates/issue_bodies/feature_body.md | cat`
`</command>`
`<# Reads the appropriate issue template to ensure the review follows a standard structure. #>`
`<requires_approval>false</requires_approval>`
`</execute_command>`

### 2. AI-Powered Issue Analysis
This is the core of the workflow. The AI assistant MUST perform a detailed analysis of the issue content, which is expected to be in **Brazilian Portuguese (pt-BR)**.

**Instructions for the AI Assistant (Mandatory Action):**
1.  **Analyze the Original Issue:** Read the fetched issue title and body carefully.
2.  **Assess Clarity and Completeness:**
    *   The issue title **MUST** be concise and clearly summarize the task. It **SHOULD** follow the Conventional Commits standard (e.g., `[FEAT]`, `[FIX]`).
    *   The original issue body **SHOULD** provide enough detail to fill in the standard template.
3.  **Check Against Project Documentation:**
    *   The proposed changes **MUST NOT** conflict with the guidelines in `guia_desenvolvimento.md` or the project's `stack_tec.md`.
4.  **Verify Best Practices:**
    *   The issue **SHOULD** have appropriate labels. The primary label (`feature`, `bug`, etc.) will determine the template to be used.
    *   The scope of the issue **SHOULD** be realistic. If not, the assistant **SHOULD** recommend breaking it down.
5.  **Formulate Improved Issue Content using the Template:**
    *   Based on the analysis, the AI assistant **MUST** generate an improved, edited version of the issue's body by **filling out the structure provided by the fetched issue template**.
    *   The new content **SHALL** transfer the information from the original issue into the standardized sections of the template (e.g., "Descrição da Funcionalidade", "Critérios de Aceite").
    *   The assistant **SHOULD** use its understanding of the project to enrich the issue with relevant details, ensuring all sections of the template are completed appropriately.
    .   The assistant **MUST** use RFC2119 keywords (e.g., `DEVE`, `PODERIA`) as specified in the project documentation.

### 3. Update the Issue
The AI assistant will update the issue directly with the improved content.

**Instructions for the AI Assistant:**
1.  Construct the new title and body for the issue.
2.  Use a command-line tool like `gh` to edit the issue.
3.  The command below is a template. The AI assistant **MUST** replace `{issue_number}`, `"{new_title}"`, and `"{new_body}"` with the actual values.

`<execute_command>`
`<command>`
`# The AI assistant will generate the complete gh issue edit command here.`
`# Example format:`
`# new_title="[FEAT] Implement User Authentication with django-allauth"`
`# new_body="## Context / Motivation`
`# To provide a personalized experience, user accounts are necessary.`
`# ... (rest of the improved body) ...`
`# `
`# ## Acceptance Criteria`
`# - [ ] AC1: Authentication routes for login, logout, and registration **MUST** be configured.`
`# - [ ] AC2: Authentication templates **MUST** be styled with Tailwind CSS."`
`# `
`# gh issue edit {issue_number} --title "$new_title" --body "$new_body"`
`</command>`
`<# Review the improved issue content generated by the AI assistant. If correct, approve to update the issue on GitHub. #>`
`<requires_approval>true</requires_approval>`
`</execute_command>`

### 4. Final Verification
After editing the issue, the assistant SHOULD confirm the action was successful.

`<execute_command>`
`<command># The AI assistant SHOULD replace {issue_number} with the actual issue number.`
`# gh issue view {issue_number} --json title,body | cat`
`</command>`
`<# Displays the issue's title and body to verify the edits were applied successfully. #>`
`<requires_approval>false</requires_approval>`
`</execute_command>`
