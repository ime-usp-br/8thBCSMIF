# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 🔄 WORKFLOW SEQUENCE (OBRIGATÓRIO)

**SEMPRE siga esta sequência exata para implementar qualquer AC (Acceptance Criteria):**

### 1. **Análise e Planejamento**
- Use `TodoWrite` para planejar as tarefas
- Leia a issue completa: `gh issue view <ISSUE_NUMBER>`
- Identifique o AC específico a implementar
- Analise dependências e padrões existentes no código

### 2. **Implementação**
- Implemente as mudanças seguindo padrões do projeto
- **SEMPRE** adicione testes que comprovem a funcionalidade (mesmo que o AC não exija explicitamente)
- Siga convenções de código existentes

### 3. **Quality Checks (OBRIGATÓRIOS)**
```bash
vendor/bin/pint                     # PSR-12 formatting
vendor/bin/phpstan analyse          # Static analysis  
php artisan test                    # PHPUnit tests
pytest -v --live                    # Python tests (se aplicável)
```

### 4. **Validação (CRÍTICO)**
```bash
git add .
python3 scripts/generate_context.py --stages git
printf "y\ny\ny\n" | python3 scripts/tasks/llm_task_analyze_ac.py -i <ISSUE> -a <AC> -sc
```
**⚠️ SÓ AVANCE SE analyze-ac APROVAR! Caso contrário, atenda as exigências.**

### 5. **Commit & Documentação**
```bash
git log -5                          # Analise formato (NÃO use --oneline)
git commit -m "$(cat <<'EOF'
tipo(escopo): Descrição principal (#issue)

- Bullet point com mudança específica 1
- Bullet point com mudança específica 2
- Bullet point com mudança específica 3
- Atende ACX: Descrição do critério atendido
EOF
)"
git push                            # ANTES do comentário GitHub
```

### 6. **Documentação GitHub**

#### **🔴 PASSO CRÍTICO: Verificar Padrão de Comentários ANTES de Elaborar**
```bash
# SEMPRE verificar comentários existentes para manter padrão
gh api repos/:owner/:repo/issues/<ISSUE>/comments

# Se houver menos de 3 comentários na issue atual, verificar issues fechadas similares
COMMENT_COUNT=$(gh api repos/:owner/:repo/issues/<ISSUE>/comments | jq length)
if [ "$COMMENT_COUNT" -lt 3 ]; then
    gh issue list --state closed --label feature --limit 5
    gh api repos/:owner/:repo/issues/<ISSUE_FECHADA>/comments
fi
```

#### **Formato Obrigatório do Comentário:**
- **Título:** `## Conclusão sobre o Critério de Aceite X (ACX) da Issue #Y`
- **Critério:** Citar exatamente o texto do AC
- **Análise:** Seções numeradas explicando implementação detalhada
- **Conclusão:** "O Critério de Aceite X (ACX) foi **Atendido**."
- **Rodapé:** `---\n**Validação realizada no commit:** [hash](link)`

#### **Processo de Criação do Comentário:**
1. **Analisar padrão existente:** Observe formatação, estrutura e estilo dos comentários
2. **Reformatar saída do analyze-ac:** NÃO copie diretamente - adapte o conteúdo ao padrão observado
3. **Manter consistência:** Use exatamente o mesmo formato dos demais comentários
4. **Incluir rastreabilidade:** Link do commit no formato `[hash](url)`

#### **Submissão do Comentário:**
```bash
# Criar comentário formatado manualmente baseado no analyze-ac
cat > /tmp/comment.txt << 'EOF'
## Conclusão sobre o Critério de Aceite X (ACX) da Issue #Y

**Critério de Aceite (ACX):** "Texto exato do critério"

**Análise:**

1. [Reformular primeira análise do analyze-ac seguindo padrão observado]
2. [Reformular segunda análise do analyze-ac seguindo padrão observado]
...

**Conclusão:**

O Critério de Aceite X (ACX) foi **Atendido**.
EOF

# Adicionar rodapé com link do commit
echo "---" >> /tmp/comment.txt
echo "**Validação realizada no commit:** [hash](https://github.com/owner/repo/commit/hash)" >> /tmp/comment.txt

# Verificar antes de enviar
cat /tmp/comment.txt

# Submeter comentário
gh api repos/:owner/:repo/issues/<ISSUE>/comments -F body=@/tmp/comment.txt
```

#### **Diretrizes Críticas:**
- **🔴 REFORMATAR, NÃO COPIAR:** Adapte o conteúdo do analyze-ac ao padrão observado
- **🔴 CONSISTÊNCIA ABSOLUTA:** Mantenha exatamente o mesmo formato dos comentários existentes
- **🔴 VERIFICAÇÃO OBRIGATÓRIA:** Se < 3 comentários na issue atual, consulte issues fechadas
- **🔴 ZERO HEREDOC:** NUNCA use HEREDOC em /tmp/comment.txt (causa "EOF < /dev/null")
- **🔴 SEMPRE VERIFICAR:** Use `cat /tmp/comment.txt` antes do `gh api`

---

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

**Mock Implementation Issues & Solutions:**
- **Problem**: Mocks may not work when services are instantiated via `app()` helper in controllers
- **Solution**: Focus tests on the primary functionality being validated rather than forcing complex mocks
- **Alternative**: When mocking fails, use real services and validate the core behavior (e.g., database associations, relationships)
- **Best Practice**: Prefer dependency injection in controller constructors/methods over `app()` calls for better testability

**Commit Message Standards:**
- **CRITICAL:** Use `git log -5` (NOT `git log --oneline`) to see FULL commit message format
- `--oneline` flag only shows first line, missing the complete multi-line structure
- Follow project's bullet-point format:
  ```
  tipo(escopo): Descrição principal (#issue)
  
  - Bullet point describing specific change 1
  - Bullet point describing specific change 2
  - Bullet point describing specific change 3
  - Final line indicating which AC is fulfilled (if applicable)
  ```
- Focus on the specific AC/feature implemented
- NEVER include AI tool references ("Generated with Claude Code", etc.)
- Use HEREDOC format for multi-line commit messages to ensure proper formatting
- Include relevant issue references (#XX) where appropriate

### Advanced Workflow: Complete AC Implementation & Validation Cycle

**Post-Implementation Workflow Best Practices:**

**Stage and Commit Changes:**
- Stage all changes: `git add .`
- **CRITICAL:** Analyze commit patterns: `git log -5` (NOT `--oneline`) to see full message structure
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
- **CRITICAL:** Always post the EXACT output from `analyze-ac` script as issue comment
- Use file-based approach for complex messages: `gh api repos/:owner/:repo/issues/N/comments -F body=@/tmp/comment.txt`
- Include commit hash in validation comments for traceability
- Edit issue body directly using `gh issue edit` to update AC status
- Mark completed ACs with `[x]` checkbox syntax
- Maintain clear audit trail of completion through comments

**Complete Documentation Cycle:**
- Capture validation results for issue tracking
- Update project documentation with lessons learned
- Document both successful patterns and common pitfalls
- Ensure knowledge transfer for future implementations

### Autonomous Workflow Interruption Handling

**Interruption Identification & Resolution:**
When executing autonomous AC implementation cycles, document any interruptions encountered and their solutions for continuous workflow improvement.

**Common Interruption Patterns:**

**🔴 Critical Interruptions (Must Fix Immediately):**
- Mock failures due to service instantiation patterns (`app()` vs dependency injection)
- Test database configuration issues
- Missing dependencies or relationship configurations

**🟡 Quality Interruptions (Address During Implementation):**
- PHPStan warnings about null safety
- Code formatting inconsistencies
- Test assertion specificity improvements

**🟢 Process Interruptions (Workflow Optimizations):**
- Context selection and API quota management
- Validation script automation improvements
- Git workflow optimizations

**Resolution Documentation Process:**
1. **Identify**: Note exact error/interruption and context
2. **Solve**: Implement pragmatic solution focused on AC completion
3. **Document**: Add solution pattern to CLAUDE.md for future reference
4. **Validate**: Ensure solution doesn't break existing functionality

### Workflow Lessons Learned

**Successful Patterns:**
- Autonomous execution of full cycle (discovery → implementation → validation → commit)
- Effective use of existing placeholder code that just needed activation
- Quality checks integration working seamlessly
- GitHub API integration for automated issue management

**Interruption #1 - Mock Service Issues:**
- **Context**: FeeCalculationService mock not applied due to `app()` instantiation in controller
- **Problem**: Test expecting specific mock values but real service returning different results
- **Solution**: Adapted test to validate core functionality (pivot table associations) without forcing mock
- **Learning**: When mocks fail, focus on the primary AC requirement rather than forcing unreliable mocks
- **Future Prevention**: Consider dependency injection patterns for better testability

**Process Optimizations Identified:**
- `printf "y\ny\ny\n"` automation worked perfectly for all validation scripts
- Context generation before validation is critical for accurate analysis
- Real-time documentation of solutions during implementation improves future cycles

**Interruption #2 - Git Commit Message Format Analysis:**
- **Context:** Using `git log --oneline` to analyze commit patterns for consistency
- **Problem:** `--oneline` flag only shows first line of commits, missing the complete multi-line structure with bullet points that the project follows
- **Root Cause:** Misunderstanding of git log flags led to incomplete pattern analysis and incorrect commit format
- **Solution:** Always use `git log -5` (or similar) to see FULL commit message structure including:
  - Main line: `tipo(escopo): Descrição principal (#issue)`
  - Blank line
  - Bullet points with specific changes: `- Description of change`
  - Optional final line indicating AC fulfillment
- **Learning:** Proper commit format analysis requires seeing the complete message structure, not just the summary line
- **Implementation:** Updated CLAUDE.md to emphasize using `git log -5` and document the exact bullet-point format expected

**Interruption #3 - GitHub Comment Formatting Issues (CRÍTICO - RECORRENTE):**
- **Context:** Complex messages with code blocks and special characters fail when passed directly to `gh api`
- **Problem:** Shell escaping issues with backticks, backslashes, and multi-line content
- **Solution:** Use file-based approach: save content to `/tmp/comment.txt` and use `-F body=@/tmp/comment.txt`
- **Learning:** Always post EXACT `analyze-ac` output for consistent validation documentation
- **Implementation:** Create temp file, use `-F` flag, ensures accurate content delivery
- **🔴 CRITICAL RECURRING ISSUE:** HEREDOC delimiter ("EOF < /dev/null") ALWAYS appears in GitHub comments
- **🔴 MANDATORY FIX:** NEVER use HEREDOC for /tmp/comment.txt creation. Use alternative methods:
  - **✅ WORKING SOLUTION:** Use `cp llm_outputs/analyze-ac/[timestamp].txt /tmp/comment.txt` to copy exact analyze-ac output
  - Add footer with `echo "" >> /tmp/comment.txt && echo "---" >> /tmp/comment.txt && echo "**Validação realizada no commit:** [hash]" >> /tmp/comment.txt`
  - AVOID: Complex shell escaping, printf with backticks, HEREDOC (causes "EOF < /dev/null")
  - ALWAYS verify file content with `cat /tmp/comment.txt` before `gh api` call
  - **ZERO TOLERANCE:** Any HEREDOC artifacts in GitHub comments is unacceptable

**Interruption #4 - Padrão de Comentários GitHub Inconsistente (CRÍTICO - RECORRENTE):**
- **Context:** Formatação de comentários de validação AC sem verificar padrão existente na issue
- **Problem:** Comentários com formatação inconsistente quebram padrão estabelecido no projeto
- **Root Cause:** Copiar diretamente output do analyze-ac ao invés de reformatar seguindo padrão observado
- **Critical Issues Identified:**
  1. **Cópia Direta:** Usar output do analyze-ac "as-is" ignora padrão estabelecido
  2. **Verificação Insuficiente:** Não consultar issues fechadas quando < 3 comentários na atual
  3. **Inconsistência de Links:** Formato de commit hash variando entre comentários
- **Solution:** 
  ```bash
  # 1. Verificar comentários existentes primeiro
  COMMENT_COUNT=$(gh api repos/:owner/:repo/issues/<ISSUE>/comments | jq length)
  if [ "$COMMENT_COUNT" -lt 3 ]; then
      gh issue list --state closed --label feature --limit 5
      gh api repos/:owner/:repo/issues/<ISSUE_FECHADA>/comments
  fi
  
  # 2. Reformatar analyze-ac seguindo padrão observado (NÃO copiar diretamente)
  # 3. Usar formato consistente de commit: [hash](url)
  ```
- **Mandatory Process:** 
  1. Analisar padrão dos comentários existentes
  2. Reformatar conteúdo do analyze-ac seguindo este padrão 
  3. Manter estrutura: Critério → Análise (numerada) → Conclusão → Rodapé com commit
  4. Verificar com `cat /tmp/comment.txt` antes de submeter
- **Learning:** Padrão de comentários é parte crítica da documentação - consistência é obrigatória
- **Zero Tolerance:** Qualquer comentário que não siga o padrão estabelecido é inaceitável

## Code Quality Standards

- **PSR-12** compliance enforced by Laravel Pint
- **Level 9** PHPStan analysis
- All new code must include appropriate tests
- Follow Laravel conventions and existing codebase patterns

## Internationalization (i18n) Standards

**MANDATORY:** All user-facing text, logs, and communications MUST use Laravel's localization system:

### Required Localization Practices

**1. User Interface Text:**
- ALL strings displayed to users MUST use `__()` function
- Store translation keys in `lang/en.json` and `lang/pt_BR.json`
- Use descriptive English keys as default: `__('Payment Proof Uploaded - 8th BCSMIF')`

**2. Email Templates:**
- Subject lines MUST be localized: `subject: __('Payment Proof Uploaded - 8th BCSMIF')`
- All email content MUST use `__()` functions for text elements
- Maintain consistency between coordinator and user templates

**3. Log Messages:**
- Application logs MUST use localized messages
- Error messages shown to users MUST be translatable
- Debug/internal logs may use English but prefer localization when user-visible

**4. Exception Messages:**
- User-facing exception messages MUST be localized
- Use translation keys for consistent error messaging
- Provide meaningful context in translation keys

**5. Validation Messages:**
- Custom validation messages MUST be localized
- Follow Laravel's validation translation patterns
- Store in appropriate `lang/{locale}/validation.php` files

**6. Translation Key Standards:**
- Use English as the key language for consistency
- Keys should be descriptive and self-documenting
- Maintain alphabetical order in JSON files
- Group related translations logically

**Example Implementation:**
```php
// ✅ CORRECT - Localized
return new Envelope(
    subject: __('Payment Proof Uploaded - 8th BCSMIF'),
);

// ❌ INCORRECT - Hardcoded
return new Envelope(
    subject: 'Comprovante de Pagamento Enviado - 8th BCSMIF',
);
```

**Translation Files:**
- `lang/en.json`: English translations (base language)
- `lang/pt_BR.json`: Portuguese (Brazil) translations
- Maintain parity between all language files
- Add new keys to ALL supported languages simultaneously

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