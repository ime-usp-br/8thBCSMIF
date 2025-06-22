---
description: "Executa git push com verificações de segurança e status do repositório."
---

## Tarefa: Push Seguro para Repositório Remoto

Executa `git push` com verificações prévias de segurança e status do repositório, seguindo as diretrizes do `CLAUDE.md`.

### 1. **Verificação de Status Atual**
!git status --porcelain && git branch --show-current

### 2. **Verificação de Commits Locais**
!git log --oneline @{u}..HEAD 2>/dev/null || echo "Nenhum commit local pendente ou branch não tem upstream"

### 3. **Verificação de Arquivos Sensíveis**
!git diff --cached --name-only | grep -E "\.(env|key|pem|p12|pfx)$" && echo "⚠️ ATENÇÃO: Arquivos sensíveis detectados!" || echo "✅ Nenhum arquivo sensível detectado"

### 4. **Verificação de Secrets em Commits**
!git log --oneline -5 | grep -iE "(password|secret|key|token)" && echo "⚠️ POSSÍVEIS SECRETS detectados nos commits!" || echo "✅ Nenhum secret aparente detectado"

### 5. **Push Seguro**
!git push

### 6. **Verificação Pós-Push**
!git status && git log -1 --pretty=format:"https://github.com/ime-usp-br/8thBCSMIF/commit/%H"

**⚠️ IMPORTANTE:** Este comando executa push automaticamente após as verificações. Se detectar arquivos sensíveis ou possíveis secrets, **revise cuidadosamente** antes de prosseguir.

**Segurança:** Nunca faça push de:
- Arquivos `.env` ou similares
- Chaves privadas ou certificados
- Passwords ou tokens em código
- Dados sensíveis de usuários