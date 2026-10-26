"""HTTP inference API. It never trains a model during a request."""

from contextlib import asynccontextmanager
from typing import Any

import joblib
import numpy as np
import pandas as pd
import shap
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

from ml_service.config import MODEL_PATH, RISK_THRESHOLD


class PredictionRequest(BaseModel):
    student: dict[str, Any] = Field(..., description="Model feature names and numeric values")


@asynccontextmanager
async def lifespan(app: FastAPI):
    if not MODEL_PATH.exists():
        raise RuntimeError(
            f"Model artifact missing at {MODEL_PATH}. Train explicitly with python -m ml_service.training."
        )
    app.state.artifact = joblib.load(MODEL_PATH)
    app.state.explainer = shap.TreeExplainer(
        app.state.artifact["model"],
        feature_perturbation="tree_path_dependent",
        feature_names=app.state.artifact["features"],
    )
    yield


app = FastAPI(title="PermaneceUni ML Service", version="1.0.0", lifespan=lifespan)


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/v1/predictions")
def predict(payload: PredictionRequest) -> dict[str, Any]:
    artifact = app.state.artifact
    expected = artifact["features"]
    missing = [name for name in expected if name not in payload.student]
    if missing:
        raise HTTPException(status_code=422, detail={"missing_features": missing})

    try:
        row = pd.DataFrame([{name: payload.student[name] for name in expected}], columns=expected)
        row = row.apply(pd.to_numeric, errors="raise")
    except (TypeError, ValueError) as exc:
        raise HTTPException(status_code=422, detail="All model features must be numeric.") from exc
    if not np.isfinite(row.to_numpy(dtype=float)).all():
        raise HTTPException(status_code=422, detail="All model features must be finite numbers.")

    model = artifact["model"]
    score = float(model.predict_proba(row)[0, 1])
    shap_values = app.state.explainer.shap_values(row, check_additivity=True)
    expected_values = np.asarray(app.state.explainer.expected_value).reshape(-1)

    if isinstance(shap_values, list):
        contributions = np.asarray(shap_values[1], dtype=float)[0]
        baseline = float(expected_values[1])
    else:
        values = np.asarray(shap_values, dtype=float)
        if values.ndim == 3:
            contributions = values[0, :, 1]
            baseline = float(expected_values[1])
        else:
            contributions = values[0]
            baseline = float(expected_values[0])

    if not np.isclose(baseline + contributions.sum(), score, atol=1e-6):
        raise RuntimeError("SHAP contributions failed the local additivity check.")

    ranked_indices = np.argsort(np.abs(contributions))[::-1]
    factors = [
        {
            "feature": expected[int(index)],
            "value": float(row.iloc[0, int(index)]),
            # Express contributions in percentage points, the same scale as risk_score.
            "contribution": round(float(contributions[index]) * 100, 6),
            "direction": (
                "increases_risk"
                if contributions[index] > 1e-12
                else "decreases_risk" if contributions[index] < -1e-12 else "no_change"
            ),
        }
        for index in ranked_indices
    ]
    level = "high" if score >= 0.7 else "medium" if score >= RISK_THRESHOLD else "low"

    return {
        "risk_score": round(score * 100, 2),
        "risk_level": level,
        "predicted_class": "dropout" if score >= RISK_THRESHOLD else "not_dropout",
        # SHAP explains this model's score, not a real-world or causal reason.
        "cause": "undetermined",
        "explanation": {
            "method": "tree_shap_path_dependent",
            "baseline_score": round(baseline * 100, 6),
            "factors": factors,
        },
    }
