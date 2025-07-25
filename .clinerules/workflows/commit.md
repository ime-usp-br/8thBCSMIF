<task name="Create Standardized Commit">

<task_objective>
This workflow automates the commit creation process, ensuring all current changes are staged, scanned for security, and that the commit message is intelligently generated based on project history and open GitHub issues before being submitted for user approval.
</task_objective>

<detailed_sequence_steps>
# Create Standardized Commit - Detailed Process

**Important Note:** When executing commands that might produce extensive output or hang the terminal, use `| cat` at the end of the command. Example: `your-command-here | cat`.

## 1. Prepare and Analyze Changes

1.  Add all current changes (modified and new files) to the staging area.

    ```xml
    <execute_command>
    <command>git add . | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Display the status to confirm what will be included in the commit.

    ```xml
    <execute_command>
    <command>git status | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

3.  Get the complete `diff` of the staged changes for content analysis.

    ```xml
    <execute_command>
    <command>git diff --cached | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 2. Critical Security Analysis (Assistant's Action)

1.  **Instructions for the AI Assistant (Mandatory Action):**
    *   Analyze the output of the `git diff --cached` command from the previous step.
    *   Actively search for sensitive data: passwords, API keys (`sk_live_`, `ghp_`), tokens, `.env` files, suspicious comments.
    *   **If anything suspicious is found:** Stop the process and ask the user if they wish to proceed, clearly stating the risk. If the answer is "no", terminate the workflow.
    *   **If nothing is found:** Proceed to the next step.

## 3. Gather Context for the Commit Message

1.  Analyze the commit history to understand the project's style and patterns.

    ```xml
    <execute_command>
    <command>git log -5 --pretty=format:"%C(yellow)%h %C(reset)- %s %n%b%n---" | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Analyze open GitHub issues to connect the commit to an existing task.

    ```xml
    <execute_command>
    <command>gh issue list --state open --json number,title,labels,body | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 4. Generate Commit Message (Assistant's Action)

1.  **Instructions for the AI Assistant:**
    *   Synthesize the `diff` to understand the change.
    *   Use the commit history to determine the correct `type` and `scope` (e.g., `feat(api)`, `fix(ui)`).
    *   Cross-reference the `diff` with the GitHub issues to find the relevant issue and reference it (e.g., `(#123)`).
    *   Write a clear, imperative-mood subject line.
    *   If necessary, add a body with bullet points detailing the changes.
    *   **Important:** Avoid keywords like `Closes` or `Fixes` in commits; this should be done in the Pull Request.

## 5. Execute the Commit (with User Approval)

1.  The AI assistant will generate the complete `git commit` command with the formatted message for user approval.

    ```xml
    <execute_command>
    <command>
    # The AI assistant will generate the git commit command here.
    # Example:
    # git commit -m "fix(api): Correct token authentication flow
    # 
    # - Correctly validates JWT token expiration.
    # - Returns a 401 error instead of 500 for invalid tokens.
    # 
    # Ref: #134" | cat
    </command>
    <requires_approval>true</requires_approval>
    </execute_command>
    ```

## 6. Final Verification

1.  After the commit, check the log to confirm the message was applied correctly.

    ```xml
    <execute_command>
    <command>git log -1 --pretty=format:"%C(yellow)%h %C(reset)- %s %n%b" | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Use `attempt_completion` to notify the user that the commit was created successfully.

</detailed_sequence_steps>
</task>