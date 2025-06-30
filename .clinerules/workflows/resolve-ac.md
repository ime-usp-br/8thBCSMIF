---
description: "Workflow para análise e resolução de critérios de aceite específicos de issues, fornecendo orientações estratégicas baseadas no guia de desenvolvimento do projeto."
---

**Nota Importante:** Ao executar comandos manualmente ou adicionar novos comandos a este workflow, se o comando puder gerar uma saída que precise ser exibida ou que possa travar o terminal, utilize `| cat` ao final do comando. Exemplo: `seu-comando-aqui | cat`.

## Guia: Resolução de Critério de Aceite

Este workflow analisa um critério de aceite específico de uma issue e fornece orientações estratégicas para sua implementação, seguindo rigorosamente os padrões e filosofia do projeto conforme documentado no guia de desenvolvimento.

### Parâmetros
- `$1`: Número da issue
- `$2`: Número do critério de aceite (posição na lista de checkboxes)

### Ambiente de Execução
**Atenção:** Todos os comandos `python` devem ser executados dentro do ambiente virtual do projeto. Ative-o com `source venv/bin/activate` antes de executar qualquer script.

### Instruções para o Assistente de IA

**1. Análise da Issue e Critério Específico**
Recupere a issue completa e identifique o critério de aceite específico pela posição informada no parâmetro $2. Analise o contexto geral da issue e o critério específico solicitado. Para recuperar os detalhes da issue, você pode usar o `gh cli`.

**2. Verificação do Estado do Repositório**
Analise o estado atual do repositório para entender se existe branch ativa para esta issue e qual o progresso atual. Se não existir branch para a issue seguindo o padrão `feature/$1-nome`, `fix/$1-nome` ou `chore/$1-nome`, instrua para:
- Fazer checkout para `main`
- Executar `git pull` para sincronizar
- Criar nova branch seguindo o padrão do projeto baseado no tipo da issue

**3. Contexto Arquitetural do Projeto**
Com base na documentação lida, considere sempre:
- **Backend como fonte da verdade**: A LLM sugere, mas o backend Django executa
- **Arquitetura de Function Calling**: Interação LLM-backend via ferramentas Pydantic
- **Loop de interação central**: Frontend (Alpine.js) → WebSocket → Backend → LLM → Backend → Frontend
- **Issues atômicas e commits atômicos**: Cada unidade de trabalho deve ser rastreável
- **Stack tecnológica**: Django 5.2.3 + Channels + Alpine.js + Pydantic + SQLite

**4. Análise Específica do Critério**
Para o critério de aceite identificado, determine:
- Qual componente da arquitetura é afetado (models, views, consumers, tools, frontend)
- Se requer implementação de ferramentas LLM (com schemas Pydantic)
- Se necessita de testes unitários E de integração (obrigatório conforme guia)
- Que padrões de commit e branching devem ser seguidos

**5. Orientações Estratégicas de Alto Nível**
Forneça orientações específicas sobre:
- **Onde implementar**: Arquivos e diretórios específicos baseados na estrutura do projeto
- **Como implementar**: Padrões arquiteturais a seguir (ex: Function Calling, async/await)
- **Ordem de implementação**: Sequência lógica considerando dependências
- **Estratégia de testes**: Como mockar a LLM e testar componentes isoladamente
- **Critérios de pronto**: Como validar que o critério foi completamente atendido

**6. Conformidade com o Processo**
Sempre lembre sobre:
- Commits devem seguir formato `<tipo>(<escopo>): <descrição> (#$1)`
- Testes são obrigatórios (unitários + integração com LLM mockada)
- PR deve usar `Closes #$1` para fechar a issue automaticamente
- CI deve passar (black, ruff, mypy, pytest-django) antes do merge
- Seguir limitação de WIP (1-2 tarefas em progresso)

**7. Considerações Específicas do RPG/LLM**
Para este projeto específico, sempre considere:
- Se o critério envolve interação com LLM, definir schema Pydantic para ferramentas
- Se altera estado do jogo, implementar no backend (não confiar na LLM)
- Se adiciona funcionalidade nova, considerar impacto no RAG e contexto
- Se é UI, garantir que funciona com Alpine.js e WebSockets
- Se é modelo de dados, considerar impacto no sistema de turnos e tempo de jogo

**Resultado Esperado:**
Orientações claras, específicas e acionáveis para implementar o critério de aceite solicitado, respeitando integralmente a arquitetura, padrões e filosofia do projeto conforme documentado.
