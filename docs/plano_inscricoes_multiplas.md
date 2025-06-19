### **Plano de Ação de Alto Nível: Modificação de Inscrições e Pagamentos Múltiplos**

Este plano detalha as etapas necessárias para implementar a funcionalidade que permite aos participantes modificar uma inscrição existente, adicionando novos eventos/workshops e gerenciando os pagamentos subsequentes.

#### **Fase 1: Fundação - Modelo de Dados e Relacionamentos**

*   **Objetivo:** Adaptar a estrutura do banco de dados para suportar múltiplos pagamentos por inscrição, criando um sistema robusto para rastrear o histórico financeiro de cada participante.

*   **Ações:**
    1.  **Implementar a Tabela `payments`:**
        *   **Ação:** Criar uma migration para uma nova tabela chamada `payments`. Esta tabela centralizará todos os registros financeiros.
        *   **Estrutura:** A tabela deve incluir as colunas: `id` (PK), `registration_id` (chave estrangeira para `registrations`), `amount` (decimal, para o valor do pagamento), `status` (string, para status como 'pending', 'paid', 'pending_approval'), `payment_proof_path` (string, nullable), `payment_date` (timestamp, nullable), e `notes` (text, nullable).
        *   **Lógica:** Um novo registro em `payments` será criado no momento da inscrição inicial e a cada vez que uma modificação gerar um novo valor a ser pago.

    2.  **Desenvolver o Model `Payment`:**
        *   **Ação:** Criar o Model Eloquent `App\Models\Payment`.
        *   **Relacionamento:** Definir o relacionamento `belongsTo(Registration::class)` para vincular cada pagamento a uma inscrição.

    3.  **Refatorar o Model `Registration`:**
        *   **Ação:** Modificar o model `App\Models\Registration` para refletir a nova estrutura.
        *   **Relacionamento:** Adicionar o relacionamento inverso `hasMany(Payment::class)` para permitir o acesso fácil a todos os pagamentos de uma inscrição (`$registration->payments`).
        *   **Revisão de Colunas:** As colunas `calculated_fee`, `payment_proof_path`, e `payment_uploaded_at` serão removidas da tabela `registrations`, pois essa responsabilidade agora pertence a cada registro individual na tabela `payments`.
        *   **Status Geral:** A coluna `registrations.payment_status` será mantida, mas seu propósito mudará. Ela representará um **status geral consolidado**, computado a partir dos status de todos os `payments` associados. Por exemplo, 'completed' se todos os pagamentos estiverem 'paid'; 'partially_paid' se houver pagamentos concluídos e pendentes.

#### **Fase 2: Backend - Lógica de Negócio e Serviços**

*   **Objetivo:** Construir a lógica de negócio para recalcular taxas, aplicar descontos retroativamente e gerenciar notificações por e-mail.

*   **Ações:**
    1.  **Aprimorar o `FeeCalculationService`:**
        *   **Ação:** Modificar o serviço para que ele possa processar tanto novas inscrições quanto modificações de inscrições existentes.
        *   **Funcionalidade:** O método principal aceitará um objeto `Registration` opcional. Se fornecido, o serviço calculará o valor total já pago (somando os `payments` com status 'paid') e aplicará descontos retroativamente (ex: se a adição do evento principal conceder desconto em um workshop já selecionado). O serviço retornará o novo valor total e a diferença a ser paga.

    2.  **Implementar a Lógica de Modificação:**
        *   **Ação:** Criar um novo `RegistrationModificationController` ou adicionar métodos a um controller existente.
        *   **Fluxo:** O método `store` receberá a nova seleção de eventos, usará o `FeeCalculationService` para determinar a diferença a pagar e, se a diferença for maior que zero, criará um novo registro na tabela `payments` com o status 'pending'. A relação `event_registration` (pivot) será atualizada para refletir a nova lista de eventos.

    3.  **Desenvolver o Sistema de Notificações:**
        *   **E-mail de Modificação:** Criar um novo Mailable (`RegistrationModifiedNotification`) a ser enviado ao participante após uma alteração, contendo o resumo atualizado, o novo valor devido e as instruções de pagamento.
        *   **Notificação ao Coordenador:** Garantir que o e-mail do coordenador do evento seja incluído em cópia (CC ou CCO) em todas as comunicações automáticas enviadas aos participantes (inscrição, modificação, confirmação).
        *   **Lembrete de "Early Bird":** Criar um Comando Artisan agendável para ser executado um dia antes do fim do prazo de "early bird". O comando enviará um e-mail de lembrete para participantes com pagamentos pendentes, informando sobre o aumento iminente do valor.

#### **Fase 3: Frontend - Interface do Participante**

*   **Objetivo:** Proporcionar uma experiência de usuário clara e intuitiva para visualizar e modificar inscrições, bem como para gerenciar múltiplos pagamentos.

*   **Ações:**
    1.  **Atualizar a Página "Minha Inscrição":**
        *   **Ação:** Renomear a rota e a página de `/my-registrations` para `/my-registration`, refletindo a política de uma inscrição única e modificável.
        *   **Funcionalidade:** Adicionar um botão "Adicionar Eventos" nesta página, que será o ponto de partida para o fluxo de modificação. A página também exibirá um histórico de pagamentos, listando cada registro da tabela `payments` com seu status. Para cada pagamento pendente, um formulário de upload de comprovante deve ser exibido.

    2.  **Criar a Interface de Modificação de Inscrição:**
        *   **Ação:** Desenvolver um novo componente Livewire/Volt para a página de modificação.
        *   **Funcionalidade:** Esta interface permitirá ao usuário selecionar novos eventos/workshops. Um resumo financeiro dinâmico deve ser exibido em tempo real, mostrando "Valor Original", "Valor já Pago", "Custo dos Novos Itens" e "Total a Pagar Agora". Se um pagamento anterior estiver em análise pelo administrador, o sistema exibirá um aviso para que o usuário entre em contato com a organização antes de prosseguir, embora a ação não seja bloqueada.

    3.  **Gerenciamento de Comprovantes:**
        *   **Ação:** O formulário de upload na página "Minha Inscrição" será associado a um `payment` específico.
        *   **Funcionalidade:** Após o upload bem-sucedido de um comprovante para um `payment`, o respectivo formulário de upload será ocultado. O usuário terá a opção de visualizar (baixar) os comprovantes que já enviou. Em caso de problemas com o upload, o usuário será instruído a contatar a organização.

#### **Fase 4: Backend/Frontend - Painel do Administrador**

*   **Objetivo:** Equipar os administradores com ferramentas eficazes para gerenciar o novo fluxo de inscrições modificadas e pagamentos múltiplos.

*   **Ações:**
    1.  **Aprimorar a Listagem de Inscrições:**
        *   **Ação:** Na tela principal de administração, adicionar uma coluna "Status de Pagamentos" que exiba visualmente (ex: com badges coloridos) os status de todos os `payments` associados a cada inscrição, permitindo uma rápida identificação de ações necessárias.

    2.  **Detalhes da Inscrição para o Admin:**
        *   **Ação:** A página de detalhes de uma inscrição será redesenhada para incluir uma "timeline" ou histórico de pagamentos.
        *   **Funcionalidade:** Cada `payment` será listado com seu valor, status e um link para baixar o comprovante. O formulário de aprovação de pagamento atuará sobre um `payment` individual, não sobre a inscrição como um todo.

    3.  **Implementar Relatórios Financeiros Avançados:**
        *   **Ação:** Criar uma nova área de relatórios no painel de administração.
        *   **Funcionalidade:** Oferecer duas visualizações exportáveis (CSV/Excel): um relatório "Simples" (consolidado, com o total pago por inscrição) e um "Completo" (detalhado, com uma linha para cada `payment` individual).

    4.  **Criar Interface de Edição Completa para Admin:**
        *   **Ação:** Desenvolver uma nova página (`/admin/registrations/{id}/edit-full`) de acesso restrito.
        *   **Funcionalidade:** Esta interface permitirá ao administrador editar qualquer campo da inscrição de um participante, bem como os eventos e pagamentos associados. Todas as ações nesta página devem ser rigorosamente registradas em um log de auditoria (ex: usando `spatie/laravel-activitylog`). A política de remoção de eventos por participantes será implementada aqui, permitindo que apenas administradores realizem essa ação.

#### **Fase 5: Testes e Validação**

*   **Objetivo:** Assegurar a robustez, correção e segurança de todas as novas funcionalidades implementadas.

*   **Ações:**
    1.  **Testes de Unidade:** Desenvolver testes para o `FeeCalculationService` que cubram todos os cenários de recálculo, incluindo descontos retroativos e o tratamento de inscrições já pagas.
    2.  **Testes de Feature:** Criar testes abrangentes para os fluxos de backend, como a modificação de uma inscrição, a criação de novos registros de `payment`, e o processo de aprovação de pagamentos pelo administrador.
    3.  **Testes de Browser (Dusk):** Atualizar os testes de interface para cobrir o novo fluxo do participante, incluindo a adição de eventos, o cálculo dinâmico de taxas na tela e o upload de múltiplos comprovantes.