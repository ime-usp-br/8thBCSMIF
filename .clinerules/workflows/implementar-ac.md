<task name="Implement Acceptance Criteria (AC)">

<task_objective>
This workflow guides the assistant through a structured process to implement a single Acceptance Criteria (AC) from a GitHub issue. The process focuses on context gathering, interactive planning with the user, focused implementation, and validation through testing, using standard Cline tools.
</task_objective>

<detailed_sequence_steps>
# Implement Acceptance Criteria - Detailed Process

**Important Note:** When executing commands that might produce extensive output or hang the terminal, use `| cat` at the end of the command. Example: `your-command-here | cat`.

## 1. Gather Context and Information

1.  Use the `ask_followup_question` command to ask the user for the issue number and the AC number to be implemented.

2.  Get the full details of the issue to understand the scope of work.

    ```xml
    <execute_command>
    <command>
    # Replace {issue_number} with the provided number.
    gh issue view {issue_number} --json title,body,labels | cat
    </command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

3.  List the project files to understand the current structure.

    ```xml
    <execute_command>
    <command>ls -R | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

4.  **Assistant's Action:** Based on the issue analysis, form hypotheses about which existing files are relevant and read their content using the `read_file` tool.

## 2. Planning Phase (User Interaction)

1.  **Instructions for the AI Assistant (Mandatory Action):**
    *   Synthesize all collected information: the AC requirements, project structure, and content of relevant files.
    *   Formulate a detailed implementation plan describing:
        *   Which files you intend to create or modify.
        *   The main logical changes that will be made.
        *   How you will address the specific requirements of the AC.
    *   Use the `ask_followup_question` command to present this plan to the user and request explicit approval before proceeding.

## 3. Implementation and Coding

1.  **Instructions for the AI Assistant:**
    *   After receiving user approval, execute the implementation.
    *   Use the `<write_to_file>` or `<replace_in_file>` tools to make the necessary code changes.
    *   The changes MUST be strictly limited to the scope of the AC and the approved plan.
    *   Create tests (Unit or Feature) that validate the new functionality.

## 4. Validation (Running Tests)

1.  Run the relevant tests to ensure the new functionality is correct and there are no regressions.

    ```xml
    <execute_command>
    <command>
    # The assistant MUST specify a file, filter, or group to run only relevant tests.
    # Example: ./vendor/bin/phpunit --filter=RelevantTestNameTest
    ./vendor/bin/phpunit | cat
    </command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Use `attempt_completion` to inform the user that the AC implementation is complete, tests have passed, and the changes are ready to be committed.

</detailed_sequence_steps>
</task>