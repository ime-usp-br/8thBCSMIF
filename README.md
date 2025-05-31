# Site de Inscrições do 8th BCSMIF

**Versão:** 0.1.0<br>
**Data:** 2025-05-30

[![Status da Build](https://github.com/ime-usp-br/8thBCSMIF/actions/workflows/laravel.yml/badge.svg)](https://github.com/ime-usp-br/8thBCSMIF/actions/workflows/laravel.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## 1. Introdução

Este repositório contém o código-fonte do site de inscrições para o **8th Brazilian Conference on Statistical Modeling in Insurance and Finance (8th BCSMIF)**. O evento será realizado de 28 de setembro a 3 de outubro de 2025, no Maresias Beach Hotel em Maresias, SP, e é organizado pelo Instituto de Matemática e Estatística da Universidade de São Paulo (IME-USP).

O objetivo do 8th BCSMIF é fornecer um fórum para a apresentação de pesquisas de ponta no desenvolvimento e implementação de métodos recentes no campo de Finanças e Seguros, com ênfase nas aplicações práticas de Ciência de Dados e Aprendizado de Máquina. O evento também busca promover a discussão e o intercâmbio de ideias entre jovens pesquisadores e cientistas seniores.

Este projeto de site de inscrições é construído sobre o **Laravel 12 USP Starter Kit**, aproveitando sua base robusta para autenticação, integração com sistemas USP e boas práticas de desenvolvimento.

## 2. Público-Alvo (deste Repositório)

Este README e o código-fonte são destinados principalmente a:

*   Desenvolvedores trabalhando no desenvolvimento e manutenção do site de inscrições do 8th BCSMIF.
*   Administradores do evento que possam precisar de informações técnicas sobre o sistema.

## 3. Principais Funcionalidades do Site de Inscrições

O site de inscrições do 8th BCSMIF visa oferecer as seguintes funcionalidades:

*   **Páginas Públicas Informativas:**
    *   Página Inicial com a descrição do evento, datas e local.
    *   Página de Workshops detalhando os eventos satélites (Risk Analysis and Applications e Dependence Analysis).
    *   Página de Taxas de Inscrição apresentando a tabela de preços completa.
    *   Página de Informações de Pagamento com instruções para participantes brasileiros e internacionais.
*   **Sistema de Inscrição Online:**
    *   Formulário de inscrição completo e intuitivo para participantes (requer login e email verificado).
    *   Diferenciação entre participantes USP e externos, com validação de Nº USP (codpes) para usuários USP.
    *   Seleção de participação no evento principal (8th BCSMIF) e/ou workshops.
    *   Cálculo dinâmico das taxas de inscrição baseado na categoria do participante, eventos selecionados e prazos (early bird/late).
*   **Gerenciamento de Pagamentos:**
    *   Instruções claras para pagamento (transferência/PIX para brasileiros).
    *   Informação sobre envio de invoice para participantes internacionais.
    *   Funcionalidade para upload de comprovante de pagamento para participantes brasileiros.
*   **Área do Usuário Logado:**
    *   Visualização das próprias inscrições realizadas.
    *   Acesso aos detalhes da inscrição.
    *   Upload de comprovante de pagamento (para inscrições elegíveis).
*   **Área Administrativa (MVP):**
    *   Listagem e visualização de todas as inscrições.
    *   Filtros básicos para gerenciamento.
    *   Download de comprovantes de pagamento.
    *   Atualização de status de pagamento das inscrições.
*   **Notificações por Email:**
    *   Confirmação de nova inscrição para o participante e coordenador.
    *   Notificação de upload de comprovante para o coordenador.
    *   (Opcional) Notificação de confirmação de pagamento.

*(Para um detalhamento completo do plano de implementação, consulte `docs/plano_inscricao_8thBCSMIF.md`)*

## 4. Stack Tecnológica

Este projeto utiliza a stack tecnológica fornecida pelo Laravel 12 USP Starter Kit:

*   **Framework:** Laravel 12
*   **Linguagem:** PHP >= 8.2
*   **Frontend (Stack TALL via Laravel Breeze):**
    *   **Livewire 3 (Class API)**
    *   **Alpine.js 3**
    *   **Tailwind CSS 4** (com suporte a Dark Mode)
    *   **Vite**
*   **Banco de Dados:** MySQL (preferencialmente), com suporte padrão do Laravel para outros (MariaDB, PostgreSQL, SQLite).
*   **Integrações USP (via Starter Kit):**
    *   `uspdev/senhaunica-socialite` para autenticação Senha Única.
    *   `uspdev/replicado` para acesso a dados corporativos (usado na validação de registro).
*   **Autenticação Scaffolding:** `laravel/breeze` (adaptado).
*   **Permissões:** `spatie/laravel-permission`.
*   **Testes:** PHPUnit, Laravel Dusk.
*   **Qualidade:** Laravel Pint, Larastan.
*   **Ferramentas Dev:** Python 3.x, `google-genai`, `python-dotenv`, `tqdm` (para scripts LLM auxiliares).

## 5. Instalação (Para Desenvolvedores)

Siga os passos abaixo para configurar o ambiente de desenvolvimento deste projeto:

1.  **Pré-requisitos:**
    *   PHP >= 8.2 (com extensões comuns do Laravel).
    *   Composer
    *   Node.js (v18+) e NPM
    *   Git
    *   **Google Chrome** ou **Chromium** instalado (para testes Dusk).
    *   (Opcional, para ferramentas de dev) Python >= 3.10, Pip, `gh` CLI, `jq`.

2.  **Clonar o Repositório:**
    ```bash
    git clone https://github.com/ime-usp-br/8thBCSMIF.git
    cd 8thBCSMIF
    ```

3.  **Instalar Dependências PHP:**
    ```bash
    composer install
    ```

4.  **Instalar Dependências Frontend:**
    ```bash
    npm install
    ```

5.  **Configurar Ambiente:**
    *   Copie o arquivo de exemplo `.env`:
        ```bash
        cp .env.example .env
        ```
    *   Gere a chave da aplicação:
        ```bash
        php artisan key:generate
        ```
    *   **Edite o arquivo `.env`:** Configure as variáveis de ambiente, especialmente:
        *   `APP_NAME="8thBCSMIF"` (ou similar).
        *   `APP_URL`: URL base local (ex: `http://8thbcsmif.test` ou `http://localhost:8000`).
        *   Credenciais do banco de dados (`DB_CONNECTION`, `DB_HOST`, etc.).
        *   Configurações de e-mail (`MAIL_*`).
        *   Credenciais para `uspdev/senhaunica-socialite` e `uspdev/replicado` (veja a Seção 7).
        *   (Opcional) `GEMINI_API_KEY` para scripts LLM.

6.  **Banco de Dados e Dados Iniciais:**
    *   Execute as migrações:
        ```bash
        php artisan migrate
        ```
    *   (Opcional, mas recomendado) Execute os seeders (incluindo os seeders de `Events` e `Fees` que serão criados para o 8thBCSMIF):
        ```bash
        php artisan db:seed
        ```

7.  **Compilar Assets Frontend:**
    ```bash
    npm run build
    ```
    *(Ou use `npm run dev` durante o desenvolvimento).*

8.  **Configuração Inicial do Dusk (Importante):**
    *   Verifique a instalação do Dusk (já deve estar no `composer.json`).
    *   Instale o ChromeDriver: `php artisan dusk:chrome-driver --detect`.
    *   Crie/Verifique `.env.dusk.local` (exemplo fornecido) com `APP_URL` e `DB_DATABASE` (ex: `database/testing/dusk.sqlite`) para testes Dusk.

9.  **(Opcional) Configurar Ferramentas de Desenvolvimento Python:**
    *   `pip install -r requirements-dev.txt`
    *   `chmod +x scripts/*.py scripts/tasks/*.py` (se necessário).

## 6. Uso Básico (Desenvolvimento)

1.  **Iniciar Servidores:**
    *   Servidor web PHP: `php artisan serve`
    *   Servidor Vite: `npm run dev`
2.  **Acessar a Aplicação:** Abra a `APP_URL` no navegador.

## 7. Configurações Específicas da USP

Configure as seguintes variáveis no seu arquivo `.env` para as integrações USP:

*   **Senha Única:** `SENHAUNICA_CALLBACK`, `SENHAUNICA_KEY`, `SENHAUNICA_SECRET`.
*   **Replicado:** `REPLICADO_HOST`, `REPLICADO_PORT`, `REPLICADO_DATABASE`, `REPLICADO_USERNAME`, `REPLICADO_PASSWORD`, `REPLICADO_CODUND`, `REPLICADO_CODBAS`.

*Consulte a documentação dos pacotes `uspdev/senhaunica-socialite` e `uspdev/replicado` para detalhes sobre como obter estas credenciais.*

## 8. Ferramentas e Qualidade de Código

Este projeto utiliza as ferramentas de qualidade e desenvolvimento herdadas do Laravel 12 USP Starter Kit:

*   **Laravel Pint:** Formatador PSR-12 (`vendor/bin/pint`).
*   **Larastan (PHPStan):** Análise estática (`vendor/bin/phpstan analyse`).
*   **EditorConfig:** Consistência de estilo.
*   **Scripts Python (`scripts/`):**
    *   `create_issue.py`: Para automação de criação/edição de Issues no GitHub.
    *   `generate_context.py`: Para coletar contexto do projeto para LLMs (com execução seletiva via `--stages`).
    *   `llm_interact.py` (dispatcher) e `scripts/tasks/llm_task_*.py` (tarefas individuais): Para interações com a API Gemini, auxiliando em diversas tarefas de desenvolvimento.

## 9. Testes

*   **PHPUnit (Unitários e Feature):** `php artisan test`
*   **Laravel Dusk (Browser):** Requer setup específico (servidor app e ChromeDriver rodando). Veja o [Guia de Desenvolvimento](./docs/guia_de_desenvolvimento.md#9-testes-automatizados) para instruções detalhadas de execução local.
    Comando principal: `php artisan dusk`

## 10. Documentação

*   **Este README.md:** Visão geral do projeto do site de inscrições.
*   **Diretório `docs/`:**
    *   `descricao_evento.md`: Detalhes sobre o 8th BCSMIF.
    *   `formulario_inscricao.md`: Especificação do formulário de inscrição.
    *   `plano_inscricao_8thBCSMIF.md`: Plano de implementação detalhado do site.
    *   Documentos herdados do Starter Kit (`guia_de_desenvolvimento.md`, `padroes_codigo_boas_praticas.md`, etc.) que guiam o desenvolvimento deste projeto.

## 11. Como Contribuir

Siga o fluxo descrito no **[Guia de Estratégia de Desenvolvimento](./docs/guia_de_desenvolvimento.md)** (herdado e adaptado do Starter Kit) para contribuições.

## 12. Licença

Este projeto é licenciado sob a **Licença MIT**. Veja o arquivo [LICENSE](./LICENSE) para mais detalhes.