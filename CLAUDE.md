# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 🔄 WORKFLOW SEQUENCE CLAUDE CODE (OBRIGATÓRIO)

**Claude Code deve executar este workflow completo autonomamente para implementar qualquer AC:**

### 1. **Análise e Planejamento Inicial**
- **SEMPRE** use `TodoWrite` para criar workflow transparente das tarefas
- Leia a issue completa: `gh issue view <ISSUE_NUMBER>`
- Identifique o AC específico e seus requisitos exatos
- **ATENÇÃO ESPECIAL**: Para requisitos "incrementais", verifique se operações são aditivas (attach/create) não substitutivas (sync/update)
- Analise dependências e padrões existentes no código

### 2. **Implementação com Validação de Requisitos**
- Implemente mudanças seguindo padrões do projeto
- **CRÍTICO**: Para funcionalidades "incrementais", use métodos aditivos (attach, create) não substitutivos (sync, update)
- **SEMPRE** adicione testes que comprovem a funcionalidade
- Verifique se implementação atende exatamente o comportamento descrito no AC

### 3. **Quality Checks Automáticos (OBRIGATÓRIOS ANTES DE VALIDAÇÃO)**
```bash
vendor/bin/pint                     # PSR-12 formatting
vendor/bin/phpstan analyse          # Static analysis  
php artisan test                    # PHPUnit tests
pytest -v --live                    # Python tests (se aplicável)
```
**🔴 TODOS DEVEM PASSAR ANTES DE PROSSEGUIR PARA VALIDAÇÃO.**
**🔴 SE QUALQUER FALHAR: CORRIGIR E REPETIR OS 4 COMANDOS.**

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

**MANDATORY:** After every resolve ac implementation, run ALL quality checks before validation:

```bash
# Run all quality checks in sequence
vendor/bin/pint                     # PSR-12 formatting
vendor/bin/phpstan analyse          # Static analysis  
php artisan test                    # PHPUnit tests
php artisan dusk                    # Browser tests
pytest -v --live                    # Python tests
```

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

## MCP Server Integration - Context7

Claude Code tem acesso ao servidor MCP Context7 que fornece documentação atualizada e exemplos de código para milhares de bibliotecas. Baseado nas tecnologias específicas do projeto 8th BCSMIF:

### Context7 Server - Documentação & Integração de Bibliotecas

**Funcionalidades Disponíveis:**
- **Acesso a Documentação em Tempo Real**: Consulte documentação atualizada de milhares de bibliotecas
- **Recuperação de Snippets de Código**: Acesse exemplos práticos e padrões de implementação
- **Resolução de Bibliotecas**: Resolva automaticamente nomes de pacotes para IDs compatíveis
- **Orientação Específica por Framework**: Suporte especializado para Laravel, React, Vue e outros frameworks

## 🎯 Bibliotecas Específicas do Projeto (Testadas e Disponíveis)

### **Laravel 12 Framework**
- **ID Context7**: `/context7/laravel_com-docs-12.x`
- **Snippets**: 4.920 exemplos de código
- **Casos de Uso Específicos**:
  - Eloquent ORM patterns
  - Validation rules e #[Validate] attributes
  - Model relationships
  - Database migrations
  - Artisan commands

**Exemplo prático:**
```bash
# Buscar padrões de validação para formulários
mcp__context7__get-library-docs "/context7/laravel_com-docs-12.x" --topic "validation" --tokens 3000

# Documentação de Eloquent para models
mcp__context7__get-library-docs "/context7/laravel_com-docs-12.x" --topic "eloquent" --tokens 5000
```

### **Livewire 3.4 (Full-Stack Framework)**
- **ID Context7**: `/context7/livewire_laravel_com-docs`
- **Snippets**: 681 exemplos
- **Casos de Uso do Projeto**:
  - Formulários reativos de inscrição
  - Validação em tempo real com `#[Validate]`
  - Form objects para organização
  - Interação com Alpine.js

**Padrões Testados:**
- ✅ Validação com `#[Validate('required|min:5')]`
- ✅ Form objects para separação de responsabilidades
- ✅ Real-time validation com `wire:model.blur`
- ✅ Error handling com `@error('field')`

### **Spatie Laravel Permission**
- **ID Context7**: `/spatie/laravel-permission`
- **Snippets**: 158 exemplos
- **Aplicação no Projeto**:
  - Middleware de roles para rotas administrativas
  - Controle de acesso a eventos
  - Gestão de permissões de coordenadores

**Implementações Testadas:**
- ✅ Middleware `role:admin` para rotas
- ✅ Blade directives `@role('admin')`
- ✅ Seeders para roles e permissions
- ✅ Verificações com `$user->hasRole('coordinator')`

### **Alpine.js 3.14 (JavaScript Reativo)**
- **ID Context7**: `/alpinejs/alpine`
- **Snippets**: 425 exemplos
- **Integração com Livewire**:
  - Event handling para formulários
  - Modais e dropdowns
  - Interações sem JavaScript customizado

**Diretivas Relevantes:**
- ✅ `@click` para eventos de click
- ✅ `x-data` para estado local
- ✅ `x-show/x-if` para visibilidade condicional
- ✅ `x-transition` para animações

### **Tailwind CSS 3.1**
- **ID Context7**: `/tailwindlabs/tailwindcss.com`
- **Snippets**: 2.066 exemplos
- **Aplicação no Layout**:
  - Componentes de UI responsivos
  - Sistema de cores USP (usp-blue-pri, usp-blue-sec, usp-yellow)
  - Utility-first approach

## 🔧 Casos de Uso Específicos por Fase

**Para funcionalidades de inscrição:**
```bash
# Pesquisar padrões de formulários Livewire
mcp__context7__resolve-library-id "Livewire 3"
mcp__context7__get-library-docs "/context7/livewire_laravel_com-docs" --topic "form validation" --tokens 3000

# Verificar padrões de permissões
mcp__context7__get-library-docs "/spatie/laravel-permission" --topic "roles middleware" --tokens 2000
```

**Para UI/UX:**
```bash
# Componentes responsivos Tailwind
mcp__context7__get-library-docs "/tailwindlabs/tailwindcss.com" --topic "components utilities" --tokens 2000

# Interações Alpine.js
mcp__context7__get-library-docs "/alpinejs/alpine" --topic "directives events" --tokens 2000
```

### **2. Implementação de Features**

**Sistema de Inscrições:**
- Consultar form objects Livewire para organização
- Padrões de validação `#[Validate]` para campos
- Middleware de permissões para rotas administrativas

**Interface de Usuário:**
- Componentes Tailwind para cards de eventos
- Modais Alpine.js para confirmações
- Estados de loading com Livewire

### **3. Quality Checks & Testes**

**Validação de Padrões:**
```bash
# Verificar best practices Laravel
mcp__context7__get-library-docs "/context7/laravel_com-docs-12.x" --topic "testing" --tokens 2000

# Padrões de middleware
mcp__context7__get-library-docs "/spatie/laravel-permission" --topic "middleware" --tokens 1500
```


### **IDs de Biblioteca Confirmados:**
- ✅ Laravel 12: `/context7/laravel_com-docs-12.x`
- ✅ Livewire: `/context7/livewire_laravel_com-docs`
- ✅ Spatie Permission: `/spatie/laravel-permission`
- ✅ Alpine.js: `/alpinejs/alpine`
- ✅ Tailwind CSS: `/tailwindlabs/tailwindcss.com`


### **Tópicos Mais Úteis:**
- Laravel: `"validation"`, `"eloquent"`, `"testing"`, `"middleware"`
- Livewire: `"form validation"`, `"components"`, `"lifecycle"`
- Spatie Permission: `"roles middleware"`, `"blade directives"`
- Alpine.js: `"directives events"`, `"lifecycle"`
- Tailwind: `"components utilities"`, `"responsive design"`

### File Organization
```
8thBCSMIF/                           # Project root
├── app/                             # Application core (Laravel MVC)
│   ├── Console/
│   │   └── Commands/                # Custom Artisan commands for admin tasks
│   ├── Events/                      # Laravel events for system notifications
│   ├── Exceptions/                  # Custom exception classes for error handling
│   ├── Http/
│   │   ├── Controllers/             # Thin controllers following single responsibility
│   │   ├── Middleware/              # Custom middleware for request filtering
│   │   └── Requests/                # Form request validation classes
│   ├── Listeners/                   # Event listeners for automated responses
│   ├── Livewire/                    # Livewire components (TALL stack)
│   │   ├── Actions/                 # Livewire action components
│   │   ├── Admin/                   # Administrative interface components
│   │   └── Forms/                   # Interactive form components
│   ├── Mail/                        # Email notification classes
│   ├── Models/                      # Eloquent models for database entities
│   ├── Policies/                    # Authorization policy classes
│   ├── Providers/                   # Service providers for dependency injection
│   ├── Services/                    # Business logic services (single responsibility)
│   └── View/
│       └── Components/              # View component classes
├── bootstrap/                       # Laravel bootstrap files
│   ├── app.php
│   ├── cache/                       # Bootstrap cache files
│   └── providers.php
├── config/                          # Configuration files
│   ├── app.php                      # Application configuration
│   ├── auth.php                     # Authentication configuration
│   ├── countries.php                # Country list for registration forms
│   ├── database.php                 # Database connections
│   ├── fee_calculation.php          # Fee calculation business rules
│   ├── permission.php               # Spatie permission package settings
│   └── services.php                 # External service configurations (USP)
├── database/                        # Database schema and data
│   ├── factories/                   # Model factories for testing
│   ├── migrations/                  # Database schema migrations
│   ├── seeders/                     # Database seeders for initial data
│   └── testing/                     # Test database files
├── docs/                            # Project documentation
│   ├── adr/                         # Architecture Decision Records
│   ├── laravel_12/                  # Laravel 12 framework documentation
│   └── *.md                         # Project-specific documentation
├── lang/                            # Internationalization files
│   ├── en/                          # English translations
│   └── pt_BR/                       # Portuguese (Brazil) translations
├── resources/                       # Frontend assets and templates
│   ├── css/                         # Stylesheets (Tailwind CSS)
│   ├── images/                      # Static images and logos
│   │   ├── ime/                     # IME institutional logos
│   │   └── usp/                     # USP institutional logos
│   ├── js/                          # JavaScript files (Alpine.js)
│   └── views/                       # Blade templates
│       ├── admin/                   # Administrative interface views
│       ├── components/              # Reusable Blade components
│       ├── emails/                  # Email templates
│       ├── layouts/                 # Page layout templates
│       ├── livewire/                # Livewire component templates
│       └── mail/                    # Mail layout templates
├── routes/                          # Route definitions
│   ├── auth.php                     # Authentication routes
│   ├── console.php                  # Artisan command routes
│   └── web.php                      # Web application routes
├── scripts/                         # Python automation and AI integration
│   ├── llm_core/                    # Core LLM interaction modules
│   ├── tasks/                       # Specific LLM task implementations
│   └── *.py                         # Main automation scripts
├── storage/                         # File storage and caching
│   ├── app/                         # Application file storage
│   ├── framework/                   # Framework cache and session files
│   └── logs/                        # Application log files
├── tests/                           # Comprehensive test suite
│   ├── Browser/                     # Dusk browser/UI tests
│   ├── Fakes/                       # Test doubles for external services (USP)
│   ├── Feature/                     # Integration/feature tests
│   ├── Unit/                        # Unit tests for isolated components
│   ├── python/                      # Python script tests
│   └── fixtures/                    # Test data files
├── templates/                       # Template files for automation
│   ├── context_selectors/           # Context selection templates for LLM tasks
│   ├── issue_bodies/                # GitHub issue body templates
│   ├── meta-prompts/                # Meta-prompting templates for AI
│   └── prompts/                     # Standard prompt templates
├── context_llm/                     # LLM context generation and storage
│   ├── code/                        # Generated context snapshots
│   ├── common/                      # Shared context documentation
│   └── temp/                        # Temporary context files
├── llm_outputs/                     # Generated LLM task outputs
│   ├── analyze-ac/                  # Analysis outputs
│   ├── commit-mesage/               # Commit message generation
│   ├── review-issue/                # Issue review outputs
│   └── update-doc/                  # Documentation updates
├── planos/                          # Development planning documents
├── public/                          # Web server document root
│   ├── build/                       # Compiled assets (Vite)
│   └── js/                          # Public JavaScript files
└── vendor/                          # Composer dependencies (auto-generated)
```