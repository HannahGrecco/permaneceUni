"""Train and persist the inference artifact. Run explicitly with `python -m ml_service.training`."""

import json

import joblib
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import classification_report, confusion_matrix, roc_auc_score
from sklearn.model_selection import train_test_split

from ml_service.config import (
    DATASET_PATH,
    DROPPED_COLUMNS,
    METRICS_PATH,
    MODEL_PATH,
    RISK_THRESHOLD,
    TARGET_COLUMN,
)


def main() -> None:
    data = pd.read_csv(DATASET_PATH)
    data = data.drop(columns=DROPPED_COLUMNS, errors="ignore")
    data["risk"] = (data[TARGET_COLUMN] == "Dropout").astype(int)
    features = data.drop(columns=[TARGET_COLUMN, "risk"])
    target = data["risk"]

    x_train, x_test, y_train, y_test = train_test_split(
        features, target, test_size=0.2, random_state=42, stratify=target
    )
    model = RandomForestClassifier(
        n_estimators=100, class_weight="balanced", random_state=42, n_jobs=-1
    )
    model.fit(x_train, y_train)

    probabilities = model.predict_proba(x_test)[:, 1]
    predictions = (probabilities >= RISK_THRESHOLD).astype(int)
    report = classification_report(y_test, predictions, output_dict=True, zero_division=0)
    metrics = {
        "risk_threshold": RISK_THRESHOLD,
        "roc_auc": float(roc_auc_score(y_test, probabilities)),
        "confusion_matrix": confusion_matrix(y_test, predictions).tolist(),
        "classification_report": report,
        "feature_names": features.columns.tolist(),
        "training_rows": int(len(x_train)),
        "validation_rows": int(len(x_test)),
    }

    MODEL_PATH.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump({"model": model, "features": features.columns.tolist()}, MODEL_PATH)
    METRICS_PATH.write_text(json.dumps(metrics, indent=2), encoding="utf-8")
    print(f"Model saved to {MODEL_PATH}")
    print(f"Validation ROC AUC: {metrics['roc_auc']:.3f}")


if __name__ == "__main__":
    main()
