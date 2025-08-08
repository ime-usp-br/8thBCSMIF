### **Estrutura Proposta para a Área Administrativa**

A área administrativa poderia ser dividida em quatro seções principais:

1.  **Dashboard Principal:** Uma visão geral com estatísticas e ações rápidas.
2.  **Gerenciamento de Inscrições:** A área principal para o trabalho diário de validação.
3.  **Relatórios e Exportações:** Para gerar as listas de ônibus e outros dados.
4.  **Ferramentas do Evento:** Onde a geração de certificados se encaixaria.

---

### **1. Dashboard Principal (`/admin/dashboard`)**

Esta seria a página inicial do admin, oferecendo uma visão rápida do status do evento.

*   **Widgets de Estatísticas:**
    *   **Total de Inscrições:** Contagem total.
    *   **Inscrições por Categoria:** Gráfico de pizza (Graduação, Pós-Graduação, Professor, etc.).
    *   **Comprovantes Pendentes:** Links rápidos para `Pagamentos Pendentes` e `Matrículas Pendentes`.
    *   **Receita:** Total confirmado e total pendente.
    *   **Necessidade de Transporte:** Contagem de participantes para o ônibus da USP e para o de GRU.
*   **Ações Rápidas:**
    *   Botão para "Ver Inscrições".
    *   Botão para "Gerar Relatórios".
*   **Atividade Recente:**
    *   Uma lista das últimas 5-10 inscrições ou uploads de comprovantes.

---

### **2. Gerenciamento de Inscrições (`/admin/registrations`)**

Esta é a seção operacional principal, onde a maior parte do trabalho de validação acontece.

#### **2.1. Tela de Listagem (`/admin/registrations`)**

Seu código já possui uma base excelente para isso com o `AdminRegistrationController` e o componente Livewire `RegistrationsList`. Podemos aprimorar a visualização:

*   **Tabela de Inscrições:**
    *   **Colunas:** ID, Nome do Participante, Email, Eventos (badges `BCSMIF`, `RAA`, etc.), Status do Pagamento (badge colorido), Status da Matrícula (ícone/badge para estudantes), Data da Inscrição, Ações.
    *   **Filtros (Já implementados):** Manter e talvez aprimorar os filtros de busca por nome/email, evento, status de pagamento.
    *   **Ações Rápidas na Linha:** Um botão de "Detalhes" que leva à página de visualização completa.

#### **2.2. Tela de Detalhes da Inscrição (`/admin/registrations/{registration}`) - (Reforma Proposta)**

Propomos uma refatoração completa desta tela para resolver a confusão atual e otimizar o fluxo de validação. A ideia é adotar um layout claro de duas colunas, separando a visualização de dados das ações interativas.

*   **Layout Proposto:** Uma tela dividida.
    *   **Coluna Principal (Esquerda - 70% da largura):** Dedicada exclusivamente à **visualização dos dados do participante**. Para evitar poluição visual, as informações seriam organizadas em seções expansíveis (formato acordeão), como:
        *   Informações Pessoais (Nome, Nacionalidade, Gênero)
        *   Detalhes de Contato (Email, Telefone, Endereço)
        *   Detalhes de Identificação (CPF, Passaporte)
        *   Detalhes Profissionais (Afiliação, Cargo, Membro ABE)
        *   Detalhes da Participação (Datas, Formato, Transporte)
        *   Informações Adicionais (Restrições Alimentares, Contato de Emergência)
    *   **Coluna de Ações (Direita - 30% da largura):** Um "hub" de status e ações, contendo painéis interativos.

*   **Painéis de Ação (Coluna Direita):**
    *   **Painel de Status Geral (Novo):**
        *   No topo, um card de destaque exibindo o **status geral da inscrição** (`pending`, `approved`, `rejected`) com uma badge colorida.
        *   Exibe a categoria do participante (`registration_category_snapshot`).
        *   Pode incluir links de ação rápida como "Enviar E-mail ao Participante".

    *   **Painel de Comprovantes de Matrícula (Aprimorado, visível apenas para estudantes):**
        *   **Status Atual:** "Pendente", "Aprovado" ou "Rejeitado".
        *   **Visualizador:** Um `<iframe>` ou `<img>` para visualizar o documento diretamente na tela, sem necessidade de download.
        *   **Ações (Livewire/AJAX):**
            *   Botão verde **"Aprovar Matrícula"**: Atualiza o status sem recarregar a página.
            *   Botão vermelho **"Rejeitar Matrícula"**: Abre um modal para inserir o motivo, que será salvo e enviado ao participante.
        *   **Informações:** Link para download, data do upload.
        *   _Backend: Conecta-se às rotas `approve-enrollment-proof` e `reject-enrollment-proof`._

    *   **Painel de Histórico de Pagamentos (Aprimorado):**
        *   Uma lista de **todos os pagamentos** associados a esta inscrição (essencial para o novo fluxo de modificações).
        *   Para cada pagamento individual na lista:
            *   **Valor:** R$ XXX,XX
            *   **Status:** "Pendente", "Aguardando Comprovante", "Aprovado", "Rejeitado" (com badge colorida).
            *   **Visualizador de Comprovante:** Se um comprovante foi enviado, um link para abri-lo no visualizador de documentos.
            *   **Ações por Pagamento (se comprovante enviado):**
                *   Botão **"Aprovar Pagamento"**: Age sobre este pagamento específico.
                *   Botão **"Rejeitar Pagamento"** (com modal para motivo).
        *   _Backend: Conecta-se à lógica de atualização de status do `Payment`, que por sua vez atualiza o status geral da `Registration`._

---

### **3. Relatórios e Exportações (`/admin/reports`)**

Seu `ReportsController` já é o lugar perfeito para isso. Vamos adicionar a funcionalidade das listas de ônibus.

#### **3.1. Página de Relatório de Transporte (`/admin/reports/transport`)**

*   **Interface:**
    *   Dois botões grandes: "Gerar Lista para Ônibus USP" e "Gerar Lista para Ônibus GRU".
    *   Opcional: Filtros por data de chegada/partida.

*   **Funcionalidade:**
    *   Ao clicar em um dos botões, o sistema gera uma lista (em tela e com opção de exportar para CSV/Excel) de todos os participantes que marcaram a respectiva opção (`needs_transport_from_usp` ou `needs_transport_from_gru`).
    *   **Colunas Relevantes para a Lista:**
        1.  `full_name` (Nome Completo)
        2.  `phone_number` (Telefone de Contato)
        3.  `email` (Email)
        4.  `arrival_date` (Data de Chegada)
        5.  `departure_date` (Data de Partida)
        6.  `emergency_contact_name` (Nome do Contato de Emergência)
        7.  `emergency_contact_phone` (Telefone do Contato de Emergência)
    *   **Lógica Backend:**
        *   Criar um novo método no `ReportsController`, por exemplo, `transportReport(Request $request)`.
        *   A query seria algo como: `Registration::where('needs_transport_from_usp', true)->where('payment_status', 'approved')->get([...colunas...]);`
        *   Para exportação, pacotes como `maatwebsite/excel` são excelentes.

---

### **4. Ferramentas do Evento (`/admin/tools`)**

#### **4.1. Geração de Certificados (`/admin/tools/certificates`)**

Este é um módulo mais complexo, mas o fluxo pode ser o seguinte:

*   **Passo 1: Template do Certificado**
    *   O admin faz o upload de um modelo de certificado em PDF, que já contém o design, logos e textos fixos, mas com espaços em branco para os dados dinâmicos.

*   **Passo 2: Geração**
    *   A interface de geração teria filtros para selecionar os participantes (ex: "Todos com pagamento Aprovado", "Todos do evento BCSMIF2025").
    *   O admin seleciona os participantes desejados em uma lista.
    *   Ao clicar em "Gerar Certificados", o backend faz o seguinte para cada participante selecionado:
        1.  Pega o template PDF.
        2.  Usa uma biblioteca como `FPDI` ou `TCPDF` para escrever sobre o PDF existente.
        3.  Insere o `full_name` do participante, o nome do(s) evento(s) em que participou, e a data do evento.
        4.  Salva o novo PDF com um nome único (ex: `certificado_id-inscricao_nome-participante.pdf`).
    *   **Resultado:** O sistema oferece o download de um arquivo `.zip` contendo todos os PDFs gerados.

*   **Passo 3 (Opcional): Envio por Email**
    *   Uma opção adicional poderia ser "Gerar e Enviar por Email", que colocaria os e-mails com os certificados anexados em uma fila de envio.

---

### **Resumo e Próximos Passos Sugeridos**

1.  **Refatorar a Tela de Detalhes da Inscrição:** Esta é a prioridade máxima para resolver a confusão da interface e otimizar o fluxo de trabalho de validação. A maior parte do trabalho aqui é de frontend (Blade/Livewire/Alpine), pois o backend já tem as ações de aprovação/rejeição.
2.  **Construir a Página de Relatório de Transporte:** É uma funcionalidade relativamente simples, com grande valor prático. Requer uma nova rota, um método no `ReportsController`, uma view e a lógica de exportação.
3.  **Desenvolver o Módulo de Certificados:** Por ser mais complexo, pode ser a última etapa. Requer pesquisa e implementação de uma biblioteca de manipulação de PDF.
4.  **Criar o Dashboard Administrativo:** (Em andamento via Issue #89) Criar a página inicial do admin para unificar o acesso e fornecer uma visão geral rápida.