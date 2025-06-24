---
description: "Guia para executar git push com verificações de segurança e status do repositório."
---

## Guia: Push Seguro para Repositório Remoto

O Cline deve executar `git push` com verificações prévias de segurança e status do repositório, seguindo as diretrizes do projeto.

### 1. Verificação de Status Atual
O Cline deve verificar o status atual do repositório e a branch atual.

<execute_command>
<command>git status --porcelain && git branch --show-current</command>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Verificação de Commits Locais
O Cline deve verificar se há commits locais pendentes de push.

<execute_command>
<command>git log --oneline @{u}..HEAD 2>/dev/null || echo "Nenhum commit local pendente ou branch não tem upstream"</command>
<requires_approval>false</requires_approval>
</execute_command>

### 3. Verificação de Arquivos Sensíveis
O Cline deve verificar se há arquivos sensíveis em stage.

<execute_command>
<command>git diff --cached --name-only | grep -E "\.(env|key|pem|p12|pfx)$" && echo "⚠️ ATENÇÃO: Arquivos sensíveis detectados!" || echo "✅ Nenhum arquivo sensível detectado"</command>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Verificação de Secrets em Commits
O Cline deve verificar se há possíveis secrets nos últimos 5 commits.

<execute_command>
<command>git log --oneline -5 | grep -iE "(password|secret|key|token)" && echo "⚠️ POSSÍVEIS SECRETS detectados nos commits!" || echo "✅ Nenhum secret aparente detectado"</command>
<requires_approval>false</requires_approval>
</execute_command>

### 5. Push Seguro
O Cline deve executar o `git push`.

<execute_command>
<command>git push</command>
<requires_approval>false</requires_approval>
</execute_command>

### 6. Verificação Pós-Push
O Cline deve verificar o status do repositório após o push e fornecer o link do último commit no GitHub.

<execute_command>
<command>git status && git log -1 --pretty=format:"https://github.com/ime-usp-br/8thBCSMIF/commit/%H"</command>
<requires_approval>false</requires_approval>
</execute_command>

**⚠️ IMPORTANTE:** Este guia descreve um processo de push que inclui verificações de segurança. Se arquivos sensíveis ou possíveis secrets forem detectados, o Cline deve ser instruído a revisar cuidadosamente antes de prosseguir com o push.

**Segurança:** Nunca faça push de:
- Arquivos `.env` ou similares
- Chaves privadas ou certificados
- Passwords ou tokens em código
- Dados sensíveis de usuários
