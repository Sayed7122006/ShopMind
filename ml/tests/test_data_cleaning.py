import pandas as pd
import pytest

from data_cleaning import (
    build_user_item_matrix,
    clean_interactions,
    clean_products,
    clean_reviews,
)


def raw_interactions():
    return pd.DataFrame({
        "user_id":   ["u1", "u1", "u1", None, "u2", "u2", "u3"],
        "product_id": ["p1", "p1", "p2", "p1", "p1", "p2", "p3"],
        "action":    ["view", "view", "ADD_TO_CART ", "view", "purchase", "refund", "view"],
        "timestamp": ["2026-01-01", "2026-01-01", "2026-01-02", "2026-01-03",
                      "2026-01-04", "2026-01-05", "not-a-date"],
    })


# ----------------------------- clean_interactions -----------------------------
def test_clean_interactions_drops_bad_rows():
    out = clean_interactions(raw_interactions())
    # kept: u1/p1/view, u1/p2/add_to_cart, u2/p1/purchase
    # dropped: duplicate, missing user_id, invalid action ("refund"), bad timestamp
    assert len(out) == 3


def test_clean_interactions_normalizes_action():
    out = clean_interactions(raw_interactions())
    assert set(out["action"]) == {"view", "add_to_cart", "purchase"}


def test_clean_interactions_casts_ids_to_str_and_parses_timestamp():
    df = pd.DataFrame({"user_id": [1, 2], "product_id": [10, 20],
                       "action": ["view", "view"],
                       "timestamp": ["2026-01-01", "2026-01-02"]})
    out = clean_interactions(df)
    assert out["user_id"].tolist() == ["1", "2"]
    assert out["product_id"].tolist() == ["10", "20"]
    assert pd.api.types.is_datetime64_any_dtype(out["timestamp"])


def test_clean_interactions_all_invalid_returns_empty_not_error():
    df = pd.DataFrame({"user_id": ["u1"], "product_id": ["p1"],
                       "action": ["refund"], "timestamp": ["2026-01-01"]})
    assert clean_interactions(df).empty


def test_clean_interactions_does_not_mutate_input():
    df = raw_interactions()
    before = df.copy()
    clean_interactions(df)
    pd.testing.assert_frame_equal(df, before)


# ------------------------------- clean_products -------------------------------
def test_clean_products_drops_missing_id_or_name_and_strips():
    df = pd.DataFrame({"product_id": ["p1", None, "p3"],
                       "name": ["  Shoes ", "Ghost", None],
                       "description": ["  nice  ", "x", "y"]})
    out = clean_products(df)
    assert out["product_id"].tolist() == ["p1"]
    assert out["name"].tolist() == ["Shoes"]
    assert out["description"].tolist() == ["nice"]


def test_clean_products_fills_missing_description_values():
    df = pd.DataFrame({"product_id": ["p1"], "name": ["Shoes"], "description": [None]})
    assert clean_products(df)["description"].tolist() == [""]


def test_clean_products_works_without_description_column():
    # Regression test: this used to crash (str has no .fillna()).
    df = pd.DataFrame({"product_id": ["p1"], "name": ["Shoes"]})
    assert clean_products(df)["description"].tolist() == [""]


# --------------------------- build_user_item_matrix ---------------------------
def test_user_item_matrix_applies_action_weights():
    df = pd.DataFrame({
        "user_id":   ["u1", "u1", "u1", "u2"],
        "product_id": ["p1", "p1", "p2", "p1"],
        "action":    ["view", "purchase", "add_to_cart", "view"],
    })
    m = build_user_item_matrix(df)
    assert m.loc["u1", "p1"] == 1 + 5      # view + purchase
    assert m.loc["u1", "p2"] == 3          # add_to_cart
    assert m.loc["u2", "p1"] == 1
    assert m.loc["u2", "p2"] == 0          # never interacted -> 0, not NaN
    assert m.shape == (2, 2)


# -------------------------------- clean_reviews --------------------------------
def test_clean_reviews_drops_empty_missing_and_duplicate():
    df = pd.DataFrame({
        "product_id": ["p1", "p1", "p1", None, "p2"],
        "review_text": ["Great!", "Great!", "   ", "Nice", None],
        "rating": [5, 5, 4, 4, 3],
    })
    out = clean_reviews(df)
    assert out["review_text"].tolist() == ["Great!"]


def test_clean_reviews_invalid_ratings_become_nan():
    df = pd.DataFrame({
        "product_id": ["p1", "p1", "p1", "p1"],
        "review_text": ["a", "b", "c", "d"],
        "rating": [5, 9, 0, "abc"],
    })
    out = clean_reviews(df)
    assert out["rating"].iloc[0] == 5
    assert out["rating"].iloc[1:].isna().all()


def test_clean_reviews_without_rating_column_and_int_ids():
    df = pd.DataFrame({"product_id": [1], "review_text": ["  ok  "]})
    out = clean_reviews(df)
    assert out["product_id"].tolist() == ["1"]
    assert out["review_text"].tolist() == ["ok"]
    assert "rating" not in out.columns
