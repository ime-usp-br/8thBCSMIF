---
description: "Propõe uma mensagem de commit Git seguindo os padrões do projeto."
---

## Tarefa: Elaboração da Mensagem de Commit

**Claude Code deve analisar** as mudanças em stage e o histórico de commits recentes para criar uma mensagem de commit que siga estritamente os padrões do projeto definidos em `CLAUDE.md`.

**1. Verificação de Changes em Stage:**
**Claude Code deve executar:**
!git diff --cached --stat && git status --porcelain

**2. Histórico de Commits Recentes (FORMATO COMPLETO):**
**Claude Code deve executar:**
!git log -5 --pretty=format:"%h - %s%n%b%n---"

**3. Identificação de Issue/AC (se aplicável):**
**Claude Code deve executar:**
!git diff --cached | grep -E "#[0-9]+" | head -5 || echo "Nenhuma referência a issue encontrada no diff"

**4. Ação:**
Com base nas informações acima e nos padrões do projeto, **Claude Code deve criar** o comando de commit com:
- **Tipo**: feat/fix/test/docs/refactor/style
- **Escopo**: Módulo/área afetada
- **Descrição**: Resumo principal com referência à issue (#X)
- **Corpo**: Bullet points com mudanças específicas
- **AC**: Linha final indicando qual AC foi atendido (se aplicável)

**Formato obrigatório HEREDOC conforme CLAUDE.md:**

**Exemplo de Formato da Resposta Esperada:**

**Claude Code deve executar** o comando de commit pronto:

```bash
git commit -m "$(cat <<'EOF'
feat(auth): Implementa fluxo de login com Senha Única (#42)

- Adiciona SocialiteController para lidar com o callback OAuth.
- Cria rota /login/senhaunica para redirecionamento.
- Atualiza o model User com o trait HasSenhaunica.
- Atende AC1: O usuário pode clicar no botão "Login com Senha Única".
EOF
)"
```