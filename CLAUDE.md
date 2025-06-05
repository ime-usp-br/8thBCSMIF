# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application for the 8th Brazilian Conference on Statistical Modeling in Insurance and Finance (8th BCSMIF) registration system. It's built on the Laravel 12 USP Starter Kit and integrates with USP's authentication and data systems.

## Core Stack & Architecture

- **Framework:** Laravel 12 with PHP >= 8.2
- **Frontend:** TALL Stack (Tailwind CSS 4, Alpine.js 3, Livewire 3, Laravel/Vite)
- **Database:** MySQL (supports SQLite for testing)
- **Authentication:** Laravel Breeze + USP Senha Única (uspdev/senhaunica-socialite)
- **Permissions:** Spatie Laravel Permission
- **USP Integration:** uspdev/replicado for institutional data validation

### Key Models and Services

- **User Model** (`app/Models/User.php`): Extended with HasRoles, HasSenhaunica traits, includes `codpes` field for USP number validation
- **ReplicadoService** (`app/Services/ReplicadoService.php`): Validates USP numbers and emails against institutional database
- **Event/Fee Models**: Conference-specific models for registration system

## Essential Development Commands

### Laravel/PHP Commands
```bash
# Development server
php artisan serve

# Database operations
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed

# Testing
php artisan test                    # PHPUnit tests
php artisan dusk                    # Browser tests (requires Chrome/Chromium)
php artisan dusk:chrome-driver --detect  # Install ChromeDriver

# Code quality
vendor/bin/pint                     # PSR-12 code formatting
vendor/bin/phpstan analyse          # Static analysis

# Interactive tools
php artisan tinker                  # Laravel REPL
php artisan pail                    # Log monitoring
```

### Frontend Commands
```bash
npm run dev                         # Development build with HMR
npm run build                       # Production build
```

### Unified Development
```bash
composer run dev                    # Starts all services (Laravel server, queue, logs, Vite)
```

## Environment Configuration

Critical environment variables:
- **USP Senha Única:** `SENHAUNICA_CALLBACK`, `SENHAUNICA_KEY`, `SENHAUNICA_SECRET`
- **USP Replicado:** `REPLICADO_HOST`, `REPLICADO_PORT`, `REPLICADO_DATABASE`, `REPLICADO_USERNAME`, `REPLICADO_PASSWORD`, `REPLICADO_CODUND`, `REPLICADO_CODBAS`

## Testing Configuration

- **PHPUnit:** Uses SQLite in-memory database for unit/feature tests
- **Dusk:** Requires `.env.dusk.local` with separate test database and `APP_URL` pointing to test server
- Browser tests need Chrome/Chromium installed

## Development Scripts & Workflows

### Proven Development Workflow

#### Claude Pro Subscription Workflow (Recommended)

With Claude Pro subscription, Claude Code can execute complete AC implementation cycles autonomously:

#### 1. Issue Discovery & Analysis
- Read issue details: `gh issue view <ISSUE_NUMBER>`
- Identify specific AC (Acceptance Criteria) to implement
- Analyze current codebase state and requirements

#### 2. Implementation Cycle
- Create TodoWrite workflow for task tracking
- Implement required changes following established patterns
- Run mandatory quality checks:
  ```bash
  vendor/bin/pint                     # PSR-12 formatting
  vendor/bin/phpstan analyse          # Static analysis  
  php artisan test                    # PHPUnit tests
  pytest -v --live                    # Python tests (if applicable)
  ```

#### 3. Validation & Completion
- Run context update: `context-generate --stages git`
- Execute validation:
  ```bash
  printf "y\ny\ny\n" | python3 scripts/tasks/llm_task_analyze_ac.py -i <ISSUE> -a <AC> -sc
  ```
- If validation fails: Address issues and repeat cycle
- If validation passes: Proceed to commit and documentation

#### 4. Commit & Documentation Cycle
- Stage changes: `git add .`
- Analyze commit patterns: `git log --oneline -10`
- Create commit message following project conventions (NO AI tool references)
- Commit and push to current branch
- Add validation comment to GitHub issue via `gh api`
- Update issue body to mark AC as complete `[x]`

#### Alternative: External LLM Workflow (Fallback)

For cases requiring external LLM usage (complex analysis, API quota limits):

#### 1. Generate Solution Context
```bash
resolve-ac -i <ISSUE> -a <AC> -op -sc
# -op: Output prompt only (for external LLM)
# -sc: LLM selects relevant context files
# Result: Copies context to context_llm/temp/ + shows prompt
```

#### 2. External LLM Execution
- Copy prompt to Google AI Studio (Gemini 2.5 Pro free tier)
- Apply generated code changes manually to project

#### 3. Follow same validation loop as above

### Direct Task Scripts (via ~/.bashrc aliases)

```bash
# Primary workflow tasks
analyze-ac          # python3 scripts/tasks/llm_task_analyze_ac.py
resolve-ac          # python3 scripts/tasks/llm_task_resolve_ac.py  
commit-mesage       # python3 scripts/tasks/llm_task_commit_mesage.py
create-pr           # python3 scripts/tasks/llm_task_create_pr.py

# Support utilities  
issue-create        # python3 scripts/create_issue.py
context-generate    # python3 scripts/generate_context.py
copy-sc             # python3 scripts/copy_selected_context.py
```

### Key Script Options

**resolve-ac**: Generate implementation code
- `-i <issue>`: Issue number (required)
- `-a <ac>`: AC number (required)  
- `-op`: Only output prompt (for external LLM)
- `-sc`: Enable context selection by LLM
- `-o "<text>"`: Additional observation/feedback

**analyze-ac**: Validate AC completion
- `-i <issue>`: Issue number (required)
- `-a <ac>`: Specific AC to check (optional)
- `-sc`: Enable context selection

**Context Generation**: Use `context-generate --stages <stage_list>` for selective context collection.

### Context Selection Strategy

**For External LLM Scripts (Gemini):** Always use `-sc` flag due to free tier context window limitations. The `-sc` flag enables context selection by LLM, ensuring only relevant files are included in the prompt.

**For Claude Code Direct Implementation:** No context limitations with Claude Pro subscription. Claude Code can access the entire codebase as needed for comprehensive understanding and implementation.

### Claude Pro Workflow Advantages

**Streamlined Process:**
- Single interface for issue analysis, implementation, and validation
- No manual prompt copying or external LLM context switching
- Integrated access to all development tools (git, testing, linting)

**Enhanced Capabilities:**
- Full codebase context without artificial limitations
- Direct file system access for comprehensive analysis
- Integrated quality checks and validation in single session
- TodoWrite workflow for transparent task tracking

**Improved Reliability:**
- No API quota rotations or rate limiting delays
- Consistent model performance and availability
- Integrated error handling and iterative refinement
- Direct GitHub integration for issue management

### Post-Implementation Quality Checks

**MANDATORY:** After every `resolve-ac` implementation, run ALL quality checks before validation:

```bash
# Run all quality checks in sequence
vendor/bin/pint                     # PSR-12 formatting
vendor/bin/phpstan analyse          # Static analysis  
php artisan test                    # PHPUnit tests
php artisan dusk                    # Browser tests
pytest -v --live                    # Python tests
```

**ALL tests must pass** before proceeding to `analyze-ac` validation. This ensures code quality and prevents integration issues.

### Workflow Lessons Learned

**CRITICAL: Always Follow the Established Workflow** - Even for "simple" changes, ALWAYS use `resolve-ac` first rather than implementing directly. The workflow exists to ensure consistency and proper validation.

**Context Generation is MANDATORY Before Validation** - ALWAYS run `context-generate --stages git` (or appropriate stages) before `analyze-ac` to ensure the LLM has access to recent changes. This is critical for accurate validation.

**Complete Test Coverage Required** - When implementing ACs, ensure tests cover ALL scenarios mentioned in the acceptance criteria. Partial test coverage will cause `analyze-ac` to fail validation.

**Validation Before Declaration** - Never declare an AC as "completed" until `analyze-ac` confirms it passes. Implementation alone is insufficient without proper validation.

**Interactive Scripts:** Always use `printf "y\ny\ny\n"` for auto-confirming script prompts in `analyze-ac`, `resolve-ac`, and other LLM tasks. This is essential for:
- Context selection confirmation
- Final response acceptance
- Response saving confirmation
Be prepared to wait for API quota rotations (up to 7 keys) when using external LLM services.

**Test Implementation Best Practices:**
- Use existing events/data when possible rather than creating fake entities
- Mock services appropriately to test specific conditions (e.g., zero fees)
- Add explicit assertions for the exact behavior being tested
- Update existing tests to include new assertion requirements

**Commit Message Standards:**
- Analyze recent commit patterns with `git log --oneline -10` for consistency
- Follow project conventions and established patterns
- Focus on the specific AC/feature implemented
- NEVER include AI tool references ("Generated with Claude Code", etc.)
- Use HEREDOC format for multi-line commit messages
- Include relevant issue references (#XX) where appropriate

### Advanced Workflow: Complete AC Implementation & Validation Cycle

**Post-Implementation Workflow Best Practices:**

**Stage and Commit Changes:**
- Stage all changes: `git add .`
- Analyze commit patterns: `git log --oneline -10` for consistency
- Create descriptive commit messages following project conventions
- CRITICAL: Never include AI tool references in commit messages
- Use HEREDOC format for multi-line commits to ensure proper formatting
- Commit and push to current branch immediately after validation

**Context Update for Validation:**
- MANDATORY: Run `context-generate --stages git` after any code changes
- This ensures LLM validation tools have access to latest changes including:
  - Updated source code
  - New test files
  - Recent commit history
  - Current repository state

**Automated Validation Execution:**
- Use `printf "y\ny\ny\n"` for fully automated script execution
- This handles all interactive prompts in sequence:
  - Context file selection confirmation
  - Final response acceptance
  - Response saving confirmation
- Essential for uninterrupted validation workflow

**GitHub Integration Workflow:**
- Use `gh api` for programmatic issue comments with analysis results
- Include commit hash in validation comments for traceability
- Edit issue body directly using `gh issue edit` to update AC status
- Mark completed ACs with `[x]` checkbox syntax
- Maintain clear audit trail of completion through comments

**Complete Documentation Cycle:**
- Capture validation results for issue tracking
- Update project documentation with lessons learned
- Document both successful patterns and common pitfalls
- Ensure knowledge transfer for future implementations

## Code Quality Standards

- **PSR-12** compliance enforced by Laravel Pint
- **Level 9** PHPStan analysis
- All new code must include appropriate tests
- Follow Laravel conventions and existing codebase patterns

## Integration Guidelines

When working with USP-specific features:
- Use `ReplicadoService::validarNuspEmail()` for USP user validation
- Leverage `HasSenhaunica` trait for authentication flows
- Test with both USP and non-USP users scenarios
- Handle ReplicadoServiceException appropriately

## File Structure Notes

- **Livewire components:** Follow class-based approach in `app/Livewire/`
- **Blade views:** Located in `resources/views/livewire/`
- **Frontend assets:** `resources/css/app.css` and `resources/js/app.js`
- **Database:** Migrations, factories, and seeders for Events/Fees models