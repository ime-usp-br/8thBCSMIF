<task name="Atomic and Secure Push to Remote Repository">

<task_objective>
This workflow executes `git push` incrementally and securely. Each step is a simple command, and the decision on which push command to use (standard push or push with --set-upstream) is made by Cline based on the branch status, ensuring a robust and error-proof process.
</task_objective>

<detailed_sequence_steps>
# Atomic and Secure Push - Detailed Process

**Important Note:** When executing commands that might produce extensive output or hang the terminal, use `| cat` at the end of the command. Example: `your-command-here | cat`.

## 1. Sync and Analyze Status

1.  Fetch updates from the remote repository to ensure local checks are based on the latest version.

    ```xml
    <execute_command>
    <command>git fetch --verbose | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Check the status of the branch in relation to its remote counterpart.

    ```xml
    <execute_command>
    <command>git status | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

3.  List the commits that are about to be pushed.

    ```xml
    <execute_command>
    <command>git log @{u}..HEAD --pretty=format:"%C(yellow)%h %C(reset)- %s %C(green)(%cr) %C(bold blue)<%an>%C(reset)" | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 2. Critical Security Analysis (Assistant's Action)

1.  Get the complete `diff` of what will be pushed for an internal secret scan.

    ```xml
    <execute_command>
    <command>git diff @{u}..HEAD | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  **Instructions for the AI Assistant (Mandatory Action):**
    *   Analyze the `diff` output. If the command fails (first push), execute `git diff origin/main..HEAD | cat` as an alternative.
    *   Look for sensitive data (keys, passwords, tokens).
    *   **If you find anything suspicious:** Stop the process, inform the user of the risk, and ask if they wish to proceed. If "no", terminate the workflow.

## 3. Decision and Execution of Push

1.  Check if the branch already exists on the remote repository to decide which push command to use.

    ```xml
    <execute_command>
    <command>git ls-remote --exit-code --heads origin $(git branch --show-current) | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  **Instructions for the AI Assistant (Decision Logic):**
    *   If the command above was **successful** (exit code 0), the branch already exists. **Proceed to Step 3A.**
    *   If the command above **failed** (exit code other than 0), this is the first push for the branch. **Proceed to Step 3B.**

### 3A. Option 1: Push to an Existing Branch

*Execute this step only if the check in Step 3 was successful.*

```xml
<execute_command>
<command>git push | cat</command>
<requires_approval>true</requires_approval>
</execute_command>
```

### 3B. Option 2: Publish a New Branch (First Push)

*Execute this step only if the check in Step 3 failed.*

```xml
<execute_command>
<command>git push --set-upstream origin $(git branch --show-current) | cat</command>
<requires_approval>true</requires_approval>
</execute_command>
```

## 4. Final Post-Push Confirmation

1.  After the push is approved and executed, confirm that everything is synchronized.

    ```xml
    <execute_command>
    <command>git status | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Use `attempt_completion` to inform the user that the push was completed successfully.

</detailed_sequence_steps>
</task>