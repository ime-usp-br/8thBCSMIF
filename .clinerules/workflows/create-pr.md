<task name="Create Intelligent and Secure Pull Request">

<task_objective>
This workflow automates the creation of Pull Requests (PRs) on GitHub. It analyzes the changes in the current branch, performs a security check, gathers context from commit history and open issues, and generates a standardized PR with a title, description, and labels, awaiting final user approval.
</task_objective>

<detailed_sequence_steps>
# Create Intelligent and Secure Pull Request - Detailed Process

**Important Note:** When executing commands that might produce extensive output or hang the terminal, use `| cat` at the end of the command. Example: `your-command-here | cat`.

## 1. Analyze Branch and Repository Status

1.  Verify that the working tree is clean and the branch is ready for a PR.

    ```xml
    <execute_command>
    <command>git status | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Ensure the current branch is not the main branch (main/master).

    ```xml
    <execute_command>
    <command>git branch --show-current | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 2. Ensure Branch is Pushed to Remote

1.  Check if the local branch already exists on the remote repository.

    ```xml
    <execute_command>
    <command>git ls-remote --exit-code --heads origin $(git branch --show-current) | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  **Instructions for the AI Assistant:**
    *   If the command above **failed** (non-zero exit code), the branch needs to be pushed first. Execute `git push --set-upstream origin $(git branch --show-current) | cat`.
    *   If the command **succeeded** (exit code 0), proceed to the next step.

## 3. Gather Changes for Analysis

1.  List the commits that will be included in the PR to understand the scope.

    ```xml
    <execute_command>
    <command>git log origin/main..HEAD --pretty=format:"%C(yellow)%h %C(reset)- %s %C(green)(%cr) %C(bold blue)<%an>%C(reset)" | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Get the complete `diff` for security analysis and content understanding.

    ```xml
    <execute_command>
    <command>git diff origin/main..HEAD | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 4. Critical Security Analysis (Assistant's Action)

1.  **Instructions for the AI Assistant (Mandatory Action):**
    *   Analyze the complete `diff` from the previous step.
    *   Actively search for sensitive data: passwords, API keys, tokens, etc.
    *   **If anything suspicious is found:** Stop the process and alert the user, requesting confirmation to proceed. If denied, terminate the workflow.
    *   **If nothing is found:** Proceed to the next step.

## 5. Gather Context for PR Content

1.  Analyze recent PR history to understand the project's style and conventions.

    ```xml
    <execute_command>
    <command>gh pr list --state merged --limit 10 --json number,title,body | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Analyze open issues to connect the PR to existing tasks.

    ```xml
    <execute_command>
    <command>gh issue list --state open --json number,title,labels,body | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 6. Generate PR Content (Assistant's Action)

1.  **Instructions for the AI Assistant:**
    *   Analyze the `diff` and commits to understand the purpose of the changes.
    *   Use PR and issue history to generate a title and description that follow project patterns.
    *   **Title:** Should be clear, descriptive, and follow conventions.
    *   **Description (Body):**
        *   Should include a summary, a list of changes, and how they were tested.
        *   **MANDATORY:** Use `Closes #number` to link and automatically close the relevant issue.
    *   Suggest appropriate labels (e.g., `feature`, `bug`, `documentation`).

## 7. Create the Pull Request (with User Approval)

1.  The assistant will generate the complete `gh pr create` command, with title, body, and metadata, for user approval.

    ```xml
    <execute_command>
    <command>
    # The AI assistant will generate the complete 'gh pr create' command here.
    # Example format:
    # gh pr create \
    #   --title "feat: Add user authentication system" \
    #   --body "$(cat <<'EOF'
    # ## Summary
    # Implements a comprehensive user authentication system with JWT tokens.
    # 
    # ## Changes Made
    # - Adds authentication middleware for Express routes.
    # - Implements JWT token generation and validation.
    # 
    # ## Related Issues
    # Closes #42
    # EOF
    # )" \
    #   --label "feature" \
    #   --assignee "@me" | cat
    </command>
    <requires_approval>true</requires_approval>
    </execute_command>
    ```

## 8. Post-Creation Verification

1.  After the PR is created, display its information, including the URL.

    ```xml
    <execute_command>
    <command>gh pr view --json number,title,url | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Use `attempt_completion` to notify the user of the successful operation and provide the PR link.

</detailed_sequence_steps>
</task>