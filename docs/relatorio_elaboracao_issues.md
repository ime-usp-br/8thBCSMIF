
### Relatório Detalhado de Gerenciamento de Projeto e Elaboração de Issues para o Site de Inscrições 8th BCSMIF

**Versão:** 1.0.0
**Data:** 2025-05-30
**Autor:** LLM-Assisted Project Planner

**1. Introdução**

Este documento detalha a estratégia recomendada para a elaboração e ordenação de Issues no GitHub para o desenvolvimento do site de inscrições do evento "8th BCSMIF". A abordagem é baseada nos princípios de desenvolvimento ágil, rastreabilidade e qualidade de código definidos no `docs/guia_de_desenvolvimento.md` e `docs/padroes_codigo_boas_praticas.md` do projeto "Laravel 12 USP Starter Kit".

O plano de implementação principal para o site de inscrições do 8th BCSMIF está delineado em `docs/plano_inscricao_8thBCSMIF.md`. Este relatório visa transformar os itens de alto nível desse plano em Issues do GitHub acionáveis, sugerindo uma ordem lógica para sua execução.

**2. Princípios Gerais para Gerenciamento e Elaboração de Issues**

Antes de detalhar as Issues específicas, é crucial reiterar os princípios do `guia_de_desenvolvimento.md` que **DEVEM** ser seguidos:

*   **Issues Atômicas:** Cada tarefa decomposta **DEVE** ser uma Issue distinta no GitHub.
*   **Templates de Issue:** Utilizar os templates fornecidos em `templates/issue_bodies/` (feature, chore, bug, test, etc.) para garantir consistência.
*   **Critérios de Aceite (ACs):** Toda Issue **DEVE** ter Critérios de Aceite claros, S.M.A.R.T. e verificáveis.
*   **Kanban (GitHub Projects):** As Issues **DEVEM** ser gerenciadas no quadro Kanban do projeto para visualização do fluxo (`Backlog` > `A Fazer` > `Em Progresso` > `Concluído`).
*   **Branching e Commits:**
    *   Criar um branch para cada Issue (ex: `feature/XX-descricao-curta`).
    *   Fazer commits atômicos, frequentes e vinculados à Issue (`#XX`).
    *   Utilizar o padrão Conventional Commits.
*   **Pull Requests (PRs):**
    *   Abrir PRs para cada Issue concluída, vinculando-a (`Closes #XX`).
    *   PRs **DEVEM** passar na CI (testes, Pint, PHPStan).
    *   Auto-revisão ou revisão por pares é **RECOMENDÁVEL**.
*   **Qualidade de Código:**
    *   Formatação com Laravel Pint (`./vendor/bin/pint`).
    *   Análise estática com Larastan (`./vendor/bin/phpstan analyse`).
*   **Testes Automatizados:**
    *   PHPUnit para testes unitários e de feature.
    *   Laravel Dusk para testes de browser End-to-End, especialmente para UI.
    *   Criar sub-issues de teste (`TYPE: test`) para funcionalidades complexas ou com UI significativa, seguindo o modelo de `plano_sub_issue_10_20.txt`.
*   **Documentação:** Considerar a atualização da documentação (`README.md`, Wiki) como parte dos ACs de features relevantes.

**3. Estratégia de Elaboração Detalhada de Issues**

*   **`TITLE:`** Descritivo e conciso, prefixado com o módulo/fase e o tipo. Ex: `[BCSMIF][Fase1.1] Implementar Model, Migration e Seeder para Events`
*   **`TYPE:`** `feature` (para novas funcionalidades), `chore` (para tarefas de configuração/manutenção), `ui` (para desenvolvimento de interface), `test` (para criação de testes), `bug`, `refactor`.
*   **`LABELS:`** Relevantes para o projeto e a tarefa (ex: `bcsmif`, `fase-1`, `database`, `eloquent`, `livewire`, `tailwind`, `todo`, `auth`, `test`, `phpunit`, `dusk`, `ci`).
*   **`ASSIGNEE:`** `@me` ou o responsável pela tarefa.
*   **`PROJECT:`** `8thBCSMIF` (ou o nome do Projeto GitHub correspondente, se criado).
*   **`MILESTONE:`** (Opcional) Ex: `Fase 1: Modelagem`, `Fase 2: Backend Core`.
*   **`PARENT_ISSUE:`** (Se aplicável) Para sub-tasks ou issues de teste.
*   **`FEATURE_MOTIVATION` (ou `CHORE_MOTIVATION`, etc.):** Justificativa da Issue, o "porquê".
*   **`FEATURE_DESCRIPTION` (ou `CHORE_DESCRIPTION`, etc.):** Descrição detalhada da tarefa, o "o quê".
*   **`PROPOSED_SOLUTION` (ou `TECHNICAL_DETAILS`, etc.):** Detalhes técnicos da implementação, o "como". Referenciar tecnologias (TALL stack, Livewire/Volt), padrões do projeto, e como interage com outros componentes.
*   **`ACCEPTANCE_CRITERIA:`** Lista detalhada de Critérios de Aceite com checkboxes (`- [ ]`). **DEVEM** incluir:
    *   Funcionalidade implementada conforme descrição.
    *   Adesão aos padrões de código (`pint`, `phpstan`).
    *   Criação/atualização de testes automatizados (PHPUnit, Dusk).
    *   Atualização de documentação, se aplicável.
    *   Passagem na CI.

**4. Ordem Sugerida de Implementação das Issues (Baseado em `docs/plano_inscricao_8thBCSMIF.md`)**

A seguinte ordem é sugerida, respeitando as dependências entre as fases e componentes. Issues dentro de um mesmo subitem podem, em alguns casos, ser paralelizadas se os recursos permitirem.

**Fase 0: Configuração Inicial do Projeto 8thBCSMIF (Chores)**
*   **Issue 0.1: Configurar Aplicação Laravel para o Evento 8thBCSMIF**
    *   `TYPE: chore`
    *   `LABELS: bcsmif, setup, config, todo`
    *   `CHORE_MOTIVATION:` Preparar o ambiente Laravel do Starter Kit para o projeto específico do 8th BCSMIF.
    *   `CHORE_DESCRIPTION:` Atualizar nome da aplicação, URLs, locales, e outras configurações básicas no `.env` e `config/app.php`. Limpar/adaptar rotas e views de exemplo do Breeze, se necessário. Configurar banco de dados.
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Arquivo .env configurado com APP_NAME="8thBCSMIF", APP_URL, DB_CONNECTION para MySQL (ou outro de produção).`
        *   `[ ] config/app.php atualizado (name, locale='pt_BR', fallback_locale='pt_BR', timezone='America/Sao_Paulo').`
        *   `[ ] Rotas/views de exemplo desnecessárias do Breeze removidas ou comentadas.`
        *   `[ ] `php artisan migrate` executa com sucesso no banco de dados configurado.`
        *   `[ ] Documentação básica do projeto (README.md) atualizada com informações do 8th BCSMIF.`

**Fase 1: Fundação e Modelagem de Dados (Core do Evento e Inscrições)**

*   **Issue 1.1: Implementar Model, Migration e Seeder para Events** (Ver Exemplo Detalhado na Seção 5)
*   **Issue 1.2: Implementar Model, Migration e Seeder para Fees (Tabela de Preços)**
    *   `TYPE: feature`
    *   `LABELS: bcsmif, fase-1, database, eloquent, migration, seeder, todo`
    *   `FEATURE_MOTIVATION:` Estruturar as regras de precificação do evento principal e workshops, considerando diferentes categorias de participantes e prazos.
    *   `FEATURE_DESCRIPTION:` Criar a migration para a tabela `fees`, o Model `App\Models\Fee` e um Seeder para popular os dados conforme a tabela de preços em `Proposta de Formulario.md`.
    *   `PROPOSED_SOLUTION:`
        *   Migration: tabela `fees` com campos `event_code` (FK para `events.code`), `participant_category` (string/enum), `fee_early` (decimal), `fee_late` (decimal), `fee_online` (decimal, ou variações como `fee_online_early`/`late`), `is_discount_for_main_event_participant` (boolean).
        *   Model `Fee` com `$fillable` e `$casts`.
        *   Seeder `FeesTableSeeder` para inserir todos os cenários de preço.
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Migration para tabela 'fees' criada e funcional.`
        *   `[ ] Model App\\Models\\Fee criado com $fillable e $casts.`
        *   `[ ] Seeder FeesTableSeeder criado e popula a tabela 'fees' com todos os valores e categorias conforme documento.`
        *   `[ ] Seeder é chamado em DatabaseSeeder.`
        *   `[ ] `php artisan migrate --seed` executa sem erros.`
        *   `[ ] Código PHP segue padrões PSR-12 (Pint) e passa no PHPStan.`
*   **Issue 1.3: Implementar Model e Migration para Registrations**
    *   `TYPE: feature`
    *   `LABELS: bcsmif, fase-1, database, eloquent, migration, todo`
    *   `FEATURE_MOTIVATION:` Armazenar dados de inscrição dos participantes.
    *   `FEATURE_DESCRIPTION:` Criar a migration para a tabela `registrations` contendo todos os campos detalhados no "Proposta de Formulario.md" e no `plano_inscricao_8thBCSMIF.md` (user_id, dados pessoais, identificação, contato, profissionais, participação, restrições alimentares, contato de emergência, suporte a visto, categoria da inscrição, taxa calculada, status de pagamento, caminho do comprovante, etc.). Criar o Model `App\Models\Registration` com `fillable`, `casts`, e relacionamento `belongsTo User`.
    *   `PROPOSED_SOLUTION:` Gerar migration com `php artisan make:migration create_registrations_table --create=registrations`. Definir colunas com tipos apropriados (strings, text, boolean, decimal, timestamp, foreignId). Model `Registration` com `use HasFactory;`.
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Migration create_registrations_table criada e funcional.`
        *   `[ ] Tabela 'registrations' criada no banco com todas as colunas e tipos corretos.`
        *   `[ ] Model App\\Models\\Registration criado com propriedades $fillable e $casts adequadas.`
        *   `[ ] Relacionamento $this->belongsTo(User::class) definido no Model Registration.`
        *   `[ ] Código PHP segue padrões PSR-12 (Pint) e passa no PHPStan.`
*   **Issue 1.4: Implementar Tabela Pivot `registration_event` e Relacionamentos Many-to-Many**
    *   `TYPE: feature`
    *   `LABELS: bcsmif, fase-1, database, eloquent, migration, todo`
    *   `FEATURE_MOTIVATION:` Permitir que uma inscrição possa estar associada a múltiplos eventos (ex: conferência principal + um ou mais workshops).
    *   `FEATURE_DESCRIPTION:` Criar a migration para a tabela pivot `event_registration` (ou `registration_event` seguindo convenção alfabética) com as colunas `event_code` (FK para `events.code`) e `registration_id` (FK para `registrations.id`), e `price_at_registration` (decimal). Definir os relacionamentos `belongsToMany` nos Models `App\Models\Event` e `App\Models\Registration`.
    *   `PROPOSED_SOLUTION:` Gerar migration com `php artisan make:migration create_event_registration_table`. Definir chaves primárias compostas e estrangeiras. Adicionar métodos de relacionamento (ex: `events()` no Model `Registration`, `registrations()` no Model `Event`) com `withPivot('price_at_registration')`.
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Migration para tabela pivot 'event_registration' criada e funcional.`
        *   `[ ] Relacionamento belongsToMany definido corretamente no Model Event.`
        *   `[ ] Relacionamento belongsToMany definido corretamente no Model Registration.`
        *   `[ ] Campo 'price_at_registration' acessível via pivot.`
        *   `[ ] Código PHP segue padrões PSR-12 (Pint) e passa no PHPStan.`

**Fase 2: Lógica de Backend (Core da Inscrição e Pagamento)**

*   **Issue 2.1: Implementar `FeeCalculationService`**
    *   `TYPE: feature`
    *   `LABELS: bcsmif, fase-2, backend, service, business-logic, todo`
    *   `FEATURE_MOTIVATION:` Centralizar a lógica de cálculo de taxas de inscrição, considerando categoria do participante, eventos selecionados, datas (early/late) e descontos.
    *   `FEATURE_DESCRIPTION:` Criar a classe `App\Services\FeeCalculationService` com um método principal (ex: `calculateTotalFee(User $user, array $eventCodes, Carbon $registrationDate)`). Este método consultará os Models `Fee` e `Event` para determinar a taxa correta.
    *   `PROPOSED_SOLUTION:` O serviço deve lidar com:
        *   Identificar a categoria do participante (via `User` model ou dados do formulário).
        *   Obter as taxas base para cada evento selecionado.
        *   Aplicar lógica de "early bird" vs. "late" com base na `registrationDate` e nos `registration_deadline_early/late` do evento.
        *   Aplicar descontos para participantes do evento principal em workshops (se `is_discount_for_main_event_participant` for true na `Fee`).
        *   Considerar o formato de participação (in-person/online) se os preços variarem.
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Classe App\\Services\\FeeCalculationService criada.`
        *   `[ ] Método principal calcula corretamente a taxa total para diferentes cenários (categorias, prazos, eventos combinados, descontos).`
        *   `[ ] Testes unitários (PHPUnit) para FeeCalculationService cobrem todos os cenários de cálculo, incluindo casos de borda e descontos.`
        *   `[ ] Código PHP segue padrões PSR-12 (Pint) e passa no PHPStan.`
*   **Issue 2.2: Implementar Lógica de Criação de Inscrição (Controller/Service)**
    *   `TYPE: feature`
    *   `LABELS: bcsmif, fase-2, backend, controller, livewire, validation, todo`
    *   `FEATURE_MOTIVATION:` Orquestrar o processo de salvar uma nova inscrição, desde a validação dos dados do formulário até o disparo de notificações.
    *   `FEATURE_DESCRIPTION:` Criar um `RegistrationController` (ou um Service `RegistrationService` chamado por um componente Livewire). O método `store` (ou equivalente) será responsável por: validar os dados, verificar duplicidade (opcional), usar o `FeeCalculationService`, salvar a `Registration` e suas associações com `Event` (via `sync()` na relação `belongsToMany`), e disparar a notificação por email.
    *   `PROPOSED_SOLUTION:` Criar FormRequest `StoreRegistrationRequest` para validação robusta. No controller/service, obter usuário logado, chamar `FeeCalculationService`, criar `Registration` com `user_id`, `calculated_fee`, `registration_category`, `payment_status = 'pending_payment'`, e demais dados do formulário. Associar eventos via `$registration->events()->sync($eventDataWithPrices)`. Disparar `NewRegistrationNotification`.
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] StoreRegistrationRequest criado com todas as regras de validação necessárias para os campos do formulário.`
        *   `[ ] Controller/Service de Registro implementado com método 'store'.`
        *   `[ ] Dados da inscrição são validados usando o FormRequest.`
        *   `[ ] FeeCalculationService é utilizado para determinar 'calculated_fee'.`
        *   `[ ] Nova Registration é salva no banco de dados com todos os campos corretos.`
        *   `[ ] Eventos selecionados são corretamente associados à inscrição na tabela pivot 'event_registration' com 'price_at_registration'.`
        *   `[ ] Notificação por email NewRegistrationNotification é disparada para o usuário e coordenador.`
        *   `[ ] Testes de Feature (PHPUnit) para o fluxo de criação de inscrição (incluindo validação e disparo de email mockado).`
        *   `[ ] Código PHP segue padrões PSR-12 (Pint) e passa no PHPStan.`
*   **Issue 2.3: Implementar Notificações por Email para Inscrição**
    *   `TYPE: feature`
    *   `LABELS: bcsmif, fase-2, backend, mail, notification, todo`
    *   `FEATURE_MOTIVATION:` Manter usuários e coordenadores informados sobre o status das inscrições e uploads de comprovantes.
    *   `FEATURE_DESCRIPTION:` Criar classes Mailable para:
        *   `NewRegistrationNotification`: Enviada ao usuário e coordenador após uma nova inscrição, com resumo e instruções de pagamento (condicionais para BR/INT).
        *   `ProofUploadedNotification`: Enviada ao coordenador (e opcionalmente ao usuário) quando um comprovante é anexado.
    *   `PROPOSED_SOLUTION:` Gerar Mailables com `php artisan make:mail`. Utilizar Markdown Mailables. Configurar o email do coordenador via `.env` (`COORDINATOR_EMAIL`) e `config/mail.php` ou um novo config.
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Mailable NewRegistrationNotification criado e envia email para usuário e coordenador.`
        *   `[ ] Conteúdo do email NewRegistrationNotification inclui resumo da inscrição e instruções de pagamento corretas (condicional BR/INT).`
        *   `[ ] Mailable ProofUploadedNotification criado e envia email para o coordenador.`
        *   `[ ] Conteúdo do email ProofUploadedNotification inclui link para admin visualizar o comprovante.`
        *   `[ ] Testes (PHPUnit com Mail::fake()) verificam o envio e conteúdo dos emails.`
        *   `[ ] Código PHP segue padrões PSR-12 (Pint) e passa no PHPStan.`
*   **Issue 2.4: Implementar Lógica de Upload de Comprovante de Pagamento**
    *   `TYPE: feature`
    *   `LABELS: bcsmif, fase-2, backend, file-upload, storage, todo`
    *   `FEATURE_MOTIVATION:` Permitir que participantes brasileiros anexem seus comprovantes de pagamento.
    *   `FEATURE_DESCRIPTION:` Implementar a funcionalidade de upload de arquivo para o comprovante de pagamento. O arquivo deve ser armazenado de forma segura e associado à inscrição correspondente. O status da inscrição deve ser atualizado.
    *   `PROPOSED_SOLUTION:` Criar um método em um Controller (ex: `UserProfileController` ou `RegistrationController`) ou em um componente Livewire na área do usuário. Utilizar o `Storage` facade do Laravel para salvar o arquivo em `storage/app/proofs/{registration_id}/` (ou similar, não público). Validar o tipo e tamanho do arquivo. Atualizar os campos `payment_proof_path`, `payment_uploaded_at` e `payment_status` (ex: para `'pending_br_proof_approval'`) na `Registration`. Disparar `ProofUploadedNotification`.
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Método/Ação para upload de comprovante implementado.`
        *   `[ ] Arquivo de comprovante é validado (ex: PDF, JPG, PNG, tamanho máximo).`
        *   `[ ] Arquivo é armazenado de forma segura no disco (ex: 'local' ou 'private').`
        *   `[ ] Caminho do arquivo, data de upload e status de pagamento são atualizados na tabela 'registrations'.`
        *   `[ ] Notificação ProofUploadedNotification é disparada.`
        *   `[ ] Testes de Feature (PHPUnit com Storage::fake() e UploadedFile::fake()) para o upload.`
        *   `[ ] Código PHP segue padrões PSR-12 (Pint) e passa no PHPStan.`

**Fase 3: Interface do Usuário (Frontend - TALL Stack)**

*   **Issue 3.1: Criar Páginas Públicas Informativas Estáticas** (Conforme detalhado na Seção 4 do plano original)
*   **Issue 3.2: Desenvolver Componente Livewire/Volt para Formulário de Inscrição**
    *   `TYPE: ui`
    *   `LABELS: bcsmif, fase-3, frontend, livewire, volt, tailwind, form, validation, todo`
    *   `FEATURE_MOTIVATION:` Permitir que usuários (logados e verificados) preencham e submetam o formulário de inscrição.
    *   `FEATURE_DESCRIPTION:` Desenvolver o componente Livewire/Volt `RegistrationForm` (ou similar) que renderize todos os campos do formulário conforme `Proposta de Formulario.md`. Implementar lógica condicional (ex: CPF/RG vs. Passaporte), integração com date pickers (se Alpine.js ou JS customizado), e exibição dinâmica da taxa calculada (chamando `FeeCalculationService` via backend). Realizar validação frontend (HTML5, Alpine.js se aplicável) e submeter os dados para a ação de backend (Issue 2.2).
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Componente Livewire/Volt RegistrationForm criado e renderiza todos os campos do formulário.`
        *   `[ ] Lógica condicional para campos (CPF/RG, Passaporte, Suporte a Visto) funciona corretamente.`
        *   `[ ] Campos de data (Nascimento, Passaporte, Chegada, Partida) usam date pickers ou input type="date".`
        *   `[ ] A taxa de inscrição é exibida dinamicamente (ou após seleção de eventos/categoria).`
        *   `[ ] Validação de campos obrigatórios e formatos é realizada no frontend (informativa) e backend (definitiva).`
        *   `[ ] Componente submete os dados para o método 'store' do Controller/Service de Registro.`
        *   `[ ] Componente é responsivo e estilizado com Tailwind CSS.`
*   **Issue 3.2.T: Implementar Testes Dusk para o Formulário de Inscrição**
    *   `TYPE: test`
    *   `LABELS: bcsmif, fase-3, test, dusk, frontend, todo`
    *   `PARENT_ISSUE:` (ID da Issue 3.2)
    *   `TEST_MOTIVATION:` Garantir a funcionalidade completa do formulário de inscrição, incluindo validações, condicionais e submissão.
    *   `TEST_SCOPE:` Cobrir cenários de preenchimento com dados válidos e inválidos, seleção de diferentes opções de eventos e categorias, e verificar a resposta do backend (redirecionamento, mensagens de erro/sucesso).
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Teste Dusk para submissão bem-sucedida com dados válidos.`
        *   `[ ] Testes Dusk para validação de campos obrigatórios e formatos incorretos.`
        *   `[ ] Testes Dusk verificam a lógica de exibição condicional de campos.`
        *   `[ ] Testes Dusk verificam a correta interação com seletores de evento/categoria e a exibição da taxa.`
*   **Issue 3.3: Desenvolver Área do Usuário Logado (Minhas Inscrições, Upload de Comprovante)**
    *   `TYPE: ui`
    *   `LABELS: bcsmif, fase-3, frontend, livewire, volt, tailwind, user-dashboard, todo`
    *   `FEATURE_MOTIVATION:` Permitir que usuários gerenciem suas inscrições e realizem o upload de comprovantes.
    *   `FEATURE_DESCRIPTION:` Criar uma seção "Minhas Inscrições" (ex: `/dashboard/my-registrations`). Listar as inscrições do usuário logado, com status e taxa. Permitir visualizar detalhes de cada inscrição. Para inscrições com `payment_status = 'pending_payment'` (e usuário brasileiro), exibir um formulário para upload do comprovante de pagamento que interaja com a lógica de backend (Issue 2.4).
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Página/Componente "Minhas Inscrições" lista corretamente as inscrições do usuário autenticado.`
        *   `[ ] Detalhes da inscrição (eventos, taxa, status) são exibidos corretamente.`
        *   `[ ] Formulário de upload de comprovante é exibido condicionalmente para inscrições pendentes de pagamento (BR).`
        *   `[ ] Upload de comprovante funciona, atualiza o status e exibe feedback ao usuário.`
        *   `[ ] Interface é responsiva e estilizada com Tailwind CSS.`
*   **Issue 3.3.T: Implementar Testes Dusk para a Área do Usuário (Upload de Comprovante)**
    *   `TYPE: test`
    *   `LABELS: bcsmif, fase-3, test, dusk, frontend, todo`
    *   `PARENT_ISSUE:` (ID da Issue 3.3)
    *   `TEST_MOTIVATION:` Validar a listagem de inscrições e o fluxo de upload de comprovante na área do usuário.
    *   `TEST_SCOPE:` Testar a visualização de inscrições, a condicionalidade do formulário de upload e o processo de upload de um arquivo de teste.
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Teste Dusk verifica se as inscrições do usuário são listadas corretamente.`
        *   `[ ] Teste Dusk verifica se o formulário de upload aparece/desaparece conforme o status da inscrição.`
        *   `[ ] Teste Dusk simula o upload de um arquivo de comprovante e verifica a atualização do status/feedback.`
*   **Issue 3.4: Adaptar Layouts e Navegação Principal**
    *   `TYPE: ui`
    *   `LABELS: bcsmif, fase-3, frontend, layout, navigation, tailwind, todo`
    *   `FEATURE_MOTIVATION:` Integrar as novas seções do site à navegação principal da aplicação.
    *   `FEATURE_DESCRIPTION:` Modificar o componente `resources/views/livewire/layout/navigation.blade.php` (ou similar) para incluir links para as novas páginas públicas (Home, Workshops, Taxas, Informações de Pagamento) e para a área de "Inscrição" (que levará ao formulário de inscrição ou à área "Minhas Inscrições" dependendo do status do usuário).
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Links para "Home", "Workshops", "Taxas", "Pagamento" e "Inscrever-se" (ou "Minhas Inscrições") adicionados à navegação principal.`
        *   `[ ] Navegação é responsiva e funciona corretamente em diferentes dispositivos.`
        *   `[ ] Links são exibidos/ocultados condicionalmente (ex: "Minhas Inscrições" apenas para logados).`

**Fase 4: Administração (Mínimo Viável)**

*   **Issue 4.1: Implementar Listagem e Visualização de Inscrições para Admin**
    *   `TYPE: feature`
    *   `LABELS: bcsmif, fase-4, backend, admin, ui, livewire, todo, roles`
    *   `FEATURE_MOTIVATION:` Permitir que administradores visualizem e gerenciem as inscrições recebidas.
    *   `FEATURE_DESCRIPTION:` Criar rotas protegidas pelo role `admin` (guard `web`). Desenvolver uma interface (provavelmente Livewire/Volt) para listar todas as inscrições, com filtros básicos (por evento, status de pagamento). Permitir a visualização detalhada de cada inscrição, incluindo acesso ao download do comprovante de pagamento (se houver).
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Rotas de administração para inscrições protegidas por middleware (ex: `auth` e `role:admin`).`
        *   `[ ] Interface de listagem de inscrições exibe informações chave (participante, evento(s), status, taxa).`
        *   `[ ] Filtros básicos (ex: por evento, status) funcionam corretamente.`
        *   `[ ] View de detalhes da inscrição exibe todos os dados do formulário e permite download do comprovante.`
        *   `[ ] Interface é responsiva e funcional.`
*   **Issue 4.2: Implementar Atualização de Status de Pagamento para Admin**
    *   `TYPE: feature`
    *   `LABELS: bcsmif, fase-4, backend, admin, ui, livewire, todo, payment`
    *   `FEATURE_MOTIVATION:` Permitir que administradores confirmem pagamentos e atualizem o status das inscrições.
    *   `FEATURE_DESCRIPTION:` Na view de detalhes da inscrição (admin), adicionar funcionalidade (ex: botões, dropdown) para que o administrador possa alterar o `payment_status` de uma inscrição (ex: de `'pending_br_proof_approval'` para `'paid_br'`, ou de `'pending_payment'` para `'free'` ou `'cancelled'`).
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Administrador pode alterar o 'payment_status' de uma inscrição na interface de detalhes.`
        *   `[ ] Alterações de status são refletidas corretamente no banco de dados e na interface.`
        *   `[ ] (Opcional) Disparar notificação ao usuário sobre a mudança de status de pagamento.`

**Fase 5: Testes, Qualidade e Configuração Final**

*   **Issue 5.1: Escrever Testes de Feature (PHPUnit) para Fluxos de Backend** (Conforme detalhado na Seção 4 do plano original)
*   **Issue 5.2: Configurar CI para Testes Dusk do Site de Inscrições** (Se ainda não abrangido pela CI geral do Starter Kit)
    *   `TYPE: chore`
    *   `LABELS: bcsmif, fase-5, ci, dusk, devops, todo`
    *   `CHORE_MOTIVATION:` Garantir que os testes de browser para o site de inscrições sejam executados automaticamente no pipeline de CI.
    *   `CHORE_DESCRIPTION:` Revisar e, se necessário, ajustar o workflow `.github/workflows/laravel.yml` para incluir a execução dos testes Dusk específicos para o 8th BCSMIF. Isso pode envolver a configuração de um banco de dados de teste Dusk dedicado, variáveis de ambiente e a inicialização correta do ChromeDriver e do servidor da aplicação no ambiente de CI.
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] Workflow de CI executa `php artisan dusk --group=bcsmif` (ou tag apropriada) com sucesso.`
        *   `[ ] Artefatos de falha Dusk (screenshots, console logs) são salvos e acessíveis na CI.`
*   **Issue 5.3: Configurações Finais e Conteúdo para Produção**
    *   `TYPE: chore`
    *   `LABELS: bcsmif, fase-5, config, content, deployment, todo`
    *   `CHORE_MOTIVATION:` Preparar a aplicação para o ambiente de produção.
    *   `CHORE_DESCRIPTION:` Configurar todas as variáveis de ambiente necessárias no `.env.example` e no ambiente de produção (email, `COORDINATOR_EMAIL`, chaves de API se houver). Inserir todo o conteúdo final nas páginas informativas e templates de email. Executar os seeders `EventsTableSeeder` e `FeesTableSeeder` para popular com os dados finais. Realizar uma revisão completa de segurança (permissões de arquivo, XSS, SQLi, etc.) e usabilidade.
    *   `ACCEPTANCE_CRITERIA:`
        *   `[ ] `.env.example` está completo e documentado para produção.`
        *   `[ ] Conteúdo final inserido em todas as páginas e emails.`
        *   `[ ] Seeders de Events e Fees executados com dados de produção.`
        *   `[ ] Aplicação passa em uma checklist de segurança básica.`
        *   `[ ] Revisão final de usabilidade realizada.`

**5. Exemplo Detalhado de Elaboração de Issue (Baseado na Fase 1.1 do Plano)**

**`TITLE:`** `[BCSMIF][Fase1.1] Implementar Model, Migration e Seeder para Events`
**`TYPE:`** `feature`
**`LABELS:`** `bcsmif, fase-1, database, eloquent, migration, seeder, todo`
**`ASSIGNEE:`** `@me`
**`PROJECT:`** `8thBCSMIF`
**`MILESTONE:`** `Fase 1: Modelagem e Dados Core`

**`FEATURE_MOTIVATION:`**
Armazenar informações detalhadas sobre o evento principal (8th BCSMIF) e os workshops satélites (Risk Analysis and Applications, Dependence Analysis) é fundamental para a gestão do site de inscrições, cálculo de taxas e exibição de informações aos usuários.

**`FEATURE_DESCRIPTION:`**
Esta tarefa envolve a criação da estrutura de banco de dados e dos dados iniciais para a entidade "Event". Isso inclui:
1.  Criar uma migration para a tabela `events`.
2.  Definir o Model Eloquent `App\Models\Event` com seus atributos, fillable, casts e relacionamentos futuros (ex: com `Registration`).
3.  Criar um Seeder (`EventsTableSeeder`) para popular a tabela `events` com os dados do 8th BCSMIF e dos dois workshops, conforme as informações disponíveis em `descricao_evento.md`.

**`PROPOSED_SOLUTION:`**
*   **Migration (`create_events_table`):**
    *   Gerar com `php artisan make:migration create_events_table --create=events`.
    *   Campos a incluir:
        *   `id` (PK, bigIncrements)
        *   `code` (string, unique, ex: 'BCSMIF2025', 'RAA2025', 'WDA2025') - Chave primária lógica para referências.
        *   `name` (string) - Nome completo do evento/workshop.
        *   `description` (text, nullable) - Descrição detalhada.
        *   `start_date` (date)
        *   `end_date` (date)
        *   `location` (string) - Local de realização.
        *   `registration_deadline_early` (date, nullable) - Data limite para inscrição com desconto "early bird".
        *   `registration_deadline_late` (date, nullable) - Data limite final para inscrição.
        *   `is_main_conference` (boolean, default: false) - Indica se é o evento principal.
        *   `timestamps`
*   **Model (`App\Models\Event`):**
    *   Gerar com `php artisan make:model Event`.
    *   Definir `$fillable` para os campos da tabela.
    *   Definir `$casts` para `start_date`, `end_date`, `registration_deadline_early`, `registration_deadline_late` como `'date'` e `is_main_conference` como `'boolean'`.
    *   (Futuro) Adicionar relacionamento `public function registrations(): HasMany { return $this->hasMany(Registration::class, 'event_code', 'code'); }` (ou via tabela pivot se um registro puder ter múltiplos eventos). Por ora, podemos omitir se Registration ainda não existe.
*   **Seeder (`EventsTableSeeder`):**
    *   Gerar com `php artisan make:seeder EventsTableSeeder`.
    *   No método `run()`, usar `Event::updateOrCreate(['code' => '...'], [ ...dados... ])` para popular:
        *   8th BCSMIF (Maresias, 28/Set a 03/Out/2025, `is_main_conference` = true)
        *   Risk Analysis and Applications (IME-USP, 24+25/Set/2025)
        *   Dependence Analysis (IMECC-UNICAMP, 26+27/Set/2025)
    *   Chamar `EventsTableSeeder` a partir de `DatabaseSeeder`.

**`ACCEPTANCE_CRITERIA:`**
- `[ ] Migration `create_events_table` existe e define corretamente todos os campos especificados (code, name, description, start_date, end_date, location, registration_deadline_early, registration_deadline_late, is_main_conference) com tipos de dados apropriados e constraints (unique para code).`
- `[ ] Model `App\Models\Event` existe, estende `Illuminate\Database\Eloquent\Model`, e possui as propriedades `$fillable` e `$casts` corretamente definidas para todos os campos da migration.`
- `[ ] Seeder `EventsTableSeeder` existe e, quando executado, popula a tabela `events` com os dados corretos para o 8th BCSMIF, o workshop de Risk Analysis e o workshop de Dependence Analysis, incluindo nomes, datas, locais e o status `is_main_conference`.`
- `[ ] O `EventsTableSeeder` é chamado corretamente dentro do método `run()` do `DatabaseSeeder.php`.`
- `[ ] O comando `php artisan migrate --seed` executa sem erros e a tabela `events` é criada e populada corretamente no banco de dados.`
- `[ ] O código PHP desenvolvido (Migration, Model, Seeder) segue estritamente os padrões PSR-12 (verificado com `./vendor/bin/pint`).`
- `[ ] O código PHP desenvolvido passa na análise estática do Larastan (`./vendor/bin/phpstan analyse` no nível configurado).`
- `[ ] (Opcional, mas recomendado) Testes unitários básicos são criados para o Model `Event` para verificar casts ou qualquer lógica customizada futura (se houver).`

**6. Considerações Adicionais**

*   **Revisão de Issues:** É **RECOMENDÁVEL** que as Issues, especialmente as de `feature` com UI, sejam revisadas por outro membro da equipe (ou pelo próprio dev após um tempo) antes de iniciar o desenvolvimento, para garantir clareza e completude dos ACs. O script `scripts/llm_interact.py review-issue -i XX` pode auxiliar.
*   **Flexibilidade:** Este é um plano sugerido. A ordem exata e a granularidade das Issues podem ser ajustadas conforme o progresso e as prioridades do projeto.
*   **Comunicação:** Manter o quadro Kanban e as Issues atualizadas é fundamental para a comunicação do progresso.

**7. Conclusão**

A adoção desta estratégia de elaboração e gerenciamento de Issues, combinada com as ferramentas e padrões do "Laravel 12 USP Starter Kit", fornecerá uma base sólida para o desenvolvimento eficiente, rastreável e de alta qualidade do site de inscrições do 8th BCSMIF.
