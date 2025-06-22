---
description: "Executa valida��o completa de AC seguindo workflow obrigat�rio do CLAUDE.md"
---

## Tarefa: Valida��o Completa de Acceptance Criteria (AC)

Seguindo a **Sequ�ncia Obrigat�ria** definida no `CLAUDE.md`, esta tarefa executa todo o workflow de valida��o de um AC espec�fico.

**Argumentos esperados:** `<ISSUE_NUMBER> <AC_NUMBER>`

### 1. **An�lise e Contexto da Issue**
!echo "=== AN�LISE DA ISSUE #$ARGUMENT_1 ===" && gh issue view $ARGUMENT_1

### 2. **Quality Checks Obrigat�rios**
!echo "=== EXECUTANDO QUALITY CHECKS ===" && echo "Executando Pint (PSR-12)..." && vendor/bin/pint && echo -e "\n Pint conclu�do\n" && echo "Executando PHPStan..." && vendor/bin/phpstan analyse && echo -e "\n PHPStan conclu�do\n" && echo "Executando PHPUnit..." && php artisan test && echo -e "\n PHPUnit conclu�do\n" && echo "Executando Pytest..." && pytest -v --live && echo -e "\n Pytest conclu�do\n"

### 3. **Atualiza��o de Contexto**
!echo "=== ATUALIZANDO CONTEXTO ===" && git add . && python3 scripts/generate_context.py --stages git

### 4. **Valida��o Cr�tica com analyze-ac**
!echo "=== EXECUTANDO VALIDA��O CR�TICA ===" && printf "y\ny\ny\n" | python3 scripts/tasks/llm_task_analyze_ac.py -i $ARGUMENT_1 -a $ARGUMENT_2 -sc

### 5. **Verifica��o de Resultados**
!echo "=== VERIFICANDO RESULTADOS DA AN�LISE ===" && LATEST_ANALYSIS=$(ls -t llm_outputs/analyze-ac/*.txt | head -1) && echo "=� �ltimo arquivo de an�lise: $LATEST_ANALYSIS" && echo -e "\n=� RESUMO DA VALIDA��O:" && tail -10 "$LATEST_ANALYSIS"

**� ATEN��O:** S� prossiga para commit e documenta��o se a valida��o `analyze-ac` APROVAR o AC. Caso contr�rio, corrija as pend�ncias identificadas.

**Pr�ximos passos** (apenas se valida��o APROVADA):
1. Use `/commit` para criar commit formatado
2. Use `/postar-comentario` para documentar no GitHub