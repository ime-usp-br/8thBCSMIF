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

This workflow leverages Python scripts for heavy LLM tasks while Claude Code orchestrates the process:

#### 1. Issue Discovery
```bash
# Check GitHub Projects/Issues to find current in-progress issue
# Identify next AC (Acceptance Criteria) to resolve
```

#### 2. Generate Solution Prompt
```bash
resolve-ac -i <ISSUE> -a <AC> -op -sc
# -op: Output prompt only (for Google AI Studio)
# -sc: LLM selects relevant context files
# Result: Copies context to context_llm/temp/ + shows prompt
```

#### 3. External LLM Execution
- Copy prompt to Google AI Studio (Gemini 2.5 Pro free tier)
- Apply generated code changes manually to project

#### 4. Validation Loop
```bash
analyze-ac -i <ISSUE> -a <AC> -sc
# Checks if AC requirements are met
```

**If AC not accepted:**
- Use analyze-ac output as observation
- Re-run: `resolve-ac -i <ISSUE> -a <AC> -op -sc -o "<analyze_output>"`
- Repeat until AC passes

**If AC accepted:**
```bash
commit-mesage [-i <ISSUE>] -g  # Generate commit message
# Then: git commit, git push
# Move to next AC
```

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