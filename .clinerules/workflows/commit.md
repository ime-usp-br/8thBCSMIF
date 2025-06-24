---
description: "Guia para elaborar uma mensagem de commit Git seguindo os padrões do projeto."
---

## Guia: Elaboração da Mensagem de Commit

O Cline deve analisar as mudanças em stage e o histórico de commits recentes para criar uma mensagem de commit que siga estritamente os padrões do projeto definidos em `CLAUDE.md` (ou `CLINE.md` se for o caso).

### 1. Verificação de Changes em Stage:
<execute_command>
<command>git diff --cached --stat && git status --porcelain</command>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Histórico de Commits Recentes (FORMATO COMPLETO - CRÍTICO):
<execute_command>
<command>git log -5 --pretty=format:"%h - %s%n%b%n---"</command>
<requires_approval>false</requires_approval>
</execute_command>
<!-- LIÇÃO APRENDIDA: NUNCA use `git log --oneline` - só mostra primeira linha, perdendo estrutura de bullet points. -->

### 3. Identificação de Issue/AC (se aplicável):
<execute_command>
<command>git diff --cached | grep -E "#[0-9]+" | head -5 || echo "Nenhuma referência a issue encontrada no diff"</command>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Ação:
Com base nas informações acima e nos padrões do projeto, o Cline deve criar o comando de commit com:
- **Tipo**: feat/fix/test/docs/refactor/style
- **Escopo**: Módulo/área afetada
- **Descrição**: Resumo principal com referência à issue (#X)
- **Corpo**: Bullet points com mudanças específicas
- **AC**: Linha final indicando qual AC foi atendido (se aplicável)

**Formato HEREDOC obrigatório (TESTADO E APROVADO):**
O Cline deve construir a mensagem de commit dinamicamente, utilizando o formato HEREDOC para garantir a estrutura correta.

**📋 PADRÃO CONFIRMADO:**
- Tipo(escopo): Descrição principal (#issue)
- Linha em branco
- Bullet points com mudanças específicas
- Linha final com "Atende ACX: [texto do critério]"
