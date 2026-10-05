import pandas as pd
import pytest

pytest.importorskip("torch")
pytest.importorskip("sentence_transformers")

import search  # noqa: E402
from helpers import FakeSentenceModel  # noqa: E402


def make_engine(monkeypatch, products=None):
    monkeypatch.setattr(search, "SentenceTransformer", FakeSentenceModel)
    if products is None:
        products = pd.DataFrame({
            "product_id": ["p1", "p2", "p3"],
            "name": ["Running Shoes", "Coffee Maker", "Yoga Mat"],
            "description": ["lightweight breathable shoes for training",
                            "automatic drip coffee machine with timer",
                            "non-slip thick mat"],
        })
    return search.SmartSearch(products)


def test_search_ranks_relevant_product_first(monkeypatch):
    engine = make_engine(monkeypatch)
    assert engine.search("running shoes")[0]["product_id"] == "p1"
    assert engine.search("coffee")[0]["product_id"] == "p2"


def test_search_results_have_expected_shape_and_are_sorted(monkeypatch):
    results = make_engine(monkeypatch).search("shoes for training", top_n=3)
    assert all(set(r) == {"product_id", "name", "score"} for r in results)
    assert all(isinstance(r["score"], float) for r in results)
    scores = [r["score"] for r in results]
    assert scores == sorted(scores, reverse=True)


def test_search_top_n_respected(monkeypatch):
    assert len(make_engine(monkeypatch).search("mat", top_n=2)) == 2


def test_search_top_n_larger_than_catalog_returns_whole_catalog(monkeypatch):
    assert len(make_engine(monkeypatch).search("mat", top_n=50)) == 3


def test_search_handles_product_with_empty_description(monkeypatch):
    products = pd.DataFrame({"product_id": ["p1"], "name": ["Desk Lamp"], "description": [""]})
    results = make_engine(monkeypatch, products).search("lamp")
    assert results[0]["product_id"] == "p1"
