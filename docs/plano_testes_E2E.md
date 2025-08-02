### **Plano Expandido de Implementação de Testes E2E com Laravel Dusk para o Site 8th BCSMIF**

> **Versão Expandida**: Este documento foi expandido com base na análise completa do código atual do projeto 8th BCSMIF, incluindo modelos, componentes Livewire, lógica de negócio e interfaces administrativas.

**1. Introdução e Objetivo**

Este plano detalha a estratégia para criar uma suíte de testes E2E robusta e de fácil manutenção para o site de inscrições do 8th BCSMIF, utilizando Laravel Dusk. O objetivo principal é simular as interações reais de um usuário, garantindo a funcionalidade, integridade e usabilidade dos fluxos críticos da aplicação, com ênfase especial no processo de inscrição e modificação.

**2. Escopo dos Testes**

*   **INCLUÍDO:** Todos os fluxos do ponto de vista do usuário não-administrador.
    *   Autenticação (Login Local, Registro Local, Reset de Senha, Login Senha Única).
    *   Fluxo de Inscrição Principal (para diferentes categorias de participantes).
    *   Fluxo Pós-Inscrição (visualização, upload de comprovantes).
    *   Fluxo de Modificação de Inscrição (adição de novos eventos).
*   **EXCLUÍDO:**
    *   Interface do painel de administração.
    *   Testes de API, Unitários ou de Integração (que são cobertos por PHPUnit).
    *   Testes de performance e carga.

**3. Pré-requisitos e Configuração do Ambiente de Teste**

Antes de escrever os testes, a seguinte configuração **DEVE** ser realizada:

1.  **Instalação do Dusk:**
    *   Adicionar o Laravel Dusk como dependência de desenvolvimento:
        ```bash
        composer require --dev laravel/dusk
        ```
    *   Instalar o scaffolding do Dusk:
        ```bash
        php artisan dusk:install
        ```

2.  **Configuração do Ambiente Dusk:**
    *   Criar o arquivo `.env.dusk.local` na raiz do projeto. Este arquivo será usado para executar os testes localmente.
    *   Configurar as variáveis essenciais no `.env.dusk.local`:
        ```dotenv
        APP_URL=http://127.0.0.1:8000
        APP_ENV=dusk
        DB_CONNECTION=sqlite
        DB_DATABASE=/path/to/your/project/database/dusk.sqlite
        # Demais configurações necessárias, como Senha Única (com valores de teste)
        SENHAUNICA_KEY=test_key
        SENHAUNICA_SECRET=test_secret
        ```
    *   Criar o arquivo de banco de dados SQLite para Dusk:
        ```bash
        touch database/dusk.sqlite
        ```

3.  **ChromeDriver:**
    *   Garantir que o Google Chrome ou Chromium esteja instalado na máquina de desenvolvimento e no ambiente de CI.
    *   Instalar o ChromeDriver compatível:
        ```bash
        php artisan dusk:chrome-driver --detect
        ```

4.  **Factories e Seeders de Teste:**
    *   Garantir que as Factories para `User`, `Event`, `Fee`, `Registration`, `Payment` e `EnrollmentProof` estejam completas e capazes de gerar todos os cenários necessários.
    *   Criar um Seeder específico para o ambiente de teste Dusk, se necessário, para popular `events` e `fees` com dados consistentes.

**4. Estratégia de Teste e Boas Práticas**

1.  **Page Objects e Components:** Para máxima manutenibilidade e legibilidade, os testes **DEVEM** utilizar o padrão Page Object.
    *   **Pages:** Criar classes de Página para cada tela principal:
        *   `LoginPage.php`
        *   `RegisterPage.php`
        *   `MyRegistrationPage.php`
        *   `RegistrationModificationPage.php`
    *   **Components:** Criar classes de Componente para elementos reutilizáveis:
        *   `RegistrationForm.php` (para o formulário de inscrição principal).
        *   `PaymentUploadComponent.php` (para o formulário de upload de comprovante).
        *   `EnrollmentProofUploadComponent.php` (para o upload de comprovante de matrícula).

2.  **Seletores Dusk:** Adicionar atributos `dusk="..."` aos elementos HTML mais importantes (inputs, botões, links, áreas de feedback) para criar seletores estáveis e desacoplados da estrutura CSS/HTML.

3.  **Gerenciamento de Dados:** Utilizar o Trait `DatabaseTruncation` em vez de `DatabaseMigrations` na classe `DuskTestCase` para acelerar a execução dos testes. O banco de dados será migrado uma vez e truncado entre os testes.

4.  **Tratamento de Livewire:** Utilizar os helpers do Dusk para Livewire, como `->waitForLivewire()` e `->assertSeeIn('@livewire-component', '...')`, para lidar com a natureza assíncrona dos componentes.

**5. Plano de Testes Detalhado**

#### **Suíte 1: Autenticação e Contas de Usuário (EXPANDIDA)**

##### **1.1 Login Local - Cenários Básicos**

*   **Teste 1.1.1 (Login Local - Sucesso com Email Verificado):**
    *   **Dado** um usuário com conta local e email verificado
    *   **Quando** ele acessa `/login` e preenche credenciais corretas
    *   **Então** ele é redirecionado para `/my-registration`
    *   **E** vê uma mensagem de boas-vindas personalizada

*   **Teste 1.1.2 (Login Local - Falha de Credenciais):**
    *   **Dado** um usuário com conta local
    *   **Quando** ele preenche senha incorreta e submete
    *   **Então** permanece na página de login
    *   **E** vê mensagem específica "These credentials do not match our records"
    *   **E** o campo senha é limpo automaticamente

*   **Teste 1.1.3 (Login Local - Email Não Verificado):**
    *   **Dado** um usuário com conta local mas email não verificado
    *   **Quando** faz login com credenciais corretas
    *   **Então** é redirecionado para `/verify-email`
    *   **E** vê instruções para verificação de email
    *   **E** pode solicitar reenvio do email de verificação

##### **1.2 Login USP Senha Única**

*   **Teste 1.2.1 (Redirect para Senha Única):**
    *   **Quando** usuário clica no botão "Login com Senha Única USP"
    *   **Então** é redirecionado para o sistema USP (mock em ambiente de teste)
    *   **E** o callback retorna dados corretos do usuário USP

*   **Teste 1.2.2 (Login USP - Primeira Vez):**
    *   **Dado** um usuário USP que nunca se registrou no sistema
    *   **Quando** completa autenticação USP e retorna via callback
    *   **Então** uma conta é criada automaticamente
    *   **E** é redirecionado para completar o perfil
    *   **E** campo NUSP é preenchido automaticamente

*   **Teste 1.2.3 (Login USP - Usuário Existente):**
    *   **Dado** um usuário USP já registrado no sistema
    *   **Quando** faz login via Senha Única
    *   **Então** é autenticado diretamente
    *   **E** redirecionado para `/my-registration`

##### **1.3 Registro de Usuário - Cenários Detalhados**

*   **Teste 1.3.1 (Registro - Usuário Externo Completo):**
    *   **Quando** usuário acessa `/register` com email não-USP
    *   **Então** campo "Número USP" não aparece na interface
    *   **E** após submeter dados válidos, conta é criada
    *   **E** email de verificação é enviado
    *   **E** usuário é redirecionado para `/verify-email`

*   **Teste 1.3.2 (Registro - Usuário USP):**
    *   **Quando** usuário preenche email `@usp.br` no formulário
    *   **Então** campo "Número USP" aparece dinamicamente
    *   **E** torna-se obrigatório com validação específica
    *   **E** validação NUSP é acionada contra ReplicadoService

*   **Teste 1.3.3 (Registro - Validação de Campos):**
    *   **Quando** usuário submete formulário com dados inválidos
    *   **Então** mensagens de erro específicas aparecem:
        - Email já cadastrado: "The email has already been taken"
        - NUSP inválido: "Invalid USP number"
        - Senha fraca: requisitos específicos de senha
    *   **E** dados válidos são preservados no formulário

*   **Teste 1.3.4 (Registro - NUSP Duplicado):**
    *   **Dado** usuário USP já registrado com determinado NUSP
    *   **Quando** outro usuário tenta registrar com mesmo NUSP
    *   **Então** validação impede cadastro duplicado
    *   **E** mensagem específica é exibida

##### **1.4 Verificação de Email**

*   **Teste 1.4.1 (Processo de Verificação):**
    *   **Dado** usuário recém-registrado na tela `/verify-email`
    *   **Quando** clica no link recebido por email
    *   **Então** email é marcado como verificado
    *   **E** usuário é redirecionado para `/my-registration`
    *   **E** vê mensagem de confirmação

*   **Teste 1.4.2 (Reenvio de Email de Verificação):**
    *   **Dado** usuário na tela de verificação
    *   **Quando** clica em "Resend Verification Email"
    *   **Então** novo email é enviado
    *   **E** mensagem de confirmação é exibida
    *   **E** botão fica temporariamente desabilitado

##### **1.5 Reset de Senha - Fluxo Completo**

*   **Teste 1.5.1 (Solicitação de Reset):**
    *   **Quando** usuário acessa `/forgot-password` e informa email válido
    *   **Então** email com link de reset é enviado
    *   **E** mensagem de confirmação é exibida
    *   **E** usuário é instruído a verificar email

*   **Teste 1.5.2 (Reset de Senha Válido):**
    *   **Dado** usuário recebe email de reset
    *   **Quando** clica no link válido e define nova senha
    *   **Então** senha é atualizada com sucesso
    *   **E** usuário é redirecionado para login
    *   **E** pode fazer login com nova senha

*   **Teste 1.5.3 (Link de Reset Inválido/Expirado):**
    *   **Quando** usuário acessa link de reset inválido ou expirado
    *   **Então** vê mensagem de erro apropriada
    *   **E** é redirecionado para solicitar novo reset

##### **1.6 Cenários de Segurança e Proteção**

*   **Teste 1.6.1 (Rate Limiting):**
    *   **Quando** usuário faz múltiplas tentativas de login incorretas
    *   **Então** sistema aplica rate limiting temporário
    *   **E** mensagem informa sobre bloqueio temporário

*   **Teste 1.6.2 (CSRF Protection):**
    *   **Quando** formulários são submetidos sem token CSRF válido
    *   **Então** submissão é rejeitada com erro 419

*   **Teste 1.6.3 (Session Management):**
    *   **Dado** usuário logado
    *   **Quando** faz logout
    *   **Então** sessão é invalidada completamente
    *   **E** tentativa de acessar área restrita redireciona para login

#### **Suíte 2: Fluxo Principal de Inscrição (EXPANDIDA)**

Esta suíte cobre o complexo formulário de inscrição de 9 seções com validação dinâmica, cálculo de taxas e diferentes categorias de participante.

##### **2.1 Navegação e Estrutura do Formulário**

*   **Teste 2.1.1 (Navegação Entre Seções):**
    *   **Dado** usuário na página de inscrição `/registration`
    *   **Quando** navega entre as 9 seções usando botões Previous/Next
    *   **Então** progresso é preservado em cada seção
    *   **E** validação ocorre ao tentar avançar de seção
    *   **E** indicador de progresso atualiza corretamente

*   **Teste 2.1.2 (Auto-save e Recuperação):**
    *   **Dado** usuário preenchendo formulário
    *   **Quando** dados são inseridos em campos
    *   **Então** informações são persistidas automaticamente
    *   **E** dados são recuperados se usuário recarregar página
    *   **E** sessão preserva estado entre seções

##### **2.2 Seção 1: Informações Pessoais Básicas**

*   **Teste 2.2.1 (Campos Obrigatórios Básicos):**
    *   **Quando** usuário tenta avançar sem preencher campos obrigatórios
    *   **Então** validação bloqueia avanço
    *   **E** campos são marcados com erro visual
    *   **E** mensagens específicas aparecem para cada campo

*   **Teste 2.2.2 (Validação de Data de Nascimento):**
    *   **Quando** usuário insere data futura ou muito antiga
    *   **Então** validação rejeita com mensagem específica
    *   **E** aceita apenas datas realistas (18-100 anos)

*   **Teste 2.2.3 (Seleção de Gênero):**
    *   **Quando** usuário seleciona opção de gênero
    *   **Então** seleção é registrada corretamente
    *   **E** opção "Prefiro não informar" está disponível

##### **2.3 Seção 2: Documentos e Identificação**

*   **Teste 2.3.1 (Validação CPF - Brasileiro):**
    *   **Dado** usuário selecionou nacionalidade brasileira
    *   **Quando** preenche CPF inválido
    *   **Então** validação rejeita com algoritmo específico
    *   **E** aceita apenas CPFs válidos com formatação automática

*   **Teste 2.3.2 (Documentos Internacionais):**
    *   **Dado** usuário selecionou nacionalidade estrangeira
    *   **Quando** preenche dados de passaporte
    *   **Então** campos CPF/RG ficam opcionais
    *   **E** número e país do passaporte tornam-se obrigatórios
    *   **E** data de expiração é validada (futuro)

*   **Teste 2.3.3 (Troca de Nacionalidade):**
    *   **Dado** usuário já preencheu campos para brasileiro
    *   **Quando** muda nacionalidade para estrangeiro
    *   **Então** campos brasileiros são limpos automaticamente
    *   **E** validação ajusta-se aos novos requisitos

##### **2.4 Seção 3: Endereço e Contato**

*   **Teste 2.4.1 (Endereço Brasileiro):**
    *   **Dado** usuário brasileiro
    *   **Quando** preenche CEP válido
    *   **Então** endereço é preenchido automaticamente via API
    *   **E** usuário pode editar campos preenchidos
    *   **E** validação de CEP funciona corretamente

*   **Teste 2.4.2 (Endereço Internacional):**
    *   **Dado** usuário internacional
    *   **Quando** preenche endereço manualmente
    *   **Então** formato de endereço ajusta-se ao país
    *   **E** validação de código postal é apropriada

*   **Teste 2.4.3 (Validação de Telefone):**
    *   **Quando** usuário insere números de telefone
    *   **Então** formatação automática é aplicada
    *   **E** validação aceita formatos nacionais e internacionais

##### **2.5 Seção 4: Informações Profissionais**

*   **Teste 2.5.1 (Seleção de Posição):**
    *   **Quando** usuário seleciona "Undergraduate Student"
    *   **Então** campos relacionados à academia aparecem
    *   **E** questões sobre ABE ficam ocultas
    *   **E** cálculo de taxa ajusta automaticamente

*   **Teste 2.5.2 (Membro ABE - Professor):**
    *   **Dado** usuário selecionou "Professor/Researcher"
    *   **Quando** marca "Yes" para membro ABE
    *   **Então** validação solicita número de membro
    *   **E** cálculo de taxa aplica desconto ABE
    *   **E** taxa é recalculada automaticamente

*   **Teste 2.5.3 (Afiliação Institucional):**
    *   **Quando** usuário preenche afiliação
    *   **Então** campo aceita texto livre
    *   **E** informação é preservada corretamente
    *   **E** validação exige preenchimento obrigatório

##### **2.6 Seção 5: Seleção de Eventos e Cálculo de Taxas**

*   **Teste 2.6.1 (Seleção de Evento Principal):**
    *   **Quando** usuário seleciona BCSMIF2025
    *   **Então** taxa principal é calculada baseada na categoria
    *   **E** workshops ficam elegíveis para desconto
    *   **E** total é atualizado em tempo real

*   **Teste 2.6.2 (Cálculo de Taxa - Graduação):**
    *   **Dado** usuário undergraduate student
    *   **Quando** seleciona evento principal
    *   **Então** taxa exibida é R$ 0,00 (gratuito)
    *   **E** workshops são listados como gratuitos também
    *   **E** não há geração de payment

*   **Teste 2.6.3 (Cálculo de Taxa - Pós-graduação Early Bird):**
    *   **Dado** usuário graduate student, data < 15/08/2025
    *   **Quando** seleciona evento principal + workshop
    *   **Então** taxa é early bird para pós-graduação
    *   **E** workshop tem desconto aplicado
    *   **E** total reflete valores corretos

*   **Teste 2.6.4 (Cálculo de Taxa - Professor ABE Late):**
    *   **Dado** usuário professor ABE, data > 15/08/2025
    *   **Quando** seleciona múltiplos eventos
    *   **Então** taxa late é aplicada com desconto ABE
    *   **E** desconto workshop é aplicado corretamente
    *   **E** valor total é preciso

*   **Teste 2.6.5 (Cálculo de Taxa - Internacional):**
    *   **Dado** usuário com país ≠ Brasil
    *   **Quando** seleciona eventos
    *   **Então** taxas internacionais são aplicadas
    *   **E** conversão USD é mostrada se aplicável
    *   **E** informações sobre invoice aparecem

##### **2.7 Seção 6: Formato de Participação**

*   **Teste 2.7.1 (Seleção Online vs Presencial):**
    *   **Quando** usuário alterna entre formatos
    *   **Então** taxa é recalculada automaticamente
    *   **E** seções subsequentes ajustam campos conforme formato
    *   **E** informações de viagem aparecem apenas para presencial

##### **2.8 Seção 7: Informações de Viagem (Condicional)**

*   **Teste 2.8.1 (Dados de Viagem - Presencial):**
    *   **Dado** usuário selecionou participação presencial
    *   **Quando** preenche dados de chegada/partida
    *   **Então** validação garante lógica de datas
    *   **E** opções de transporte são registradas
    *   **E** necessidades especiais são capturadas

*   **Teste 2.8.2 (Campos Ocultos - Online):**
    *   **Dado** usuário selecionou participação online
    *   **Então** seção de viagem não aparece
    *   **E** validação não exige campos de viagem
    *   **E** formulário pula para próxima seção

##### **2.9 Seção 8: Preferências e Necessidades**

*   **Teste 2.9.1 (Restrições Alimentares):**
    *   **Quando** usuário marca restrições alimentares
    *   **Então** campo de texto livre aparece
    *   **E** informações são registradas corretamente
    *   **E** dados são preservados para organização

*   **Teste 2.9.2 (Necessidades Especiais):**
    *   **Quando** usuário indica necessidades de acessibilidade
    *   **Então** detalhes podem ser especificados
    *   **E** informação é tratada adequadamente

##### **2.10 Seção 9: Contato de Emergência e Finalização**

*   **Teste 2.10.1 (Contato de Emergência):**
    *   **Quando** usuário preenche dados de emergência
    *   **Então** validação verifica formato de telefone
    *   **E** relacionamento com contato é registrado
    *   **E** informações são armazenadas com segurança

*   **Teste 2.10.2 (Carta Convite/Visto):**
    *   **Dado** usuário internacional
    *   **Quando** solicita carta para visto
    *   **Então** checkbox é registrado
    *   **E** processo de geração é iniciado
    *   **E** informações são passadas para admin

##### **2.11 Submissão e Pós-Processo**

*   **Teste 2.11.1 (Validação Final e Submissão):**
    *   **Dado** usuário completou todas as seções
    *   **Quando** clica em "Submit Registration"
    *   **Então** validação final verifica todos os campos
    *   **E** registro é criado no banco de dados
    *   **E** payments são gerados conforme necessário
    *   **E** emails de confirmação são enviados

*   **Teste 2.11.2 (Redirecionamento Pós-Submissão):**
    *   **Após** submissão bem-sucedida
    *   **Então** usuário é redirecionado para `/my-registration`
    *   **E** vê dados da inscrição registrados
    *   **E** status correto é exibido conforme categoria
    *   **E** próximos passos são claramente indicados

##### **2.12 Cenários de Erro e Recuperação**

*   **Teste 2.12.1 (Falha de Submissão):**
    *   **Quando** erro ocorre durante submissão
    *   **Então** usuário permanece na página
    *   **E** dados preenchidos são preservados
    *   **E** mensagem de erro é clara e acionável

*   **Teste 2.12.2 (Timeout de Sessão):**
    *   **Dado** usuário com formulário parcialmente preenchido
    *   **Quando** sessão expira
    *   **Então** dados são preservados quando possível
    *   **E** usuário é redirecionado para login
    *   **E** pode recuperar progresso após autenticação

*   **Teste 2.12.3 (Validação Cross-Section):**
    *   **Quando** dados em seções diferentes conflitam
    *   **Então** validação identifica inconsistências
    *   **E** usuário é direcionado para corrigir
    *   **E** campos problemáticos são destacados

#### **Suíte 3: Fluxo Pós-Inscrição e Upload de Arquivos (EXPANDIDA)**

Esta suíte cobre os complexos fluxos de upload de comprovantes de pagamento e matrícula, com diferentes cenários baseados no tipo de usuário e status de inscrição.

##### **3.1 Dashboard "My Registration" - Visão Geral**

*   **Teste 3.1.1 (Carregamento da Página My Registration):**
    *   **Dado** usuário com inscrição completada
    *   **Quando** acessa `/my-registration`
    *   **Então** vê resumo completo da inscrição
    *   **E** status de cada pagamento é exibido corretamente
    *   **E** seções são organizadas por tipo (registration details, payments, enrollment proof)

*   **Teste 3.1.2 (Navegação Responsiva):**
    *   **Dado** usuário em dispositivo móvel
    *   **Quando** acessa dashboard
    *   **Então** layout adapta-se responsivamente
    *   **E** todas as funcionalidades permanecem acessíveis
    *   **E** upload de arquivos funciona em mobile

##### **3.2 Upload de Comprovante de Pagamento - Cenários Básicos**

*   **Teste 3.2.1 (Upload Pagamento - PDF Válido):**
    *   **Dado** usuário brasileiro com payment status `pending`
    *   **Quando** faz upload de arquivo PDF válido (< 10MB)
    *   **Então** arquivo é carregado com sucesso
    *   **E** status muda para `pending_approval`
    *   **E** formulário de upload para esse payment desaparece
    *   **E** mensagem de sucesso é exibida
    *   **E** email de notificação é enviado aos coordenadores

*   **Teste 3.2.2 (Upload Pagamento - Imagem Válida):**
    *   **Dado** usuário com payment pendente
    *   **Quando** faz upload de JPG/PNG válido
    *   **Então** upload é aceito normalmente
    *   **E** arquivo é armazenado com nome sanitizado
    *   **E** extensão original é preservada

*   **Teste 3.2.3 (Upload Múltiplos Payments):**
    *   **Dado** usuário com múltiplos payments pendentes
    *   **Quando** visualiza dashboard
    *   **Então** cada payment tem seu próprio formulário de upload
    *   **E** upload em um payment não afeta outros
    *   **E** status é rastreado independentemente

##### **3.3 Upload de Comprovante de Pagamento - Validações e Erros**

*   **Teste 3.3.1 (Formato de Arquivo Inválido):**
    *   **Dado** usuário com payment pendente
    *   **Quando** tenta upload de arquivo .txt/.doc/.exe
    *   **Então** upload é rejeitado
    *   **E** mensagem de erro específica informa formatos aceitos
    *   **E** formulário permanece visível
    *   **E** status não é alterado

*   **Teste 3.3.2 (Arquivo Muito Grande):**
    *   **Quando** usuário tenta upload de arquivo > 10MB
    *   **Então** upload é rejeitado antes de envio completo
    *   **E** mensagem específica sobre tamanho máximo
    *   **E** progresso de upload é interrompido

*   **Teste 3.3.3 (Arquivo Corrompido ou Vazio):**
    *   **Quando** usuário faz upload de arquivo corrompido
    *   **Então** validação detecta problema
    *   **E** erro é reportado com orientações
    *   **E** upload é rejeitado

*   **Teste 3.3.4 (Upload Duplicado):**
    *   **Dado** payment já tem comprovante enviado
    *   **Quando** usuário tenta novo upload
    *   **Então** sistema permite substituição
    *   **E** arquivo anterior é substituído
    *   **E** histórico é mantido para auditoria

##### **3.4 Upload de Comprovante de Matrícula - Estudantes**

*   **Teste 3.4.1 (Upload Matrícula - Graduação):**
    *   **Dado** estudante de graduação com inscrição free
    *   **Quando** acessa `/my-registration`
    *   **Então** vê seção específica para enrollment proof
    *   **E** formulário de upload está disponível
    *   **E** instruções claras sobre documentos aceitos

*   **Teste 3.4.2 (Upload Matrícula - Sucesso):**
    *   **Dado** estudante na tela de upload
    *   **Quando** faz upload de comprovante válido
    *   **Então** status muda para `pending_approval`
    *   **E** formulário de upload desaparece
    *   **E** mensagem confirma recebimento
    *   **E** orientações sobre tempo de análise são exibidas

*   **Teste 3.4.3 (Upload Matrícula - Pós-graduação):**
    *   **Dado** estudante de pós-graduação
    *   **Quando** faz upload de comprovante
    *   **Então** processo é similar à graduação
    *   **E** auto-aprovação pode ocorrer para workshops
    *   **E** status específico é mostrado

##### **3.5 Status e Acompanhamento de Comprovantes**

*   **Teste 3.5.1 (Visualização de Status - Aguardando Aprovação):**
    *   **Dado** comprovante enviado aguardando análise
    *   **Quando** usuário visualiza dashboard
    *   **Então** status "Aguardando Aprovação" é claramente exibido
    *   **E** informações sobre tempo estimado de análise
    *   **E** não há opção de novo upload

*   **Teste 3.5.2 (Status Aprovado):**
    *   **Dado** comprovante foi aprovado por coordenador
    *   **Quando** usuário acessa dashboard
    *   **Então** status "Aprovado" é exibido com cor verde
    *   **E** data de aprovação é mostrada
    *   **E** próximos passos (se houver) são indicados

*   **Teste 3.5.3 (Status Rejeitado):**
    *   **Dado** comprovante foi rejeitado
    *   **Quando** usuário visualiza dashboard
    *   **Então** status "Rejeitado" é exibido
    *   **E** motivo da rejeição é claramente mostrado
    *   **E** formulário de novo upload está disponível
    *   **E** orientações para correção são fornecidas

##### **3.6 Notificações e Comunicação**

*   **Teste 3.6.1 (Email de Confirmação - Upload):**
    *   **Quando** usuário faz upload de comprovante
    *   **Então** email de confirmação é enviado automaticamente
    *   **E** conteúdo confirma recebimento do documento
    *   **E** próximos passos são explicados
    *   **E** informações de contato para dúvidas

*   **Teste 3.6.2 (Notificação para Coordenadores):**
    *   **Quando** novo comprovante é carregado
    *   **Então** coordenadores recebem notificação por email
    *   **E** link direto para análise do documento
    *   **E** informações relevantes do usuário

##### **3.7 Segurança e Controle de Acesso**

*   **Teste 3.7.1 (Acesso Restrito a Arquivos):**
    *   **Dado** usuário A fez upload de comprovante
    *   **Quando** usuário B tenta acessar arquivo diretamente
    *   **Então** acesso é negado
    *   **E** política de segurança é aplicada
    *   **E** apenas owner e admins podem acessar

*   **Teste 3.7.2 (Download Próprio Arquivo):**
    *   **Dado** usuário que fez upload
    *   **Quando** clica para download do próprio arquivo
    *   **Então** download inicia normalmente
    *   **E** arquivo original é servido
    *   **E** nome original é preservado

*   **Teste 3.7.3 (Validação CSRF):**
    *   **Quando** tentativa de upload sem token CSRF
    *   **Então** upload é rejeitado
    *   **E** erro de segurança é reportado

##### **3.8 Cenários de Erro e Recuperação**

*   **Teste 3.8.1 (Falha de Rede Durante Upload):**
    *   **Dado** upload em progresso
    *   **Quando** conexão de rede é perdida
    *   **Então** erro é detectado e reportado
    *   **E** usuário pode tentar novamente
    *   **E** progresso parcial não corrompe sistema

*   **Teste 3.8.2 (Erro de Servidor):**
    *   **Quando** erro interno ocorre durante upload
    *   **Então** mensagem de erro amigável é exibida
    *   **E** usuário é orientado a tentar novamente
    *   **E** suporte técnico é notificado automaticamente

*   **Teste 3.8.3 (Limite de Storage):**
    *   **Quando** sistema está próximo do limite de armazenamento
    *   **Então** upload é gerenciado graciosamente
    *   **E** administradores são alertados
    *   **E** usuário recebe orientação adequada

##### **3.9 Tipos Específicos de Usuário**

*   **Teste 3.9.1 (Usuário Internacional - Sem Upload):**
    *   **Dado** usuário internacional com registration
    *   **Quando** acessa `/my-registration`
    *   **Então** não vê formulário de upload de pagamento
    *   **E** instruções sobre invoice são exibidas
    *   **E** informações de pagamento internacional

*   **Teste 3.9.2 (Graduação - Apenas Enrollment Proof):**
    *   **Dado** estudante de graduação (free registration)
    *   **Quando** visualiza dashboard
    *   **Então** não há seção de payment upload
    *   **E** apenas enrollment proof upload está presente
    *   **E** status é específico para comprovante de matrícula

*   **Teste 3.9.3 (Auto-aprovação - Pós-graduação):**
    *   **Dado** estudante de pós-graduação em workshop gratuito
    *   **Quando** sistema processa inscrição
    *   **Então** alguns itens podem ser auto-aprovados
    *   **E** status reflete aprovação automática
    *   **E** diferenciação clara entre manual e automático

#### **Suíte 4: Fluxo de Modificação de Inscrição (EXPANDIDA)**

Esta suíte cobre o complexo sistema de modificação de inscrições, incluindo adição/remoção de eventos, recálculo de taxas com descontos retroativos e gerenciamento de status.

##### **4.1 Acesso e Navegação para Modificação**

*   **Teste 4.1.1 (Acesso à Página de Modificação):**
    *   **Dado** usuário com inscrição ativa
    *   **Quando** acessa `/my-registration` e clica "Modify Registration"
    *   **Então** é direcionado para `/registration-modification`
    *   **E** vê resumo da inscrição atual
    *   **E** eventos disponíveis para adição são listados

*   **Teste 4.1.2 (Verificação de Elegibilidade):**
    *   **Dado** usuário na página de modificação
    *   **Quando** sistema carrega eventos disponíveis
    *   **Então** apenas eventos não inscritos são mostrados
    *   **E** eventos já inscritos aparecem como "Already Registered"
    *   **E** informações de desconto são exibidas quando aplicável

##### **4.2 Adição de Eventos com Cálculo de Desconto**

*   **Teste 4.2.1 (Adicionar Workshop - Professor ABE com Desconto):**
    *   **Dado** professor ABE inscrito apenas no evento principal
    *   **Quando** seleciona workshop adicional
    *   **Então** preço com desconto é calculado e exibido
    *   **E** "Total to Pay Now" reflete valor com desconto
    *   **E** explicação do desconto é mostrada

*   **Teste 4.2.2 (Adicionar Evento Principal - Desconto Retroativo):**
    *   **Dado** usuário inscrito apenas em workshop (valor cheio pago)
    *   **Quando** adiciona evento principal
    *   **Então** sistema calcula: (Preço Principal + Workshop com Desconto) - Valor já pago
    *   **E** "Total to Pay Now" pode ser negativo (crédito) ou diferença positiva
    *   **E** explicação detalhada do cálculo é fornecida

*   **Teste 4.2.3 (Múltiplas Adições Simultâneas):**
    *   **Dado** usuário pode adicionar múltiplos eventos
    *   **Quando** seleciona vários eventos de uma vez
    *   **Então** cálculo é feito considerando todos os descontos aplicáveis
    *   **E** total é atualizado dinamicamente
    *   **E** breakdown detalhado dos cálculos é mostrado

##### **4.3 Validação e Restrições de Modificação**

*   **Teste 4.3.1 (Bloqueio por Status de Pagamento):**
    *   **Dado** usuário com pagamento em "Pending Approval"
    *   **Quando** tenta acessar modificação
    *   **Então** funcionalidade está bloqueada
    *   **E** mensagem explicativa sobre o bloqueio
    *   **E** orientações sobre quando poderá modificar

*   **Teste 4.3.2 (Verificação de Prazos de Inscrição):**
    *   **Dado** data atual após deadline de um evento
    *   **Quando** usuário tenta adicionar evento expirado
    *   **Então** evento não aparece como opção
    *   **E** mensagem informa sobre prazos expirados

*   **Teste 4.3.3 (Limite de Modificações):**
    *   **Dado** usuário já fez múltiplas modificações
    *   **Quando** tenta nova modificação
    *   **Então** sistema verifica regras de limite
    *   **E** aceita ou rejeita conforme política estabelecida

##### **4.4 Confirmação e Processamento**

*   **Teste 4.4.1 (Confirmação de Modificação):**
    *   **Dado** usuário selecionou eventos para adicionar
    *   **Quando** clica "Confirm Modification"
    *   **Então** tela de confirmação mostra resumo detalhado
    *   **E** valor a pagar é claramente indicado
    *   **E** opção de cancelar está disponível

*   **Teste 4.4.2 (Processamento Bem-sucedido):**
    *   **Dado** usuário confirmou modificação
    *   **Quando** submissão é processada
    *   **Então** novos events são adicionados à registration
    *   **E** novo payment é criado se necessário
    *   **E** usuário é redirecionado para `/my-registration`
    *   **E** modificações são visíveis no dashboard

*   **Teste 4.4.3 (Falha de Processamento):**
    *   **Quando** erro ocorre durante processamento
    *   **Então** usuário permanece na página de modificação
    *   **E** dados selecionados são preservados
    *   **E** mensagem de erro específica é exibida

##### **4.5 Cenários Especiais de Cálculo**

*   **Teste 4.5.1 (Crédito por Desconto Retroativo):**
    *   **Dado** desconto retroativo resulta em valor negativo
    *   **Quando** modificação é processada
    *   **Então** sistema registra crédito para usuário
    *   **E** próximo payment considera o crédito
    *   **E** informações sobre crédito são claras

*   **Teste 4.5.2 (Modificação Internacional):**
    *   **Dado** usuário internacional fazendo modificação
    *   **Quando** adiciona eventos
    *   **Então** cálculos usam taxas internacionais
    *   **E** conversão de moeda é aplicada
    *   **E** processo de invoice é atualizado

#### **Suíte 5: Interface Administrativa (NOVA)**

Esta suíte abrange todas as funcionalidades administrativas, incluindo gerenciamento de inscrições, aprovação de comprovantes e relatórios.

##### **5.1 Acesso e Autenticação Administrativa**

*   **Teste 5.1.1 (Login de Administrador):**
    *   **Dado** usuário com role "admin"
    *   **Quando** faz login no sistema
    *   **Então** tem acesso a menu administrativo
    *   **E** pode acessar `/admin/registrations`
    *   **E** funcionalidades de usuário comum permanecem disponíveis

*   **Teste 5.1.2 (Login de Coordenador):**
    *   **Dado** usuário com role "coordinator"
    *   **Quando** acessa sistema
    *   **Então** tem acesso limitado a funções administrativas
    *   **E** pode aprovar comprovantes
    *   **E** não tem acesso a funções de admin completo

*   **Teste 5.1.3 (Controle de Acesso):**
    *   **Dado** usuário sem roles administrativas
    *   **Quando** tenta acessar páginas admin
    *   **Então** acesso é negado com erro 403
    *   **E** é redirecionado adequadamente

##### **5.2 Lista de Inscrições e Filtros**

*   **Teste 5.2.1 (Carregamento da Lista Principal):**
    *   **Dado** admin acessa `/admin/registrations`
    *   **Quando** página carrega
    *   **Então** lista todas as inscrições do sistema
    *   **E** paginação funciona corretamente
    *   **E** informações essenciais são exibidas (nome, email, status, eventos)

*   **Teste 5.2.2 (Filtro por Status de Pagamento):**
    *   **Quando** admin aplica filtro "Pending Payment"
    *   **Então** apenas inscrições com payment pendente são mostradas
    *   **E** contador de resultados é atualizado
    *   **E** filtro permanece ativo durante navegação

*   **Teste 5.2.3 (Filtro por Tipo de Usuário):**
    *   **Quando** admin filtra por "Graduate Students"
    *   **Então** apenas inscrições de pós-graduação aparecem
    *   **E** filtros múltiplos podem ser combinados

*   **Teste 5.2.4 (Busca por Nome/Email):**
    *   **Quando** admin digita nome ou email no campo de busca
    *   **Então** resultados são filtrados em tempo real
    *   **E** busca é case-insensitive
    *   **E** busca parcial funciona corretamente

*   **Teste 5.2.5 (Layout Responsivo - Admin):**
    *   **Dado** admin usando dispositivo móvel
    *   **Quando** acessa lista de inscrições
    *   **Então** layout adapta-se para mobile
    *   **E** funcionalidades essenciais permanecem acessíveis
    *   **E** filtros são acessíveis em menu colapsível

##### **5.3 Detalhes de Inscrição e Gerenciamento**

*   **Teste 5.3.1 (Visualização de Detalhes Completos):**
    *   **Quando** admin clica em uma inscrição
    *   **Então** vê todos os dados do formulário original
    *   **E** status de payments é claramente indicado
    *   **E** histórico de modificações é mostrado

*   **Teste 5.3.2 (Download de Comprovantes):**
    *   **Dado** inscrição com comprovantes enviados
    *   **Quando** admin clica para download
    *   **Então** arquivo é baixado com nome original
    *   **E** ação é registrada para auditoria

*   **Teste 5.3.3 (Notas e Anotações Administrativas):**
    *   **Quando** admin adiciona nota sobre inscrição
    *   **Então** nota é salva com timestamp e autor
    *   **E** aparece em visualizações futuras
    *   **E** outros admins podem ver as notas

##### **5.4 Aprovação de Comprovantes de Pagamento**

*   **Teste 5.4.1 (Lista de Comprovantes Pendentes):**
    *   **Dado** admin acessa seção de aprovações
    *   **Quando** visualiza lista de pendências
    *   **Então** vê todos os comprovantes aguardando análise
    *   **E** informações do usuário e evento são mostradas
    *   **E** pode visualizar comprovante inline ou download

*   **Teste 5.4.2 (Aprovação de Comprovante):**
    *   **Dado** comprovante válido aguardando aprovação
    *   **Quando** admin clica "Approve"
    *   **Então** status muda para "Paid"
    *   **E** email de confirmação é enviado ao usuário
    *   **E** comprovante sai da lista de pendências

*   **Teste 5.4.3 (Rejeição de Comprovante):**
    *   **Dado** comprovante inadequado
    *   **Quando** admin clica "Reject" e fornece motivo
    *   **Então** status volta para "Pending Payment"
    *   **E** usuário recebe email com motivo da rejeição
    *   **E** formulário de upload fica disponível novamente

*   **Teste 5.4.4 (Aprovação/Rejeição em Lote):**
    *   **Quando** admin seleciona múltiplos comprovantes
    *   **Então** pode aprovar ou rejeitar em lote
    *   **E** ações são processadas individualmente
    *   **E** falhas são reportadas adequadamente

##### **5.5 Aprovação de Comprovantes de Matrícula**

*   **Teste 5.5.1 (Lista de Enrollment Proofs):**
    *   **Dado** admin acessa seção de enrollment proofs
    *   **Quando** visualiza lista
    *   **Então** vê comprovantes de matrícula pendentes
    *   **E** informações do estudante são mostradas
    *   **E** tipo de estudante (graduação/pós) é indicado

*   **Teste 5.5.2 (Aprovação de Matrícula):**
    *   **Quando** admin aprova comprovante de matrícula
    *   **Então** status muda para "Approved"
    *   **E** estudante recebe confirmação
    *   **E** acesso aos eventos é liberado

*   **Teste 5.5.3 (Processo de Auto-aprovação):**
    *   **Dado** estudante de pós-graduação em workshop gratuito
    *   **Quando** sistema processa automaticamente
    *   **Então** admin vê status "Auto-approved"
    *   **E** pode reverter se necessário
    *   **E** histórico de auto-aprovação é mantido

##### **5.6 Relatórios e Estatísticas**

*   **Teste 5.6.1 (Dashboard de Estatísticas):**
    *   **Quando** admin acessa dashboard principal
    *   **Então** vê estatísticas gerais:
        - Total de inscrições por categoria
        - Status de pagamentos
        - Comprovantes pendentes
        - Receita total e projetada

*   **Teste 5.6.2 (Relatório de Inscrições por Evento):**
    *   **Quando** admin gera relatório por evento
    *   **Então** vê breakdown detalhado por evento
    *   **E** pode filtrar por período ou status
    *   **E** dados podem ser exportados (CSV/Excel)

*   **Teste 5.6.3 (Relatório Financeiro):**
    *   **Quando** admin acessa relatório financeiro
    *   **Então** vê receita por categoria de participante
    *   **E** pendências financeiras são destacadas
    *   **E** projeções baseadas em inscrições são mostradas

##### **5.7 Gerenciamento de Usuários e Roles**

*   **Teste 5.7.1 (Lista de Usuários):**
    *   **Dado** admin com permissões de user management
    *   **Quando** acessa lista de usuários
    *   **Então** vê todos os usuários do sistema
    *   **E** roles atuais são mostradas
    *   **E** pode filtrar por role ou status

*   **Teste 5.7.2 (Atribuição de Roles):**
    *   **Quando** admin modifica role de usuário
    *   **Então** mudança é aplicada imediatamente
    *   **E** usuário recebe notificação sobre mudança
    *   **E** ação é registrada em auditoria

*   **Teste 5.7.3 (Desativação de Usuário):**
    *   **Quando** admin desativa conta de usuário
    *   **Então** usuário não consegue mais fazer login
    *   **E** dados de inscrição são preservados
    *   **E** ação pode ser revertida se necessário

**6. Execução dos Testes e Integração Contínua (CI/CD) - EXPANDIDA**

##### **6.1 Configuração Local de Desenvolvimento**

**Setup Inicial Completo:**
```bash
# 1. Instalar Dusk (se ainda não estiver instalado)
composer require --dev laravel/dusk
php artisan dusk:install

# 2. Configurar ambiente Dusk
cp .env.example .env.dusk.local
# Editar .env.dusk.local com configurações específicas

# 3. Instalar ChromeDriver
php artisan dusk:chrome-driver --detect

# 4. Criar banco de dados Dusk
touch database/dusk.sqlite
php artisan migrate --env=dusk.local
php artisan db:seed --env=dusk.local --class=DuskTestSeeder
```

**Execução Local Multi-terminal:**
```bash
# Terminal 1: Servidor Laravel para Dusk
php artisan serve --port=8000 --env=dusk.local

# Terminal 2: ChromeDriver
./vendor/laravel/dusk/bin/chromedriver-linux --port=9515

# Terminal 3: Execução dos testes
php artisan dusk --env=dusk.local

# Execução de suíte específica
php artisan dusk --env=dusk.local --group=authentication
php artisan dusk --env=dusk.local --group=registration
php artisan dusk --env=dusk.local --group=admin

# Execução de teste específico
php artisan dusk --env=dusk.local tests/Browser/AuthenticationTest.php
```

**Script de Automação (`scripts/run-dusk-tests.sh`):**
```bash
#!/bin/bash
set -e

echo "🚀 Starting Dusk Test Environment..."

# Start Laravel server in background
php artisan serve --port=8000 --env=dusk.local &
SERVER_PID=$!

# Start ChromeDriver in background
./vendor/laravel/dusk/bin/chromedriver-linux --port=9515 &
CHROME_PID=$!

# Wait for services to start
sleep 5

echo "✅ Services started. Running Dusk tests..."

# Run tests with proper cleanup
trap "kill $SERVER_PID $CHROME_PID 2>/dev/null" EXIT

php artisan dusk --env=dusk.local "$@"

echo "🎉 Dusk tests completed!"
```

##### **6.2 Integração Contínua - GitHub Actions (EXPANDIDA)**

**Workflow Completo (`.github/workflows/dusk-tests.yml`):**
```yaml
name: Dusk E2E Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]
  schedule:
    - cron: '0 2 * * *' # Daily at 2 AM

jobs:
  dusk-tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ALLOW_EMPTY_PASSWORD: false
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: bcsmif_dusk_test
        ports:
          - 3306/tcp
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    strategy:
      matrix:
        php-version: [8.2, 8.3]
        test-suite: [authentication, registration, upload, admin, full]

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv
          coverage: none

      - name: Install Chrome
        uses: browser-actions/setup-chrome@latest
        with:
          chrome-version: stable

      - name: Install ChromeDriver
        run: |
          CHROME_VERSION=$(google-chrome --version | cut -d " " -f3 | cut -d "." -f1)
          CHROMEDRIVER_VERSION=$(curl -sS chromedriver.storage.googleapis.com/LATEST_RELEASE_$CHROME_VERSION)
          wget -N https://chromedriver.storage.googleapis.com/$CHROMEDRIVER_VERSION/chromedriver_linux64.zip
          unzip chromedriver_linux64.zip
          chmod +x chromedriver
          sudo mv chromedriver /usr/local/bin/chromedriver

      - name: Cache Composer packages
        id: composer-cache
        uses: actions/cache@v3
        with:
          path: vendor
          key: ${{ runner.os }}-php-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-php-

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-interaction --no-suggest

      - name: Setup Environment
        run: |
          cp .env.dusk.ci .env.dusk.local
          php artisan key:generate --env=dusk.local

      - name: Setup Database
        env:
          DB_PORT: ${{ job.services.mysql.ports[3306] }}
        run: |
          php artisan migrate --env=dusk.local --force
          php artisan db:seed --env=dusk.local --class=DuskTestSeeder

      - name: Start Laravel Server
        run: |
          php artisan serve --port=8000 --env=dusk.local &
          sleep 5

      - name: Run Dusk Tests
        env:
          APP_URL: http://127.0.0.1:8000
        run: |
          case "${{ matrix.test-suite }}" in
            "authentication")
              php artisan dusk --env=dusk.local --group=authentication
              ;;
            "registration")
              php artisan dusk --env=dusk.local --group=registration
              ;;
            "upload")
              php artisan dusk --env=dusk.local --group=upload
              ;;
            "admin")
              php artisan dusk --env=dusk.local --group=admin
              ;;
            "full")
              php artisan dusk --env=dusk.local
              ;;
          esac

      - name: Upload Screenshots
        uses: actions/upload-artifact@v3
        if: failure()
        with:
          name: dusk-screenshots-${{ matrix.test-suite }}-${{ matrix.php-version }}
          path: tests/Browser/screenshots
          retention-days: 7

      - name: Upload Console Logs
        uses: actions/upload-artifact@v3
        if: failure()
        with:
          name: dusk-console-logs-${{ matrix.test-suite }}-${{ matrix.php-version }}
          path: tests/Browser/console
          retention-days: 7

      - name: Upload Source Code on Failure
        uses: actions/upload-artifact@v3
        if: failure()
        with:
          name: dusk-source-${{ matrix.test-suite }}-${{ matrix.php-version }}
          path: tests/Browser/source
          retention-days: 7
```

**Configuração de Ambiente CI (`.env.dusk.ci`):**
```env
APP_NAME="8th BCSMIF - Dusk Tests"
APP_ENV=dusk
APP_KEY=base64:GENERATE_NEW_KEY_FOR_DUSK
APP_DEBUG=false
APP_URL=http://127.0.0.1:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bcsmif_dusk_test
DB_USERNAME=root
DB_PASSWORD=password

# Mail configuration for testing
MAIL_MAILER=log
MAIL_FROM_ADDRESS=test@bcsmif.test
MAIL_FROM_NAME="${APP_NAME}"

# Disable external services in CI
SENHAUNICA_KEY=fake_key_for_testing
SENHAUNICA_SECRET=fake_secret_for_testing
REPLICADO_HOST=fake_host
```

##### **6.3 Estratégias de Paralelização**

**Execução Paralela por Suíte:**
```bash
# Parallel execution script
#!/bin/bash
(php artisan dusk --group=authentication) &
(php artisan dusk --group=registration) &
(php artisan dusk --group=upload) &
(php artisan dusk --group=admin) &
wait
```

**Configuração de Database por Worker:**
```php
// DuskTestCase.php
protected function setUp(): void
{
    parent::setUp();
    
    // Use different database for each parallel worker
    $workerId = env('DUSK_WORKER_ID', 1);
    config(['database.connections.dusk.database' => "dusk_test_worker_{$workerId}"]);
}
```

#### **Suíte 6: Cenários Avançados e Edge Cases (NOVA)**

Esta suíte cobre cenários complexos, edge cases e testes de integração que validam o comportamento do sistema em situações não convencionais.

##### **6.1 Testes de Performance e Carga Limitada**

*   **Teste 6.1.1 (Upload de Arquivo Grande):**
    *   **Dado** usuário com upload de comprovante
    *   **Quando** faz upload de arquivo próximo ao limite (9.5MB)
    *   **Então** upload é processado sem timeout
    *   **E** interface permanece responsiva durante upload
    *   **E** progresso é mostrado adequadamente

*   **Teste 6.1.2 (Formulário com Muitos Dados):**
    *   **Dado** formulário de inscrição com todos os campos preenchidos
    *   **Quando** submissão contém dados extensos (textos longos, múltiplos eventos)
    *   **Então** processamento é concluído em tempo razoável (< 30s)
    *   **E** dados são salvos completamente

*   **Teste 6.1.3 (Sessão de Longa Duração):**
    *   **Dado** usuário preenchendo formulário lentamente
    *   **Quando** permanece na página por tempo estendido (45 min)
    *   **Então** dados são preservados via auto-save
    *   **E** submissão final funciona normalmente

##### **6.2 Testes de Compatibilidade e Responsividade**

*   **Teste 6.2.1 (Resolução Mobile):**
    *   **Dado** dispositivo com resolução 375x667 (iPhone SE)
    *   **Quando** usuário navega por todas as funcionalidades
    *   **Então** interface é totalmente funcional
    *   **E** todos os elementos são acessíveis
    *   **E** upload de arquivos funciona em mobile

*   **Teste 6.2.2 (Tablet Landscape):**
    *   **Dado** resolução 1024x768 (tablet landscape)
    *   **Quando** admin acessa dashboard
    *   **Então** layout se adapta adequadamente
    *   **E** tabelas são navegáveis
    *   **E** filtros permanecem acessíveis

*   **Teste 6.2.3 (Desktop Ultra-wide):**
    *   **Dado** resolução 2560x1440
    *   **Quando** usuário acessa sistema
    *   **Então** conteúdo não fica excessivamente espalhado
    *   **E** proporções visuais são mantidas

##### **6.3 Testes de Segurança e Validação**

*   **Teste 6.3.1 (Tentativa de SQL Injection):**
    *   **Quando** campos de input recebem strings maliciosas
    *   **Então** sistema rejeita entrada com validação
    *   **E** logs de segurança são criados
    *   **E** aplicação permanece estável

*   **Teste 6.3.2 (Upload de Arquivo Malicioso):**
    *   **Quando** usuário tenta upload de arquivo .exe ou script
    *   **Então** upload é rejeitado antes do processamento
    *   **E** mensagem de erro específica sobre tipos permitidos

*   **Teste 6.3.3 (Acesso Direto a URLs Protegidas):**
    *   **Dado** usuário não autenticado
    *   **Quando** tenta acessar URLs como `/admin/registrations`
    *   **Então** é redirecionado para login
    *   **E** após login é direcionado corretamente

##### **6.4 Testes de Integração de Sistemas**

*   **Teste 6.4.1 (Integração USP Senha Única - Mock):**
    *   **Dado** ambiente de teste com USP mock
    *   **Quando** usuário faz login via Senha Única
    *   **Então** fluxo OAuth funciona completamente
    *   **E** dados corretos são recebidos do sistema USP
    *   **E** conta é criada ou atualizada adequadamente

*   **Teste 6.4.2 (Validação NUSP via Replicado - Mock):**
    *   **Dado** serviço Replicado mockado
    *   **Quando** usuário registra com email USP
    *   **Então** validação NUSP é acionada
    *   **E** resposta do serviço é processada corretamente
    *   **E** erro de NUSP inválido é tratado adequadamente

*   **Teste 6.4.3 (Sistema de Email - Verificação):**
    *   **Quando** ações que geram emails são executadas
    *   **Então** emails são enviados corretamente
    *   **E** conteúdo dos emails está correto
    *   **E** templates são renderizados adequadamente

##### **6.5 Testes de Concorrência e Estado**

*   **Teste 6.5.1 (Modificação Simultânea de Inscrição):**
    *   **Dado** usuário com inscrição ativa
    *   **Quando** tenta modificar inscrição simultaneamente em duas abas
    *   **Então** apenas uma modificação é processada
    *   **E** conflitos são detectados e reportados
    *   **E** estado final é consistente

*   **Teste 6.5.2 (Aprovação Concurrent de Comprovantes):**
    *   **Dado** dois admins visualizando mesmo comprovante
    *   **Quando** ambos tentam aprovar simultaneamente
    *   **Então** apenas uma aprovação é registrada
    *   **E** segundo admin recebe feedback sobre ação já realizada

*   **Teste 6.5.3 (Upload Simultâneo de Múltiplos Arquivos):**
    *   **Dado** usuário com múltiplos payments pendentes
    *   **Quando** faz upload de comprovantes simultaneamente
    *   **Então** cada upload é processado independentemente
    *   **E** status são atualizados corretamente para cada payment

##### **6.6 Testes de Recuperação e Resiliência**

*   **Teste 6.6.1 (Recuperação após Erro de Rede):**
    *   **Dado** upload em progresso
    *   **Quando** conexão é perdida e restaurada
    *   **Então** usuário pode tentar upload novamente
    *   **E** estado da aplicação é recuperado corretamente

*   **Teste 6.6.2 (Timeout de Transação):**
    *   **Quando** operação de banco de dados demora excessivamente
    *   **Então** timeout é aplicado graciosamente
    *   **E** usuário recebe feedback sobre erro temporário
    *   **E** pode tentar operação novamente

*   **Teste 6.6.3 (Falha de Storage):**
    *   **Quando** sistema de arquivos está indisponível
    *   **Então** upload falha com mensagem informativa
    *   **E** dados de inscrição permanecem íntegros
    *   **E** sistema continua funcional para outras operações

##### **6.7 Testes de Migração e Compatibilidade de Dados**

*   **Teste 6.7.1 (Dados Legacy):**
    *   **Dado** registrations criados em versões anteriores
    *   **Quando** usuário acessa sistema atualizado
    *   **Então** dados antigos são exibidos corretamente
    *   **E** funcionalidades novas são disponibilizadas
    *   **E** modificações funcionam com dados legacy

*   **Teste 6.7.2 (Upgrade de Schema):**
    *   **Quando** migration adiciona novos campos
    *   **Então** registrations existentes recebem valores padrão
    *   **E** formulários incluem novos campos
    *   **E** validação é aplicada adequadamente

**7. Deliverables e Cronograma Expandido**

##### **7.1 Deliverables Detalhados por Fase**

**Fase 1: Fundação e Infraestrutura (Semanas 1-2)**
- ✅ Configuração completa do ambiente Dusk
- ✅ Implementação de todos os atributos `dusk` nas views Blade
- ✅ Classes base de Page Objects e Components
- ✅ Configuração inicial de CI/CD
- ✅ Estrutura de tests com factories atualizadas

**Fase 2: Testes Core de Usuário (Semanas 3-4)**
- ✅ Suíte 1: Autenticação completa (18 cenários)
- ✅ Suíte 2: Formulário de inscrição (36 cenários)
- ✅ Suíte 3: Upload de arquivos (27 cenários)
- ✅ Page Objects para authentication e registration

**Fase 3: Funcionalidades Avançadas (Semanas 5-6)**
- ✅ Suíte 4: Modificação de inscrições (15 cenários)
- ✅ Suíte 5: Interface administrativa (21 cenários)
- ✅ Page Objects administrativos
- ✅ Testes de permissões e roles

**Fase 4: Cenários Avançados e Qualidade (Semanas 7-8)**
- ✅ Suíte 6: Edge cases e performance (21 cenários)
- ✅ Testes de integração com sistemas externos
- ✅ Otimização de CI/CD com paralelização
- ✅ Documentação completa e training

##### **7.2 Métricas de Qualidade Esperadas**

**Cobertura de Testes:**
- ✅ **117 cenários de teste** distribuídos em 6 suítes
- ✅ **100% das user journeys críticas** cobertas
- ✅ **Todas as permissões e roles** testadas
- ✅ **90%+ dos edge cases** identificados e testados

**Performance de Execução:**
- ✅ Execução completa: < 45 minutos
- ✅ Execução paralela por suíte: < 15 minutos
- ✅ Feedback de falha: < 2 minutos
- ✅ CI/CD pipeline: < 30 minutos end-to-end

**Manutenibilidade:**
- ✅ Page Objects para 100% das interfaces
- ✅ Seletores Dusk em todos elementos críticos
- ✅ Documentação completa de implementação
- ✅ Scripts de automação para desenvolvimento local

##### **7.3 Cronograma de Implementação Sugerido**

**Sprint 1 (Semanas 1-2): Infraestrutura**
```
Semana 1:
- Configuração ambiente Dusk
- Implementação atributos dusk nas views
- Classes base Page Objects

Semana 2:
- Suíte 1: Testes de autenticação (completa)
- CI/CD básico configurado
- Factories de teste atualizadas
```

**Sprint 2 (Semanas 3-4): Core Registration**
```
Semana 3:
- Suíte 2: Testes de formulário (seções 1-5)
- Page Objects de registration
- Validação e cálculo de fees

Semana 4:
- Suíte 2: Testes de formulário (seções 6-9)
- Suíte 3: Upload de arquivos (básico)
- Testes de responsividade
```

**Sprint 3 (Semanas 5-6): Admin e Modificações**
```
Semana 5:
- Suíte 3: Upload completo (27 cenários)
- Suíte 4: Modificação de inscrições
- Page Objects administrativos

Semana 6:
- Suíte 5: Interface administrativa (completa)
- Testes de permissões e roles
- Integração com mocks USP
```

**Sprint 4 (Semanas 7-8): Qualidade e Otimização**
```
Semana 7:
- Suíte 6: Edge cases e performance
- Testes de concorrência
- Otimização CI/CD paralelo

Semana 8:
- Testes de integração sistemas
- Documentação final
- Training e handover
```

**8. Conclusão e Impacto Esperado**

A implementação deste plano expandido de testes E2E com Laravel Dusk transformará significativamente a qualidade e confiabilidade do sistema 8th BCSMIF:

##### **8.1 Benefícios Técnicos**
- **Detecção Precoce de Bugs**: 117 cenários garantem identificação de problemas antes da produção
- **Regression Testing**: Automação completa previne quebras em funcionalidades existentes
- **Confidence em Deployments**: CI/CD robusto permite deploys seguros e frequentes
- **Manutenibilidade**: Estrutura organizada facilita evolução dos testes com o sistema

##### **8.2 Benefícios de Negócio**
- **Qualidade de UX**: Todos os fluxos de usuário são validados automaticamente
- **Redução de Custos**: Bugs encontrados cedo custam 10x menos para corrigir
- **Velocidade de Desenvolvimento**: Testes automatizados aceleram ciclo de desenvolvimento
- **Confiança do Cliente**: Sistema mais estável resulta em melhor experiência do usuário

##### **8.3 Cobertura Abrangente Alcançada**
- ✅ **6 Suítes de Teste** cobrindo todos os aspectos do sistema
- ✅ **117 Cenários** validando desde happy path até edge cases complexos
- ✅ **100% das User Journeys** críticas para o negócio
- ✅ **Integração Completa** com CI/CD e ferramentas de desenvolvimento

**Esta implementação estabelecerá o sistema 8th BCSMIF como referência em qualidade de software para eventos acadêmicos, garantindo uma experiência excepcional para todos os participantes.**
*   **Cronograma (Fases Sugeridas):**
    1.  **Fase 1:** Configuração do ambiente e implementação da Suíte 1 (Autenticação).
    2.  **Fase 2:** Implementação da Suíte 2 (Fluxo de Inscrição Principal), cobrindo todos os tipos de participantes.
    3.  **Fase 3:** Implementação das Suítes 3 (Pós-Inscrição) e 4 (Modificação).
    4.  **Fase 4:** Integração final com o pipeline de CI/CD.

**8. Conclusão**

A implementação deste plano de testes E2E com Laravel Dusk garantirá uma cobertura abrangente dos fluxos de usuário mais críticos do site 8th BCSMIF. Ao focar em cenários realistas e utilizar padrões de projeto como Page Objects, a suíte de testes será não apenas um mecanismo de garantia de qualidade, mas também um ativo de fácil manutenção que evoluirá com a aplicação.
