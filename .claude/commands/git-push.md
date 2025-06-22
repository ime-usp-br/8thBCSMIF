---
description: "Executa git push com verificações de segurança e status do repositório."
---

## Tarefa: Push Seguro para Repositório Remoto

Executa `git push` com verificações prévias de segurança e status do repositório, seguindo as diretrizes do `CLAUDE.md`.

### 1. **Verificação de Status Atual**
!echo "📊 Verificando status atual do repositório..." && git status --porcelain && echo -e "\n🔍 Branch atual:" && git branch --show-current

### 2. **Verificação de Commits Locais**
!echo "📋 Commits locais não enviados:" && git log --oneline @{u}..HEAD 2>/dev/null || echo "Nenhum commit local pendente ou branch não tem upstream"

### 3. **Verificação de Arquivos Sensíveis**
!echo "🔒 Verificando arquivos sensíveis antes do push..." && git diff --cached --name-only | grep -E "\.(env|key|pem|p12|pfx)$" && echo "⚠️ ATENÇÃO: Arquivos sensíveis detectados!" || echo "✅ Nenhum arquivo sensível detectado"

### 4. **Verificação de Secrets em Commits**
!echo "🔐 Verificando secrets nos commits..." && git log --oneline -5 | grep -iE "(password|secret|key|token)" && echo "⚠️ POSSÍVEIS SECRETS detectados nos commits!" || echo "✅ Nenhum secret aparente detectado"

### 5. **Push Seguro**
!echo "🚀 Executando git push..." && git push && echo -e "\n✅ Push realizado com sucesso!"

### 6. **Verificação Pós-Push**
!echo "📈 Status após push:" && git status && echo -e "\n🔗 Link do último commit:" && git log -1 --pretty=format:"https://github.com/ime-usp-br/8thBCSMIF/commit/%H"

**⚠️ IMPORTANTE:** Este comando executa push automaticamente após as verificações. Se detectar arquivos sensíveis ou possíveis secrets, **revise cuidadosamente** antes de prosseguir.

**Segurança:** Nunca faça push de:
- Arquivos `.env` ou similares
- Chaves privadas ou certificados
- Passwords ou tokens em código
- Dados sensíveis de usuários