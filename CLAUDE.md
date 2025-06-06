# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 🔄 WORKFLOW SEQUENCE (OBRIGATÓRIO)

**SEMPRE siga esta sequência exata para implementar qualquer AC (Acceptance Criteria):**

### 1. **Análise e Planejamento**
- Use `TodoWrite` para planejar as tarefas
- Leia a issue completa: `gh issue view <ISSUE_NUMBER>`
- Identifique o AC específico a implementar
- Analise dependências e padrões existentes no código

### 2. **Implementação**
- Implemente as mudanças seguindo padrões do projeto
- **SEMPRE** adicione testes que comprovem a funcionalidade (mesmo que o AC não exija explicitamente)
- Siga convenções de código existentes

### 3. **Quality Checks (OBRIGATÓRIOS)**
```bash
vendor/bin/pint                     # PSR-12 formatting
vendor/bin/phpstan analyse          # Static analysis  
php artisan test                    # PHPUnit tests
pytest -v --live                    # Python tests (se aplicável)
```

### 4. **Validação (CRÍTICO)**
```bash
git add .
python3 scripts/generate_context.py --stages git
printf "y\ny\ny\n" | python3 scripts/tasks/llm_task_analyze_ac.py -i <ISSUE> -a <AC> -sc
```
**⚠️ SÓ AVANCE SE analyze-ac APROVAR! Caso contrário, atenda as exigências.**

### 5. **Commit & Documentação**
```bash
git log -5                          # Analise formato (NÃO use --oneline)
git commit -m "$(cat <<'EOF'
tipo(escopo): Descrição principal (#issue)

- Bullet point com mudança específica 1
- Bullet point com mudança específica 2
- Bullet point com mudança específica 3
- Atende ACX: Descrição do critério atendido
EOF
)"
git push                            # ANTES do comentário GitHub
```

### 6. **Documentação GitHub**

#### **🔴 PASSO CRÍTICO: Verificar Padrão de Comentários ANTES de Elaborar**
```bash
# SEMPRE verificar comentários existentes para manter padrão
gh api repos/:owner/:repo/issues/<ISSUE>/comments

# Se for AC1 e não houver comentários, verificar issues fechadas semelhantes
gh issue list --state closed --label feature --limit 5
gh api repos/:owner/:repo/issues/<ISSUE_FECHADA>/comments
```

#### **Formato Obrigatório do Comentário:**
- **Título:** `## Conclusão sobre o Critério de Aceite X (ACX) da Issue #Y`
- **Critério:** Citar exatamente o texto do AC
- **Análise:** Seções numeradas explicando implementação detalhada
- **Conclusão:** "O Critério de Aceite X (ACX) foi **Atendido**."
- **Rodapé:** `---\n**Validação realizada no commit:** <hash>`

#### **Submissão do Comentário:**
```bash
gh api repos/:owner/:repo/issues/<ISSUE>/comments -F body=@/tmp/comment.txt
```
- Use EXATAMENTE o output do analyze-ac como base
- Adapte ao formato padrão observado nos comentários existentes
- Inclua hash do commit para rastreabilidade
- **🔴 CRÍTICO:** NUNCA use HEREDOC para criar /tmp/comment.txt (causa "EOF < /dev/null" no GitHub)
- **OBRIGATÓRIO:** Verificar conteúdo com `cat /tmp/comment.txt` antes do `gh api`

### Instruções Importantes
- O comando `analyze_ac` deve ser executado sem me perguntar!!!

[Restante do conteúdo anterior permanece o mesmo...]