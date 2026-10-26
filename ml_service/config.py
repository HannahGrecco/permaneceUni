from pathlib import Path

ROOT_DIR = Path(__file__).resolve().parent.parent
DATASET_PATH = ROOT_DIR / "datasets" / "students_dropout_academic_success.csv"
MODEL_PATH = ROOT_DIR / "ml_service" / "artifacts" / "dropout_model.joblib"
METRICS_PATH = ROOT_DIR / "ml_service" / "artifacts" / "metrics.json"

# Keep this list aligned with the documented dataset adaptation in the notebook.
DROPPED_COLUMNS = [
    "Application mode",
    "Application order",
    "Previous qualification",
    "Previous qualification (grade)",
    "Marital Status",
    "Nacionality",
    "International",
    "Displaced",
    "Mother's qualification",
    "Father's qualification",
    "Mother's occupation",
    "Father's occupation",
    "Unemployment rate",
    "Inflation rate",
    "GDP",
    "Curricular units 1st sem (credited)",
    "Curricular units 2nd sem (credited)",
]

TARGET_COLUMN = "target"
RISK_THRESHOLD = 0.35
