### **Guia do Administrador para o Sistema de Inscrições do 8th BCSMIF**

**Versão 0.1**

Bem-vindo(a) ao guia oficial para administradores do sistema de inscrições do **8th Brazilian Conference on Statistical Modeling in Insurance and Finance (8th BCSMIF)**. Este documento foi criado para orientá-lo(a) em todas as funcionalidades da área administrativa, garantindo que você possa gerenciar as inscrições, aprovar pagamentos e extrair informações importantes com eficiência.

---

#### **Índice**

1.  **Acesso ao Sistema**
2.  **Visão Geral: O Dashboard Administrativo**
    *   Widgets de Estatísticas
    *   Feed de Atividade Recente
    *   Ações Rápidas
3.  **Gerenciamento de Inscrições: O Dia a Dia**
    *   Navegando pela Lista de Inscrições
    *   Utilizando os Filtros
    *   Analisando os Detalhes de uma Inscrição
4.  **Fluxo de Aprovação: Validando Comprovantes**
    *   Usando a Fila de Aprovação
    *   Validando Comprovantes de Pagamento
    *   Validando Comprovantes de Matrícula (Estudantes)
5.  **Relatórios e Exportação de Dados**
    *   Relatório de Comprovantes de Matrícula
    *   Relatório de Pagamentos
    *   Relatório de Pagamentos Auto-aprovados
6.  **Fluxo de Trabalho Recomendado**

---

### **1. Acesso ao Sistema**

Para acessar a área administrativa, você precisa de uma conta de usuário com a permissão de "admin".

1.  Acesse a página de login do sistema.
2.  Faça login com suas credenciais de administrador.
3.  Após o login, você será redirecionado automaticamente para o **Dashboard Administrativo**. Você também verá links para a área de administração no menu de navegação do seu perfil.

---

### **2. Visão Geral: O Dashboard Administrativo (`/admin/dashboard`)**

O dashboard é sua central de comando. Ele oferece uma visão geral e em tempo real de tudo o que está acontecendo no sistema de inscrições.

#### **Widgets de Estatísticas**

No topo da página, você encontrará widgets interativos que resumem os dados mais importantes:

*   **Total de Inscrições:** Mostra o número total de participantes inscritos e um indicador de tendência (crescimento ou queda) em comparação com o mês anterior.
*   **Aprovações Pendentes:** Este é um dos widgets mais importantes para o trabalho diário. Ele mostra quantos **Comprovantes de Pagamento** e **Comprovantes de Matrícula** estão aguardando sua análise. Clicar nos botões "Revisar" levará você diretamente para a fila de aprovação.
*   **Receita:** Apresenta um resumo financeiro, dividido entre receita **Confirmada** (pagamentos já aprovados) e **Pendente** (pagamentos aguardando aprovação ou envio de comprovante).
*   **Necessidades de Transporte:** Informa quantos participantes solicitaram transporte saindo da USP e/ou do Aeroporto de Guarulhos (GRU). Essencial para a logística do evento.
*   **Inscrições por Categoria:** Um gráfico visual que divide os inscritos por categoria (Estudante de Graduação, Pós-Graduação, Professor, etc.), ajudando a entender o perfil do público.

#### **Feed de Atividade Recente**

Localizado no dashboard, este feed é atualizado automaticamente e mostra as últimas ações realizadas no sistema, como:
*   Novas inscrições enviadas.
*   Uploads de comprovantes de pagamento.
*   Uploads de comprovantes de matrícula.

Isso permite que você tenha uma visão em tempo real do que está acontecendo.

#### **Ações Rápidas**

Abaixo dos widgets, você encontrará botões de atalho para as seções mais importantes:
*   **Ver Inscrições:** Leva para a lista completa de todos os participantes.
*   **Gerar Relatórios:** Leva para a central de relatórios.
*   **Aprovações Pendentes:** Leva para a fila de validação de documentos.

---

### **3. Gerenciamento de Inscrições: O Dia a Dia (`/admin/registrations`)**

Esta é a área onde você passará a maior parte do tempo, gerenciando e analisando cada inscrição individualmente.

#### **Navegando pela Lista de Inscrições**

Ao acessar esta página, você verá uma tabela com todas as inscrições. Para cada participante, a tabela exibe:
*   **ID da Inscrição:** Um identificador único.
*   **Nome e Email:** Para identificação rápida.
*   **Eventos:** Badges que indicam em quais eventos (8th BCSMIF, RAA2025, etc.) o participante se inscreveu.
*   **Taxa:** O valor total da inscrição.
*   **Status Geral:** O status consolidado da inscrição (`Pendente`, `Aprovado`, `Rejeitado`, etc.).
*   **Status dos Pagamentos:** Badges coloridos que mostram o status de cada pagamento associado.
*   **Documentos de Estudante:** O status do comprovante de matrícula (se aplicável).

#### **Utilizando os Filtros**

No topo da lista, há uma poderosa seção de filtros para ajudá-lo(a) a encontrar exatamente o que precisa:

*   **Busca Rápida:** Procure por nome, email ou ID.
*   **Filtros Básicos:** Filtre por **Evento**, **Status do Pagamento** e **Categoria** do participante.
*   **Filtros Avançados:** Clique em "Mostrar Avançados" para filtrar por:
    *   **Intervalo de Datas:** Inscrições feitas em um período específico.
    *   **Faixa de Valor da Taxa:** Inscrições com valores mínimos ou máximos.
    *   **País:** Participantes do Brasil ou internacionais.
    *   **Necessidade de Transporte:** Encontre rapidamente quem precisa de ônibus.

#### **Analisando os Detalhes de uma Inscrição (`/admin/registrations/{id}`)**

Ao clicar em "Detalhes" (ou "View") em uma inscrição na lista, você acessará a página de perfil completa do participante.

Esta página é dividida em seções:
*   **Informações do Participante:** Todos os dados preenchidos no formulário (pessoais, contato, profissionais, etc.).
*   **Eventos e Taxas:** Um resumo dos eventos selecionados e o cálculo final da taxa.
*   **Painel de Validação de Pagamento:**
    *   **Download do Comprovante:** Se um comprovante foi enviado, um botão **"Download Payment Proof"** estará disponível.
    *   **Ações:** Botões para **Aprovar** ou **Rejeitar** o pagamento. Ao rejeitar, uma janela modal aparecerá para que você escreva o motivo, que será enviado por e-mail ao participante.
*   **Painel de Validação de Matrícula (apenas para estudantes):**
    *   Funciona de forma similar ao de pagamento. Você pode baixar o comprovante de matrícula e aprová-lo ou rejeitá-lo com um motivo.
*   **Atualização de Status Geral:**
    *   Um formulário permite alterar o status geral da inscrição manualmente, se necessário. Você pode marcar a opção para notificar o participante por e-mail sobre a mudança.

---

### **4. Fluxo de Aprovação: Validando Comprovantes (`/admin/approvals`)**

Para agilizar o trabalho de validação, o sistema conta com uma **Fila de Aprovação**. Esta página é sua "caixa de entrada" de tarefas.

#### **Usando a Fila de Aprovação**

*   Esta tela unifica todos os documentos que precisam da sua atenção: **comprovantes de pagamento**, **comprovantes de matrícula** e **isenções de taxa** (para estudantes de graduação, por exemplo).
*   Você pode usar os filtros para focar em um tipo de documento por vez.
*   Cada item na lista possui ações rápidas:
    *   **Ícone de olho:** Leva para a página de detalhes da inscrição.
    *   **Ícone de download:** Baixa o comprovante diretamente.
    *   **Botão verde (✓):** Aprova o comprovante com um clique.
    *   **Botão vermelho (X):** Rejeita o comprovante.
*   **Validação Dupla:** Para estudantes de pós-graduação, o sistema pode indicar que há uma "Validação Dupla". Isso significa que o mesmo participante enviou tanto um comprovante de pagamento quanto um de matrícula, e ambos precisam ser analisados.

---

### **5. Relatórios e Exportação de Dados (`/admin/reports`)**

A seção de relatórios permite extrair dados consolidados do sistema.

*   **Relatório de Comprovantes de Matrícula:** Lista todos os comprovantes de matrícula, com filtros por status e data.
*   **Relatório de Pagamentos:** Lista todas as transações financeiras, com filtros por status, valor e data.
*   **Relatório de Pagamentos Auto-aprovados:** Mostra as inscrições gratuitas que foram aprovadas automaticamente (como workshops para estudantes de pós-graduação).

**Futuramente, esta seção também incluirá a funcionalidade para exportar as listas de participantes para a logística de transporte.**

---

### **6. Fluxo de Trabalho Recomendado**

Para um gerenciamento eficiente, sugerimos o seguinte fluxo de trabalho:

1.  **Diariamente:**
    *   Acesse o **Dashboard** para ter uma visão geral.
    *   Verifique o widget de **Aprovações Pendentes** e o **Feed de Atividade**.
    *   Vá para a **Fila de Aprovação (`/admin/approvals`)** e processe os comprovantes pendentes usando as ações rápidas.
    *   Para casos que exijam mais detalhes, clique no ícone de olho para ir à página de **Detalhes da Inscrição**.

2.  **Semanalmente (ou conforme a necessidade):**
    *   Acesse a **Lista de Inscrições (`/admin/registrations`)** para revisar inscrições que possam ter ficado para trás ou para buscar participantes específicos.
    *   Acesse a seção de **Relatórios (`/admin/reports`)** para acompanhar as finanças e o status geral das matrículas.

3.  **Próximo ao Evento:**
    *   Utilize os filtros na **Lista de Inscrições** para encontrar participantes com necessidades de transporte e planejar a logística.
    *   Use a seção de **Relatórios** para extrair listas finais de participantes por evento.