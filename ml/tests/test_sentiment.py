import pandas as pd
import pytest

from helpers import fake_classifier
from sentiment import ReviewSentiment


def make_reviews():
    return pd.DataFrame({
        "product_id": ["p1", "p1", "p1", "p1", "p2", "p2", "p3"],
        "review_text": ["I love it", "Great sound", "Terrible, broke in a week",
                        "It is okay", "I love this", "Great value", "Awful"],
        "rating": [5, 4, 1, 3, 5, 5, 1],
    })


def make_model(**kwargs):
    return ReviewSentiment(make_reviews(), classifier=fake_classifier, **kwargs)


# ------------------------------ analyze_text ------------------------------
def test_analyze_text_positive():
    r = make_model().analyze_text("I love it")
    assert r["label"] == "positive"
    assert r["score"] == pytest.approx(0.99)
    assert r["confidence"] == pytest.approx(0.99)


def test_analyze_text_negative_has_negative_signed_score():
    r = make_model().analyze_text("Awful product")
    assert r["label"] == "negative"
    assert r["score"] == pytest.approx(-0.97)


def test_analyze_text_low_confidence_is_neutral_with_zero_score():
    r = make_model().analyze_text("it is a lamp")
    assert r["label"] == "neutral"
    assert r["score"] == 0.0


def test_neutral_threshold_is_configurable():
    r = make_model(neutral_threshold=0.5).analyze_text("it is a lamp")
    assert r["label"] == "positive"  # 0.52 >= 0.5


@pytest.mark.parametrize("bad", ["", "   ", None, 123])
def test_analyze_text_rejects_empty_or_non_string(bad):
    with pytest.raises(ValueError):
        make_model().analyze_text(bad)


# ---------------------------- summarize_product ----------------------------
def test_summary_counts_and_percentages():
    s = make_model().summarize_product("p1")
    assert s["review_count"] == 4
    assert (s["positive"], s["negative"], s["neutral"]) == (2, 1, 1)
    assert s["positive_pct"] == 50.0
    assert s["negative_pct"] == 25.0


def test_summary_average_rating_and_overall_mixed():
    s = make_model().summarize_product("p1")
    assert s["avg_rating"] == pytest.approx(3.25)
    assert s["overall_sentiment"] == "mixed"   # (0.99 + 0.95 - 0.97 + 0) / 4 ~ 0.24


def test_summary_picks_most_confident_positive_and_negative_reviews():
    s = make_model().summarize_product("p1")
    assert s["top_positive_review"] == "I love it"
    assert s["top_negative_review"] == "Terrible, broke in a week"


def test_summary_all_positive_product():
    s = make_model().summarize_product("p2")
    assert s["overall_sentiment"] == "positive"
    assert s["top_negative_review"] is None


def test_summary_all_negative_product():
    s = make_model().summarize_product("p3")
    assert s["overall_sentiment"] == "negative"
    assert s["top_positive_review"] is None
    assert s["review_count"] == 1
    assert "1 review:" in s["summary"]   # singular, not "1 reviews"


def test_summary_unknown_product_returns_none():
    assert make_model().summarize_product("zzz") is None


def test_summary_accepts_non_string_product_id():
    reviews = pd.DataFrame({"product_id": [7], "review_text": ["I love it"]})
    m = ReviewSentiment(reviews, classifier=fake_classifier)
    assert m.summarize_product(7)["review_count"] == 1


def test_summary_without_rating_column_has_none_avg_rating():
    reviews = pd.DataFrame({"product_id": ["p1"], "review_text": ["I love it"]})
    s = ReviewSentiment(reviews, classifier=fake_classifier).summarize_product("p1")
    assert s["avg_rating"] is None
    assert "Average rating" not in s["summary"]


# ------------------------------ summarize_all ------------------------------
def test_summarize_all_covers_every_product_most_liked_first():
    summaries = make_model().summarize_all()
    assert [s["product_id"] for s in summaries] == ["p2", "p1", "p3"]


def test_summarize_all_on_empty_reviews_is_empty_list():
    empty = pd.DataFrame(columns=["product_id", "review_text"])
    assert ReviewSentiment(empty, classifier=fake_classifier).summarize_all() == []


# ------------------------------- construction -------------------------------
def test_all_reviews_are_classified_in_a_single_batch():
    calls = []

    def spy(texts):
        calls.append(len(texts))
        return fake_classifier(texts)

    ReviewSentiment(make_reviews(), classifier=spy)
    assert calls == [7]


def test_empty_reviews_dataframe_does_not_call_classifier():
    def boom(texts):
        raise AssertionError("classifier should not be called")

    empty = pd.DataFrame(columns=["product_id", "review_text", "rating"])
    m = ReviewSentiment(empty, classifier=boom)
    assert m.summarize_product("p1") is None
