---
description: "Guia para executar todas as verificações de qualidade obrigatórias (Pint, PHPStan, PHPUnit, Pytest)."
---

**Nota Importante:** Ao executar comandos manualmente ou adicionar novos comandos a este workflow, se o comando puder gerar uma saída que precise ser exibida ou que possa travar o terminal, utilize `| cat` ao final do comando. Exemplo: `seu-comando-aqui | cat`.

## Guia: Quality Checks Obrigatórios

O Cline deve executar todas as verificações de qualidade na ordem exata definida no projeto. Cada etapa deve ser aprovada antes de prosseguir.

### 1. PSR-12 Formatting (Pint)
O Cline deve executar o Pint para garantir a formatação PSR-12.

<execute_command>
<command>vendor/bin/pint | cat</command>
<requires_approval>false</requires_approval>
</execute_command>

### 2. Static Analysis (PHPStan Level 9)
O Cline deve executar o PHPStan para análise estática.

<execute_command>
<command>vendor/bin/phpstan analyse | cat</command>
<requires_approval>false</requires_approval>
</execute_command>

### 3. Unit/Feature Tests (PHPUnit)
O Cline deve executar os testes de unidade e feature com PHPUnit.

<execute_command>
<command>php artisan test | cat</command>
<requires_approval>false</requires_approval>
</execute_command>

### 4. Python Tests (Pytest)
O Cline deve executar os testes Python com Pytest.

<execute_command>
<command>pytest -v --live | cat</command>
<requires_approval>false</requires_approval>
</execute_command>

### 5. Browser Tests (Opcional - Dusk)
O Cline deve verificar e executar os testes de navegador (Dusk), se existirem.

<execute_command>
<command>if grep -r "dusk" tests/ >/dev/null 2>&1; then echo "Executando testes Dusk..." && php artisan dusk; else echo "Nenhum teste Dusk encontrado"; fi</command>
<requires_approval>false</requires_approval>
</execute_command>

---

**📋 RESULTADO FINAL:**
- **TODOS OS CHECKS DEVEM PASSAR** antes de executar a validação de AC.
- Se algum check falhar, o Cline deve parar e instruir o usuário a corrigir antes de prosseguir.
- O Cline **NÃO** deve executar a validação de AC com quality checks reprovados.
