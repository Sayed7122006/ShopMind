"""
data_cleaning.py
------------------
Turns the raw interactions/products tables into clean, model-ready data.
This replaces the Bronze -> Silver step that the Data Engineer role would
normally have handled.
"""

import pandas as pd


def clean_interactions(df: pd.DataFrame) -> pd.DataFrame:
    """
    Clean the raw interactions log.

    Steps:
      1. Drop rows missing a user_id or product_id (unusable for modeling).
      2. Drop exact duplicate events.
      3. Parse timestamp into a real datetime type.
      4. Normalize the `action` column to lowercase.
      5. Keep only the actions we actually use.
    """
    df = df.copy()

    df = df.dropna(subset=["user_id", "product_id", "action"])
    df = df.drop_duplicates()

    df["timestamp"] = pd.to_datetime(df["timestamp"], errors="coerce")
    df = df.dropna(subset=["timestamp"])

    df["action"] = df["action"].str.lower().str.strip()
    valid_actions = {"view", "add_to_cart", "purchase"}
    df = df[df["action"].isin(valid_actions)]

    df["user_id"] = df["user_id"].astype(str)
    df["product_id"] = df["product_id"].astype(str)

    return df.reset_index(drop=True)


def clean_products(df: pd.DataFrame) -> pd.DataFrame:
    """
    Clean the product catalog.

    Steps:
      1. Drop rows with no product_id or name.
      2. Fill missing descriptions with an empty string (so search doesn't break).
      3. Strip whitespace from text fields.
    """
    df = df.copy()

    df = df.dropna(subset=["product_id", "name"])
    # Note: df.get("description", "") returns a plain str when the column is
    # missing, and str has no .fillna() -- so handle the missing-column case
    # explicitly instead.
    if "description" not in df.columns:
        df["description"] = ""
    df["description"] = df["description"].fillna("").astype(str)

    df["product_id"] = df["product_id"].astype(str)
    df["name"] = df["name"].astype(str).str.strip()
    df["description"] = df["description"].str.strip()

    return df.reset_index(drop=True)


def build_user_item_matrix(interactions: pd.DataFrame) -> pd.DataFrame:
    """
    Build a user x product interaction-strength matrix, used by the
    Recommendation System. Different actions get different weights:
    a purchase counts more than an add-to-cart, which counts more than a view.
    """
    weights = {"view": 1, "add_to_cart": 3, "purchase": 5}
    df = interactions.copy()
    df["weight"] = df["action"].map(weights)

    matrix = (
        df.groupby(["user_id", "product_id"])["weight"]
        .sum()
        .unstack(fill_value=0)
    )
    return matrix


def clean_reviews(df: pd.DataFrame) -> pd.DataFrame:
    """
    Clean the raw product reviews (input for Review Sentiment Analysis).

    Steps:
      1. Drop rows missing a product_id or review_text.
      2. Strip whitespace and drop reviews that are empty after stripping.
      3. Drop duplicate reviews (same text on the same product).
      4. Make product_id a string (matches products / interactions).
      5. If a rating column exists, coerce it to numeric and blank out
         anything outside 1-5 instead of letting a bad value skew averages.
    """
    df = df.copy()

    df = df.dropna(subset=["product_id", "review_text"])
    df["review_text"] = df["review_text"].astype(str).str.strip()
    df = df[df["review_text"] != ""]
    df = df.drop_duplicates(subset=["product_id", "review_text"])

    df["product_id"] = df["product_id"].astype(str)

    if "rating" in df.columns:
        df["rating"] = pd.to_numeric(df["rating"], errors="coerce")
        df["rating"] = df["rating"].where(df["rating"].between(1, 5))

    return df.reset_index(drop=True)
