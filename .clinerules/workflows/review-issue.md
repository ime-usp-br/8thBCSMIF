<task name="Comprehensive GitHub Issue Review">

<task_objective>
This workflow guides the AI assistant in reviewing a specific GitHub issue. The goal is to ensure the issue is well-defined, actionable, and aligns with the project's current state and documentation. The review process SHALL use RFC 2119 keywords to structure its feedback. This workflow assumes the issue content is in English.
</task_objective>

<detailed_sequence_steps>
# Comprehensive GitHub Issue Review - Detailed Process

**Important Note:** When executing commands that might produce extensive output or hang the terminal, use `| cat` at the end of the command. Example: `your-command-here | cat`.

## 1. Fetch Issue and Project Context

1.  Use `ask_followup_question` to get the number of the issue to be reviewed from the user.

2.  Fetch the complete details of the issue (title, body, labels, comments).

    ```xml
    <execute_command>
    <command>
    # The AI assistant SHOULD replace {issue_number} with the actual issue number.
    gh issue view {issue_number} --json title,body,labels,comments | cat
    </command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

3.  Gather essential project documentation for context.

    ```xml
    <execute_command>
    <command>
    # The AI assistant MAY review other documents in the /docs folder if relevant.
    cat docs/guia_desenvolvimento.md docs/stack_tec.md docs/rfc2119_pt_BR.md | cat
    </command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

4.  Analyze the current project structure.

    ```xml
    <execute_command>
    <command>ls -R | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 2. AI-Powered Issue Analysis

1.  **Instructions for the AI Assistant (Mandatory Action):**
    *   Analyze the fetched issue content.
    *   **Clarity and Completeness:** The issue title **MUST** be concise. The body **MUST** provide enough detail.
    *   **Compliance:** The proposed changes **MUST NOT** conflict with `guia_desenvolvimento.md`.
    *   **Best Practices:** The issue **MUST** have appropriate labels. The scope **MUST** be realistic.
    *   **Formulate Improved Content:** Based on the analysis, generate an improved, edited version of the issue's body, filling out a standard template structure (e.g., "Description", "Acceptance Criteria"). The new content **SHALL** transfer information from the original issue, enrich it with relevant details, and use RFC 2119 keywords.

## 3. Update the Issue (with User Approval)

1.  **Instructions for the AI Assistant:**
    *   Construct the new title and body for the issue.
    *   Use a command-line tool like `gh` to generate the edit command for user approval.

    ```xml
    <execute_command>
    <command>
    # The AI assistant will generate the complete gh issue edit command here.
    # Example format:
    # new_title="[FEAT] Implement User Authentication with django-allauth"
    # new_body="## Context / Motivation
    # To provide a personalized experience, user accounts are necessary.
    # ... (rest of the improved body) ...
    # ## Acceptance Criteria
    # - [ ] AC1: Authentication routes for login, logout, and registration MUST be configured."
    # 
    # gh issue edit {issue_number} --title "$new_title" --body "$new_body" | cat
    </command>
    <requires_approval>true</requires_approval>
    </execute_command>
    ```

## 4. Final Verification

1.  After editing the issue, confirm the action was successful.

    ```xml
    <execute_command>
    <command>
    # The AI assistant SHOULD replace {issue_number} with the actual issue number.
    gh issue view {issue_number} --json title,body | cat
    </command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Use `attempt_completion` to inform the user that the issue was successfully reviewed and updated.

</detailed_sequence_steps>
</task>