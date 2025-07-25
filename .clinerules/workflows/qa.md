<task name="Run Quality Assurance (QA) Checks">

<task_objective>
This workflow executes all mandatory project quality checks in a defined sequence. Each step must pass before proceeding, ensuring that the code meets formatting, static analysis, and testing standards before integration.
</task_objective>

<detailed_sequence_steps>
# Run Quality Assurance Checks - Detailed Process

**Important Note:** When executing commands that might produce extensive output or hang the terminal, use `| cat` at the end of the command. Example: `your-command-here | cat`.

## 1. Code Formatting (Pint)

1.  Run Pint to ensure compliance with the PSR-12 standard.

    ```xml
    <execute_command>
    <command>vendor/bin/pint | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 2. Static Analysis (PHPStan)

1.  Run PHPStan to perform static analysis of the code.

    ```xml
    <execute_command>
    <command>vendor/bin/phpstan analyse | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 3. Unit and Feature Tests (PHPUnit)

1.  Run the unit and feature tests with PHPUnit.

    ```xml
    <execute_command>
    <command>php artisan test | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 4. Python Tests (Pytest)

1.  Run the Python tests with Pytest, if applicable.

    ```xml
    <execute_command>
    <command>pytest -v --live | cat</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 5. Browser Tests (Dusk - Optional)

1.  Check for and run browser tests (Dusk) if they exist in the project.

    ```xml
    <execute_command>
    <command>if grep -r "dusk" tests/ >/dev/null 2>&1; then echo "Running Dusk tests..." && php artisan dusk | cat; else echo "No Dusk tests found"; fi</command>
    <requires_approval>false</requires_approval>
    </execute_command>
    ```

## 6. Conclusion

1.  **Instructions for the AI Assistant:**
    *   If all checks pass, use `attempt_completion` to inform the user that the quality check was completed successfully.
    *   If any check fails, **stop the workflow**, inform the user which step failed, and present the error logs so they can fix the issue before trying again.

</detailed_sequence_steps>
</task>