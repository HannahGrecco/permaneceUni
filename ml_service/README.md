# Serviço de Machine Learning

O serviço Python mantém o treinamento separado da inferência. A API carrega um artefato já treinado ao iniciar e nunca treina durante uma requisição.

## Preparação e execução

Na raiz do repositório, instale `ml_service/requirements.txt`. Gere o artefato de forma explícita com `python -m ml_service.training` e inicie a API com `uvicorn ml_service.api:app --host 0.0.0.0 --port 8000`.

O treinamento utiliza `datasets/students_dropout_academic_success.csv`, a seleção de colunas descrita no notebook e um split estratificado. O modelo e as métricas de validação são gravados em `ml_service/artifacts/`. O primeiro treinamento não é executado automaticamente.

Para repetir a verificação de contrato após treinar: `python -m unittest ml_service.test_api`. O artefato precisa existir antes dos testes.

## Contrato HTTP

`POST /v1/predictions`

```json
{
  "student": {
    "Admission grade": 14.5,
    "Age at enrollment": 20
  }
}
```

O objeto `student` deve incluir todos os campos listados em `feature_names` no `metrics.json`, com os nomes e valores numéricos compatíveis com as colunas usadas no treinamento. Campos extras são ignorados.

A resposta inclui `risk_score` em porcentagem, `risk_level`, `predicted_class` e uma explicação local TreeSHAP para os 19 atributos. `baseline_score` mais a soma das contribuições reproduz o score do Random Forest; contribuições positivas elevam a estimativa e negativas a reduzem. `cause` permanece `undetermined`: TreeSHAP explica o cálculo do modelo, não uma causa real ou efeito causal no estudante. `GET /health` fornece a verificação de disponibilidade.

## Integração com Laravel

Laravel mantém autenticação, autorização, instituição, estudantes, importações, banco operacional, intervenções e apresentação. Um serviço Laravel dedicado envia ao endpoint os atributos acadêmicos aprovados para predição e persiste o resultado associado ao estudante. A aplicação depende apenas do contrato JSON versionado, sem importar ou conhecer o algoritmo Python.

O Laravel está em `laravel/`. `App\Services\MlPredictionService` envia o objeto `student` ao endpoint versionado e grava a resposta sem depender do algoritmo. A importação em fila executa chamadas por registro. Em ambiente local, mantenha a API Python em execução e rode `php artisan queue:work --timeout=1800` no diretório Laravel.
