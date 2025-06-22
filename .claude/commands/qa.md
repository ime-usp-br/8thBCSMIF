---
description: "Executa todas as verificações de qualidade obrigatórias (Pint, PHPStan, PHPUnit, Pytest)."
---

## Quality Checks Obrigatórios - Sequência CLAUDE.md

Executando todas as verificações de qualidade na **ordem exata** definida no `CLAUDE.md`. Cada etapa deve ser aprovada antes de prosseguir.

### 1. **PSR-12 Formatting (Pint)**
!echo "🔧 Executando Laravel Pint..." && vendor/bin/pint && echo -e "\n✅ Pint: Formatação PSR-12 aprovada\n" || echo -e "\n❌ Pint: FALHA na formatação - Corrija antes de prosseguir\n"

### 2. **Static Analysis (PHPStan Level 9)**
!echo "📊 Executando PHPStan (Level 9)..." && vendor/bin/phpstan analyse && echo -e "\n✅ PHPStan: Análise estática aprovada\n" || echo -e "\n❌ PHPStan: FALHA na análise - Corrija antes de prosseguir\n"

### 3. **Unit/Feature Tests (PHPUnit)**
!echo "🧪 Executando PHPUnit..." && php artisan test && echo -e "\n✅ PHPUnit: Todos os testes aprovados\n" || echo -e "\n❌ PHPUnit: FALHA nos testes - Corrija antes de prosseguir\n"

### 4. **Python Tests (Pytest)**
!echo "🐍 Executando Pytest..." && pytest -v --live && echo -e "\n✅ Pytest: Testes Python aprovados\n" || echo -e "\n❌ Pytest: FALHA nos testes Python - Corrija antes de prosseguir\n"

### 5. **Browser Tests (Opcional - Dusk)**
!echo "🌐 Verificando necessidade de testes Browser..." && if grep -r "dusk" tests/ >/dev/null 2>&1; then echo "Testes Dusk encontrados, executando..." && php artisan dusk && echo -e "\n✅ Dusk: Testes browser aprovados\n"; else echo "Nenhum teste Dusk encontrado, pulando..."; fi

---

**📋 RESULTADO FINAL:**
- **TODOS OS CHECKS DEVEM PASSAR** antes de executar `analyze-ac`
- Se algum check falhar, **PARE** e corrija antes de prosseguir
- **NÃO** execute validação AC com quality checks reprovados

**Próximo passo:** Execute `/validacao <ISSUE> <AC>` apenas se todos os checks passaram.