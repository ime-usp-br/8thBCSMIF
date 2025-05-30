## Plano de Ação de Alto Nível: Implementação do Site de Inscrições 8th BCSMIF

Este plano assume que as funcionalidades de autenticação (login local, Senha Única USP) e verificação de e-mail do "Laravel 12 USP Starter Kit" estão **implementadas e funcionais**, e que os usuários **DEVEM** estar logados e com e-mail verificado para realizar uma inscrição.

**Fase 1: Fundação e Modelagem de Dados (Core do Evento e Inscrições)**

1.  **Definir/Refinar Modelo `Event`:**
    *   **Objetivo:** Armazenar informações sobre o evento principal (8th BCSMIF) e os workshops satélites.
    *   **Ações:**
        *   Criar/Confirmar Migration para a tabela `events` com campos como: `code` (PK, e.g., 'BCSMIF2025', 'RAA2025'), `name`, `description` (TEXT), `start_date`, `end_date`, `location`, `registration_deadline_early`, `registration_deadline_late`, `is_main_conference` (boolean).
        *   Criar Model `Event` com relacionamentos (e.g., `hasMany Registrations`).
        *   Criar Seeder para popular com os dados do 8th BCSMIF e os dois workshops.
2.  **Definir Tabela de Preços e Modelo `Fee` (ou Configuração):**
    *   **Objetivo:** Estruturar as regras de precificação.
    *   **Ações:**
        *   Criar Migration para tabela `fees` com campos como: `event_code` (FK), `participant_category` (enum/string: 'undergrad_student', 'grad_student', 'professor_abe', 'professor_non_abe', 'professional'), `fee_early`, `fee_late`, `fee_online_early`, `fee_online_late`, `is_discount_for_main_event_participant` (boolean, para workshops).
        *   Criar Model `Fee`.
        *   Criar Seeder para popular a tabela de preços conforme o documento "Proposta de Formulario".
3.  **Definir Modelo `Registration` e Relações:**
    *   **Objetivo:** Armazenar todos os dados das inscrições dos usuários.
    *   **Ações:**
        *   Criar Migration para a tabela `registrations` com:
            *   `user_id` (FK para `users`, **NÃO NULO**, pois login é pré-requisito).
            *   Campos para todos os dados do formulário (Informações Pessoais, Identificação, Contato, Profissionais, Participação no Evento, Restrições Alimentares, Contato de Emergência, Suporte a Visto).
            *   `registration_category` (string, para registrar a categoria no momento da inscrição).
            *   `calculated_fee` (decimal).
            *   `payment_status` (enum/string: 'pending_payment', 'pending_br_proof_approval', 'paid_br', 'invoice_sent_int', 'paid_int', 'free', 'cancelled').
            *   `payment_proof_path` (string, nullable).
            *   `payment_uploaded_at` (timestamp, nullable).
            *   Timestamps.
        *   Criar Model `Registration` com relacionamento `belongsTo User` e `belongsToMany Event` (para os eventos selecionados).
4.  **Tabela Pivot `registration_event`:**
    *   **Objetivo:** Associar inscrições a múltiplos eventos (8th BCSMIF e/ou workshops).
    *   **Ações:**
        *   Criar Migration para `registration_event` com `registration_id` e `event_code` (ou `event_id`), `price_at_registration` (decimal).

**Fase 2: Lógica de Backend (Core da Inscrição e Pagamento)**

1.  **Serviço de Cálculo de Taxas (`FeeCalculationService`):**
    *   **Objetivo:** Centralizar a lógica de cálculo de taxas.
    *   **Ações:**
        *   Implementar método que recebe dados do participante (categoria, eventos selecionados, data da inscrição) e retorna a taxa correta com base na tabela `fees`.
2.  **Processo de Inscrição (`RegistrationService` ou lógica no Controller/Livewire):**
    *   **Objetivo:** Orquestrar a criação de uma nova inscrição.
    *   **Ações:**
        *   Criar `RegistrationController` e/ou um Componente Livewire dedicado.
        *   Método `store`:
            *   Validação robusta dos dados do formulário (usar Form Request).
            *   Verificar se o usuário já possui inscrição para os mesmos eventos para evitar duplicidade (opcional, mas recomendado).
            *   Utilizar o `FeeCalculationService`.
            *   Salvar a `Registration` e suas associações com `Event` (via tabela pivot).
            *   Disparar notificação por email (ver item 3).
3.  **Notificações por Email (`Mailable` classes):**
    *   **Objetivo:** Informar o usuário e o coordenador sobre a inscrição e o upload de comprovante.
    *   **Ações:**
        *   Criar `NewRegistrationNotification` (para usuário e coordenador):
            *   Corpo do email com resumo da inscrição e instruções de pagamento (condicional BR/INT).
            *   Configurar `COORDINATOR_EMAIL` no `.env`.
        *   Criar `ProofUploadedNotification` (para coordenador, com cópia para o usuário):
            *   Notificar sobre o upload e incluir link para visualização (admin).
4.  **Lógica de Upload de Comprovante:**
    *   **Objetivo:** Permitir que o usuário anexe o comprovante após a inscrição.
    *   **Ações:**
        *   Criar método no `RegistrationController` (ou Livewire component na área do usuário) para lidar com o upload.
        *   Armazenamento seguro do arquivo (`storage/app/proofs/...`).
        *   Atualizar o `payment_proof_path`, `payment_uploaded_at` e `payment_status` na `Registration`.
        *   Disparar `ProofUploadedNotification`.

**Fase 3: Interface do Usuário (Frontend - TALL Stack)**

1.  **Páginas Públicas Informativas (Blade simples ou Livewire):**
    *   **Objetivo:** Apresentar o evento e informações cruciais.
    *   **Ações:**
        *   Criar views para: Home (descrição do evento), Workshops, Tabela de Preços, Informações de Pagamento.
        *   Popular com conteúdo de `descricao_evento.md` e "Proposta de Formulário".
2.  **Formulário de Inscrição (Componente Livewire/Volt):**
    *   **Objetivo:** Coletar todos os dados da inscrição.
    *   **Ações:**
        *   Desenvolver o componente `RegistrationForm` com todos os campos.
        *   Implementar lógica condicional para campos (CPF/RG vs. Passaporte, etc.).
        *   Integrar Date Pickers.
        *   Exibição dinâmica da taxa calculada.
        *   Validação frontend e backend.
3.  **Área do Usuário Logado:**
    *   **Objetivo:** Permitir que usuários gerenciem suas inscrições.
    *   **Ações:**
        *   Criar seção "Minhas Inscrições" no dashboard ou em página dedicada.
        *   Listar inscrições do usuário.
        *   Permitir visualização detalhada de uma inscrição.
        *   Formulário para upload de comprovante de pagamento para inscrições pendentes.
4.  **Layouts e Navegação:**
    *   Adaptar a navegação principal (`navigation.blade.php`) para incluir links para "Inscrição", "Sobre", "Workshops", "Taxas".
    *   Garantir consistência visual e responsividade com Tailwind CSS.

**Fase 4: Administração (Mínimo Viável)**

1.  **Listagem e Visualização de Inscrições:**
    *   **Objetivo:** Permitir que administradores vejam quem se inscreveu.
    *   **Ações:**
        *   Criar rotas e views de administração (protegidas por `role:admin`).
        *   Tabela para listar todas as inscrições com filtros básicos (evento, status pagamento).
        *   View para detalhes da inscrição, incluindo link para download do comprovante.
2.  **Atualização de Status de Pagamento:**
    *   **Objetivo:** Permitir que administradores confirmem pagamentos.
    *   **Ações:**
        *   Funcionalidade na view de detalhes da inscrição para atualizar o `payment_status`.

**Fase 5: Testes, Qualidade e Configuração**

1.  **Testes Automatizados:**
    *   **Objetivo:** Garantir a robustez da aplicação.
    *   **Ações:**
        *   Escrever Testes de Feature (PHPUnit) para todos os fluxos críticos de backend (criação de inscrição, cálculo de taxa, upload de comprovante, envio de emails).
        *   Escrever Testes de Browser (Dusk) para o formulário de inscrição e área do usuário (upload).
2.  **Qualidade de Código:**
    *   **Objetivo:** Manter o código limpo e padronizado.
    *   **Ações:** Executar `pint` e `phpstan analyse` regularmente.
3.  **Configuração e Conteúdo Final:**
    *   **Objetivo:** Preparar para o ambiente de produção.
    *   **Ações:**
        *   Configurar `.env` com todas as chaves necessárias (email, coordenador, etc.).
        *   Inserir todo o conteúdo final nas páginas informativas e templates de email.
        *   Popular as tabelas `events` e `fees` com os dados finais via Seeders.
        *   Realizar uma revisão completa de segurança e usabilidade.

**Notas Importantes:**

*   **Iteração:** Este é um plano de alto nível. Cada fase e item pode ser quebrado em Issues menores e implementado iterativamente.
*   **Starter Kit:** Aproveitar ao máximo os componentes e a estrutura do "Laravel 12 USP Starter Kit" (autenticação, layouts, componentes Blade/Livewire, ferramentas de dev).
*   **Segurança:** Dar atenção especial à validação de dados e ao armazenamento seguro de arquivos.
*   **Localização:** o idioma sera o ingles.

