import sys

import pytest

pytest.importorskip("torch")
pytest.importorskip("sentence_transformers")
pytest.importorskip("fastapi")
pytest.importorskip("httpx")

from fastapi.testclient import TestClient  # noqa: E402

from helpers import FakeSentenceModel, fake_classifier  # noqa: E402


@pytest.fixture(scope="module")
def api_module():
    """Import api.py with the heavy models replaced by fast offline fakes."""
    import search
    import sentiment

    mp = pytest.MonkeyPatch()
    mp.setattr(search, "SentenceTransformer", FakeSentenceModel)
    mp.setattr(sentiment, "_load_default_classifier", lambda *a, **k: fake_classifier)
    sys.modules.pop("api", None)
    import api
    yield api
    mp.undo()
    sys.modules.pop("api", None)


@pytest.fixture(scope="module")
def client(api_module):
    return TestClient(api_module.app)


def test_root_ok(client):
    r = client.get("/")
    assert r.status_code == 200 and r.json()["status"] == "ok"


# ---- /search
def test_search_returns_results(client):
    r = client.get("/search", params={"q": "running shoes", "top_n": 3})
    assert r.status_code == 200
    body = r.json()
    assert body["query"] == "running shoes"
    assert 1 <= len(body["results"]) <= 3


@pytest.mark.parametrize("params", [{}, {"q": ""}, {"q": "x", "top_n": 0}, {"q": "x", "top_n": 21}])
def test_search_validation_errors(client, params):
    assert client.get("/search", params=params).status_code == 422


# ---- /recommend
def test_recommend_known_user(client, api_module):
    user = api_module.user_item_matrix.index[0]
    r = client.get(f"/recommend/{user}", params={"top_n": 3})
    assert r.status_code == 200
    assert len(r.json()["recommendations"]) <= 3


def test_recommend_unknown_user_returns_empty_with_note(client):
    body = client.get("/recommend/nobody-here").json()
    assert body["recommendations"] == [] and "note" in body


def test_recommend_validation_error(client):
    assert client.get("/recommend/u1", params={"top_n": 0}).status_code == 422


# ---- /sentiment
def test_sentiment_endpoint(client):
    body = client.get("/sentiment", params={"text": "I love this"}).json()
    assert body["label"] == "positive"


def test_sentiment_rejects_blank_text(client):
    assert client.get("/sentiment", params={"text": "   "}).status_code == 422
    assert client.get("/sentiment").status_code == 422


# ---- /products/{id}/review-summary
def test_review_summary_for_reviewed_product(client, api_module):
    ids = set(api_module.review_sentiment.reviews["product_id"]) & set(api_module.PRODUCT_NAMES)
    if not ids:
        pytest.skip("no product has reviews (run sample_reviews.py)")
    pid = sorted(ids)[0]
    body = client.get(f"/products/{pid}/review-summary").json()
    assert body["review_count"] > 0 and "summary" in body


def test_review_summary_unknown_product_is_404(client):
    assert client.get("/products/does-not-exist/review-summary").status_code == 404


def test_review_summary_product_without_reviews(client, api_module):
    reviewed = set(api_module.review_sentiment.reviews["product_id"])
    unreviewed = [p for p in api_module.PRODUCT_NAMES if p not in reviewed]
    if not unreviewed:
        pytest.skip("every product has reviews")
    body = client.get(f"/products/{unreviewed[0]}/review-summary").json()
    assert body["review_count"] == 0


# ---- /review-summaries
def test_review_summaries_overview(client):
    body = client.get("/review-summaries").json()
    assert body["count"] == len(body["products"])
    for p in body["products"]:
        assert p["overall_sentiment"] in {"positive", "negative", "mixed"}
