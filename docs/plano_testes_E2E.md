### **Plano de Implementação de Testes E2E com Laravel Dusk para o Site 8th BCSMIF**

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

#### **Suíte 1: Autenticação e Contas de Usuário**

*   **Teste 1.1 (Login Local - Sucesso):**
    *   **Dado** um usuário com conta local e email verificado.
    *   **Quando** ele preenche o formulário em `/login/local` com credenciais corretas e submete.
    *   **Então** ele é redirecionado para a página `/my-registration`.
*   **Teste 1.2 (Login Local - Falha):**
    *   **Dado** um usuário com conta local.
    *   **Quando** ele preenche o formulário com a senha incorreta.
    *   **Então** ele permanece na página de login e vê uma mensagem de erro de autenticação.
*   **Teste 1.3 (Registro - Usuário Externo):**
    *   **Quando** um novo usuário preenche o formulário de registro com um e-mail não-USP.
    *   **Então** o campo "Número USP" não aparece.
    *   **E** após submeter, ele é logado e redirecionado para a tela de verificação de e-mail.
*   **Teste 1.4 (Registro - Usuário USP):**
    *   **Quando** um novo usuário preenche o formulário com um e-mail `@usp.br`.
    *   **Então** o campo "Número USP" aparece e é obrigatório.
*   **Teste 1.5 (Reset de Senha):**
    *   **Dado** um usuário com conta local.
    *   **Quando** ele solicita um link de reset de senha e segue o fluxo.
    *   **Então** ele consegue redefinir sua senha com sucesso e fazer login com a nova senha.

#### **Suíte 2: Fluxo Principal de Inscrição (ÊNFASE)**

Esta suíte deve ser dividida por categoria de participante, pois os fluxos e taxas variam significativamente.

*   **Teste 2.1 (Inscrição - Aluno de Graduação):**
    *   **Dado** um usuário logado e verificado, que é aluno de graduação.
    *   **Quando** ele preenche o formulário de inscrição, selecionando o evento principal **BCSMIF2025**.
    *   **Então** a taxa total calculada exibida na tela é **R$ 0,00**.
    *   **E** após submeter, ele é redirecionado para a página `/my-registration`.
    *   **E** na página `/my-registration`, ele vê um status de "Isento de Taxa" (`free`) e um formulário para **upload de comprovante de MATRÍCULA**, não de pagamento.
*   **Teste 2.2 (Inscrição - Aluno de Pós-Graduação):**
    *   **Dado** um usuário logado e verificado, que é aluno de pós-graduação.
    *   **Quando** ele seleciona o evento principal **BCSMIF2025** e o workshop **RAA2025**.
    *   **Então** a taxa total calculada deve ser a do evento principal apenas (workshop é gratuito).
    *   **E** após submeter, na página `/my-registration`, ele vê um status "Pagamento Pendente" (`pending_payment`) e um formulário para **upload de comprovante de PAGAMENTO**.
*   **Teste 2.3 (Inscrição - Professor Membro ABE - Early Bird):**
    *   **Dado** um usuário logado e verificado, que é professor membro da ABE, e a data atual está **antes** de 15/08/2025.
    *   **Quando** ele seleciona apenas o workshop **WDA2025**.
    *   **Então** a taxa calculada é o valor cheio do workshop (R$ 250,00).
    *   **E quando** ele também seleciona o evento principal **BCSMIF2025**.
    *   **Então** a taxa total é recalculada para (Taxa Early Bird Prof. ABE + Taxa Workshop com desconto).
*   **Teste 2.4 (Inscrição - Profissional Internacional - Late):**
    *   **Dado** um usuário logado e verificado, com país de documento diferente de "Brasil", e a data atual está **após** 15/08/2025.
    *   **Quando** ele preenche o formulário de inscrição para o evento principal.
    *   **Então** a taxa calculada é o valor "late" para profissional.
    *   **E** após submeter, na página `/my-registration`, ele vê o status "Pagamento Pendente" (`pending_payment`) e **NÃO** vê um formulário de upload de comprovante, mas sim uma mensagem informativa sobre a invoice.
*   **Teste 2.5 (Validação de Formulário):**
    *   **Quando** um usuário tenta submeter o formulário de inscrição com campos obrigatórios faltando ou com formatos inválidos (ex: CPF inválido, data de nascimento futura).
    *   **Então** a submissão falha e mensagens de erro de validação são exibidas abaixo dos campos correspondentes.

#### **Suíte 3: Fluxo Pós-Inscrição**

*   **Teste 3.1 (Upload de Comprovante de Pagamento - Sucesso):**
    *   **Dado** um usuário com uma inscrição com pagamento pendente.
    *   **Quando** ele acessa `/my-registration` e faz o upload de um arquivo válido (PDF/JPG).
    *   **Então** ele vê uma mensagem de sucesso, e o formulário de upload para aquele pagamento desaparece.
    *   **E** o status daquele pagamento é atualizado na página para "Aguardando Aprovação" (`pending_br_proof_approval`).
*   **Teste 3.2 (Upload de Comprovante de Matrícula - Sucesso):**
    *   **Dado** um aluno de graduação com inscrição gratuita.
    *   **Quando** ele faz o upload de seu comprovante de matrícula.
    *   **Então** ele vê uma mensagem de sucesso, e o formulário de upload desaparece.
    *   **E** o status do comprovante é atualizado na página.
*   **Teste 3.3 (Upload - Falha de Validação):**
    *   **Dado** um usuário com uma inscrição com pagamento pendente.
    *   **Quando** ele tenta fazer upload de um arquivo com formato inválido (ex: `.txt`) ou que excede o tamanho máximo.
    *   **Então** ele vê uma mensagem de erro de validação e o formulário permanece visível.

#### **Suíte 4: Fluxo de Modificação de Inscrição**

*   **Teste 4.1 (Adicionar Workshop com Desconto):**
    *   **Dado** um professor membro ABE já inscrito **apenas** no evento principal **BCSMIF2025**.
    *   **Quando** ele navega para a página de modificação e seleciona o workshop **RAA2025**.
    *   **Então** o "Total a Pagar Agora" na tela de modificação reflete o preço **com desconto** do workshop.
    *   **E** após confirmar, a página `/my-registration` exibe um novo pagamento pendente com este valor.
*   **Teste 4.2 (Adicionar Evento Principal com Desconto Retroativo):**
    *   **Dado** um profissional já inscrito e **pago** apenas pelo workshop **WDA2025** (valor cheio).
    *   **Quando** ele navega para a página de modificação e adiciona o evento principal **BCSMIF2025**.
    *   **Então** o "Total a Pagar Agora" deve ser (Taxa BCSMIF + Taxa Workshop com Desconto) - (Valor já pago pelo workshop cheio).
    *   **E** após confirmar, a página `/my-registration` exibe um novo pagamento pendente com o valor da diferença.
*   **Teste 4.3 (Adição Bloqueada):**
    *   **Dado** um usuário que já fez o upload de um comprovante e cujo pagamento está com status "Aguardando Aprovação".
    *   **Quando** ele tenta clicar no botão "Adicionar Eventos".
    *   **Então** o botão está desabilitado ou, ao clicar, exibe uma mensagem informativa explicando o bloqueio.

**6. Execução dos Testes e Integração Contínua (CI/CD)**

1.  **Execução Local:** Os testes **DEVEM** ser executáveis localmente via:
    ```bash
    # Em um terminal, iniciar o servidor de desenvolvimento para Dusk
    php artisan serve --port=8000 --env=dusk.local
    
    # Em outro terminal, iniciar o ChromeDriver
    ./vendor/laravel/dusk/bin/chromedriver-linux --port=9515 # (ou para seu SO)
    
    # Em um terceiro terminal, executar os testes
    php artisan dusk --env=dusk.local
    ```

2.  **Integração Contínua (GitHub Actions):**
    *   O workflow existente em `.github/workflows/laravel.yml` **DEVE** ser atualizado para incluir um job (ou steps) para a execução dos testes Dusk.
    *   O job **DEVE** incluir:
        *   Instalação do Google Chrome no runner.
        *   Criação e migração do banco de dados de teste Dusk.
        *   Instalação do ChromeDriver.
        *   Execução do servidor Artisan e do ChromeDriver em background.
        *   Execução do comando `php artisan dusk`.
        *   Upload dos artefatos de falha (screenshots e logs de console) se os testes falharem.

**7. Deliverables e Cronograma Sugerido**

*   **Deliverable:** Um Pull Request contendo a suíte de testes Dusk completa, incluindo as classes de Page/Component, as factories de teste atualizadas e a configuração de CI modificada.
*   **Cronograma (Fases Sugeridas):**
    1.  **Fase 1:** Configuração do ambiente e implementação da Suíte 1 (Autenticação).
    2.  **Fase 2:** Implementação da Suíte 2 (Fluxo de Inscrição Principal), cobrindo todos os tipos de participantes.
    3.  **Fase 3:** Implementação das Suítes 3 (Pós-Inscrição) e 4 (Modificação).
    4.  **Fase 4:** Integração final com o pipeline de CI/CD.

**8. Conclusão**

A implementação deste plano de testes E2E com Laravel Dusk garantirá uma cobertura abrangente dos fluxos de usuário mais críticos do site 8th BCSMIF. Ao focar em cenários realistas e utilizar padrões de projeto como Page Objects, a suíte de testes será não apenas um mecanismo de garantia de qualidade, mas também um ativo de fácil manutenção que evoluirá com a aplicação.
