---
description: "Propõe uma mensagem de commit Git seguindo os padrões do projeto."
---

## Tarefa: Elaboração da Mensagem de Commit

**Claude Code deve analisar** as mudanças em stage e o histórico de commits recentes para criar uma mensagem de commit que siga estritamente os padrões do projeto definidos em `CLAUDE.md`.

**1. Verificação de Changes em Stage:**
**Claude Code deve executar:**
!git diff --cached --stat && git status --porcelain

**2. Histórico de Commits Recentes (FORMATO COMPLETO - CRÍTICO):**
**Claude Code deve executar:**
!git log -5 --pretty=format:"%h - %s%n%b%n---"

**🔴 LIÇÃO APRENDIDA:** NUNCA use `git log --oneline` - só mostra primeira linha, perdendo estrutura de bullet points.

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

**Formato HEREDOC obrigatório (TESTADO E APROVADO):**

**✅ EXEMPLO REAL QUE FUNCIONOU (AC1 Issue #50):**

**Claude Code deve executar** o comando de commit pronto:

```bash
git commit -m "$(cat <<'EOF'
feat(registrations): Implementa AC1 - nova página para modificação de inscrições (#50)

- Adiciona rota GET /my-registration/modify com middleware auth e verified
- Cria componente Livewire/Volt registration-modification.blade.php
- Implementa interface completa com seleção de eventos e cálculo de taxas
- Adiciona validação de entrada e prevenção de duplicação de eventos
- Corrige controller para usar attach() ao invés de sync() para adição incremental
- Atualiza link "Add Events" em my-registrations.blade.php
- Implementa exibição de warning para pagamentos pendentes
- Adiciona integração com FeeCalculationService para cálculo em tempo real
- Atende AC1: Uma nova página/rota para modificação de inscrição existe e carrega o componente Livewire/Volt
EOF
)"
```

**📋 PADRÃO CONFIRMADO:**
- Tipo(escopo): Descrição principal (#issue)
- Linha em branco
- Bullet points com mudanças específicas
- Linha final com "Atende ACX: [texto do critério]"