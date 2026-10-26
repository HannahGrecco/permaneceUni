# PermaneceUni

O repositório contém a aplicação web Laravel 13, o serviço Python de inferência e o notebook original de exploração de Machine Learning.

## Arquitetura atual

- `student_dropout_model.ipynb`: análise exploratória original.
- `datasets/students_dropout_academic_success.csv`: base acadêmica disponível no projeto.
- `ml_service/training.py`: treinamento explícito, validação e persistência do artefato.
- `ml_service/api.py`: API HTTP que carrega o artefato e atende previsões; não treina em requisições.
- `ml_service/artifacts/`: artefato e métricas locais, recriados pelo treinamento e ignorados pelo Git.
- `laravel/`: autenticação, instituição, estudantes, SQLite, importações, dashboard, previsões, intervenções e telas Blade/Tailwind.

O treinamento converte o alvo `Dropout` em risco positivo e agrupa `Graduate` e `Enrolled` em não dropout. O arquivo de métricas registra a lista de atributos esperados. A seleção atual usa 19 atributos acadêmicos/financeiros numéricos do CSV; a coluna de saída, atributos demográficos removidos e indicadores macroeconômicos não são enviados ao serviço.

Para preparar a API:

```powershell
python -m venv .venv
.\.venv\Scripts\python.exe -m pip install -r ml_service\requirements.txt
.\.venv\Scripts\python.exe -m ml_service.training
.\.venv\Scripts\python.exe -m unittest ml_service.test_api
.\.venv\Scripts\python.exe -m uvicorn ml_service.api:app --host 127.0.0.1 --port 8000
```

O endpoint `POST /v1/predictions` recebe os 19 atributos, sob `student`, e devolve score, classe, nível e contribuições locais TreeSHAP. `cause` permanece `undetermined` porque uma atribuição do modelo não comprova causalidade no estudante.

## Laravel

Laravel cuida de login/autorização, instituições e estudantes, importações CSV, validação, persistência de predições, intervenções/histórico e telas Blade com Tailwind. `MlPredictionService` chama `/v1/predictions`; modelos e algoritmo Python ficam desacoplados da aplicação. SQLite atende ao MVP local; `institution_id` nas entidades prepara o caminho para múltiplas instituições.

O ambiente fornece PHP 8.5 pelo Laravel Herd, Composer e Node.js. Para iniciar a API, rode em um terminal `python -m uvicorn ml_service.api:app --host 127.0.0.1 --port 8000`; em outro, `cd laravel` e `php artisan queue:work --timeout=1800`. O servidor web inicia com `php artisan serve`. Compile assets com o npm instalado no Node (`npm run build`).

## Importações CSV pendentes

Só há um CSV no projeto, a base acadêmica acima. A importação dela está ativa e gera previsões. O tipo de importação `student_registry` recebe e armazena um segundo CSV como `awaiting_schema`, sem processá-lo até que o arquivo e seus cabeçalhos sejam fornecidos.
