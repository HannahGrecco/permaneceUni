"""Contract checks for the loaded prediction service (run after explicit training)."""

import csv
import unittest

import joblib
import shap
from fastapi import HTTPException

from ml_service.api import app, predict
from ml_service.config import DATASET_PATH, DROPPED_COLUMNS, MODEL_PATH, TARGET_COLUMN
from ml_service.api import PredictionRequest


class PredictionContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        app.state.artifact = joblib.load(MODEL_PATH)
        app.state.explainer = shap.TreeExplainer(
            app.state.artifact["model"],
            feature_perturbation="tree_path_dependent",
            feature_names=app.state.artifact["features"],
        )
        with DATASET_PATH.open(newline="", encoding="utf-8-sig") as source:
            cls.csv_row = next(csv.DictReader(source))
        cls.student = {
            name: float(value)
            for name, value in cls.csv_row.items()
            if name not in DROPPED_COLUMNS + [TARGET_COLUMN]
        }

    def test_prediction_returns_local_shap_contributions_that_reconstruct_score(self) -> None:
        result = predict(PredictionRequest(student=self.student))
        self.assertEqual(result["cause"], "undetermined")
        self.assertIn(result["risk_level"], {"low", "medium", "high"})
        self.assertIn(result["predicted_class"], {"dropout", "not_dropout"})
        explanation = result["explanation"]
        self.assertEqual(explanation["method"], "tree_shap_path_dependent")
        self.assertEqual(len(explanation["factors"]), len(app.state.artifact["features"]))
        self.assertAlmostEqual(
            explanation["baseline_score"]
            + sum(factor["contribution"] for factor in explanation["factors"]),
            result["risk_score"],
            places=3,
        )
        self.assertTrue(all(factor["direction"] in {"increases_risk", "decreases_risk", "no_change"} for factor in explanation["factors"]))
        self.assertTrue(0 <= result["risk_score"] <= 100)

    def test_missing_feature_is_rejected(self) -> None:
        incomplete = dict(self.student)
        incomplete.pop(next(iter(incomplete)))
        with self.assertRaises(HTTPException) as error:
            predict(PredictionRequest(student=incomplete))
        self.assertEqual(error.exception.status_code, 422)

    def test_non_finite_feature_is_rejected(self) -> None:
        invalid = dict(self.student)
        invalid[next(iter(invalid))] = float("nan")
        with self.assertRaises(HTTPException) as error:
            predict(PredictionRequest(student=invalid))
        self.assertEqual(error.exception.status_code, 422)


if __name__ == "__main__":
    unittest.main()
