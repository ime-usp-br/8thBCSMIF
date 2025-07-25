<task name="Complete Validation of Acceptance Criteria (AC)">

<task_objective>
This guide describes the process for validating an Acceptance Criteria (AC) using Cline's tools. The workflow focuses on ensuring that the implementation meets the issue's requirements and the project's quality standards before proceeding to the commit and documentation stages.
</task_objective>

<detailed_sequence_steps>
# Complete Validation of Acceptance Criteria - Detailed Process

**Important Note:** When executing commands that might produce extensive output or hang the terminal, use `| cat` at the end of the command. Example: `your-command-here | cat`.

## 1. Gather Issue Context

1.  Use `ask_followup_question` to get the issue number and AC number from the user for validation.

2.  Analyze the issue to understand the context and requirements of the AC.

    ```xml
    <execute_command>
    <command>gh issue view {issue_number} | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 2. Run Mandatory Quality Checks

1.  Execute all mandatory quality checks for the project.

    ```xml
    <execute_command>
    <command>vendor/bin/pint | cat && vendor/bin/phpstan analyse | cat && php artisan test | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  **Instructions for the AI Assistant:** If any of the quality checks fail, the workflow must be stopped. Inform the user about the failure and provide the error logs so that corrections can be made before attempting validation again.

## 3. Prepare and Validate the Implementation

1.  Add the changes to the staging area so that the analysis context includes the latest modifications.

    ```xml
    <execute_command>
    <command>git add . | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  **AI Assistant's Action:**
    *   With the changes staged, internally analyze the `diff` (`git diff --cached`) and the content of the modified files.
    *   Compare the implementation with the AC requirements obtained in Step 1.
    *   Formulate a conclusion on whether the AC has been met.

## 4. Present Results and Next Steps

1.  Use `attempt_completion` to present your analysis and conclusion to the user.

2.  **If the AC has been met:** Inform the user and suggest the next steps, such as running the `commit`, `push`, and `comentario` workflows.

3.  **If the AC has NOT been met:** Inform the user, explaining in detail which points were not fulfilled, and await instructions to correct the implementation.

</detailed_sequence_steps>
</task>