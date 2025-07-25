<task name="Resolve Acceptance Criteria (AC)">

<task_objective>
This workflow guides the AI assistant in the process of resolving a single Acceptance Criteria (AC). The process emphasizes information gathering, upfront planning with the user, and focused implementation, without using complex project automation scripts.
</task_objective>

<detailed_sequence_steps>
# Resolve Acceptance Criteria - Detailed Process

**Important Note:** When executing commands that might produce extensive output or hang the terminal, use `| cat` at the end of the command. Example: `your-command-here | cat`.

**Expected arguments:** `{issue_number}` `{ac_number}`

## 1. Gather Essential Information

1.  Get the context of the task and the project environment.

    ```xml
    <execute_command>
    <command>
    # The AI assistant MUST replace {issue_number} with the actual issue number.
    gh issue view {issue_number} --json title,body,labels | cat
    </command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

2.  Read the development guides and coding standards to ensure the implementation aligns with project practices.

    ```xml
    <read_file>
    <path>docs/guia_de_desenvolvimento.md</path>
    </read_file>
    ```

    ```xml
    <read_file>
    <path>docs/padroes_codigo_boas_praticas.md</path>
    </read_file>
    ```

3.  **Assistant's Action:** Based on the analysis of the issue and documentation, form hypotheses about which existing files are relevant to resolving the AC and read their content using the `read_file` tool.

## 2. Planning Phase (User Interaction)

1.  **Instructions for the AI Assistant (Mandatory Action):**
    *   Synthesize all collected information.
    *   Formulate a detailed implementation plan describing which files you intend to create or modify and the main logical changes.
    *   Use `ask_followup_question` to present this plan to the user and request explicit approval before proceeding.

## 3. Implementation and Coding

1.  **Instructions for the AI Assistant:**
    *   After user approval, execute the implementation.
    *   Use the `<write_to_file>` or `<replace_in_file>` tools to make the necessary changes.
    *   Changes MUST be strictly limited to the scope of the AC and the approved plan.

## 4. Validation (Relevant Tests)

1.  If relevant to the change made, run the appropriate unit or feature tests to ensure the new functionality is correct and that there are no regressions.

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

2.  Use `attempt_completion` to inform the user that the resolution of the Acceptance Criteria is complete.

</detailed_sequence_steps>
</task>