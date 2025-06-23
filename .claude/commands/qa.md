---
description: "Executa todas as verificações de qualidade obrigatórias (Pint, PHPStan, PHPUnit, Pytest)."
---

## Quality Checks Obrigatórios - Sequência CLAUDE.md

**Claude Code deve executar** todas as verificações de qualidade na **ordem exata** definida no `CLAUDE.md`. Cada etapa deve ser aprovada antes de prosseguir.

### 1. **PSR-12 Formatting (Pint)**
**Claude Code deve executar:**
!vendor/bin/pint

### 2. **Static Analysis (PHPStan Level 9)**
**Claude Code deve executar:**
!vendor/bin/phpstan analyse

### 3. **Unit/Feature Tests (PHPUnit)**
**Claude Code deve executar:**
!php artisan test

### 4. **Python Tests (Pytest)**
**Claude Code deve executar:**
!pytest -v --live

### 5. **Browser Tests (Opcional - Dusk)**
**Claude Code deve executar:**
!if grep -r "dusk" tests/ >/dev/null 2>&1; then echo "Executando testes Dusk..." && php artisan dusk; else echo "Nenhum teste Dusk encontrado"; fi

---

**📋 RESULTADO FINAL:**
- **TODOS OS CHECKS DEVEM PASSAR** antes de executar `analyze-ac`
- Se algum check falhar, **PARE** e corrija antes de prosseguir
- **NÃO** execute validação AC com quality checks reprovados

**Próximo passo:** Execute `/validacao <ISSUE> <AC>` apenas se todos os checks passaram.