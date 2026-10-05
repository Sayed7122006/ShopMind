"""
data_loader.py
---------------
Loads raw user-interaction data for the ML pipeline, either from a CSV file
(handed over by the Backend team) or directly from the SQL database.

Expected columns in the interactions data:
    user_id      -> str or int, identifies the shopper
    product_id   -> str or int, identifies the product
    action       -> one of: "view", "add_to_cart", "purchase"
    timestamp    -> ISO date/time string

Expected columns in the products data (needed for Smart Search):
    product_id   -> str or int
    name         -> str, product name
    description  -> str, product description (optional but improves search)

Expected columns in the reviews data (needed for Review Sentiment Analysis):
    product_id   -> str or int
    review_text  -> str, the customer's written review
    rating       -> 1-5 (optional)
    user_id, review_id, timestamp -> optional, not used by the model
"""

import json
import pandas as pd
from sqlalchemy import create_engine, text


# ---------------------------------------------------------------------------
# Option A: Load from CSV (quickest way to start before a DB connection exists)
# ---------------------------------------------------------------------------
def load_interactions_from_csv(path: str) -> pd.DataFrame:
    """Load the raw interactions log from a CSV file."""
    df = pd.read_csv(path)
    return df


def load_products_from_csv(path: str) -> pd.DataFrame:
    """Load the product catalog from a CSV file."""
    df = pd.read_csv(path)
    return df


def load_reviews_from_csv(path: str) -> pd.DataFrame:
    """Load product reviews from a CSV file."""
    return pd.read_csv(path)


# The frontend's products.json has no description field, only a category. Smart
# Search works much better with a few descriptive words than with the name
# alone (e.g. so "phone" finds "Redmi 13C Dual SIM"), so we derive a fallback
# description from the category.
CATEGORY_KEYWORDS = {
    "mobiles": "mobile phone smartphone",
    "electronics": "electronics device gadget",
    "appliances": "home appliance kitchen household",
}


def _description_from_category(category) -> str:
    if not isinstance(category, str) or not category.strip():
        return ""
    category = category.strip()
    keywords = CATEGORY_KEYWORDS.get(category.lower(), "")
    return f"{category} {keywords}".strip()


# ---------------------------------------------------------------------------
# Option C: Load products straight from the frontend's products.json
# ---------------------------------------------------------------------------
def load_products_from_json(path: str) -> pd.DataFrame:
    """
    Load the product catalog from the frontend's products.json.

    Handles both a top-level list of products, and a dict with the list
    under a "products" key. Column names are normalized to what the rest
    of the ML code expects: product_id, name, description.
    If there is no description, one is derived from the category field
    ("categories" / "category") so Smart Search has more than the name to
    work with; if there is no category either, it is an empty string.
    """
    with open(path, "r", encoding="utf-8") as f:
        raw = json.load(f)

    items = raw.get("products", raw) if isinstance(raw, dict) else raw
    df = pd.DataFrame(items)

    # Normalize whichever id/name/description field names the frontend used.
    rename_map = {}
    for candidate in ["id", "productId", "product_id"]:
        if candidate in df.columns:
            rename_map[candidate] = "product_id"
            break
    for candidate in ["name", "title", "productName"]:
        if candidate in df.columns:
            rename_map[candidate] = "name"
            break
    for candidate in ["description", "desc", "details"]:
        if candidate in df.columns:
            rename_map[candidate] = "description"
            break

    df = df.rename(columns=rename_map)

    if "description" not in df.columns:
        category_col = next((c for c in ("categories", "category") if c in df.columns), None)
        if category_col:
            df["description"] = df[category_col].map(_description_from_category)
        else:
            df["description"] = ""

    return df[["product_id", "name", "description"] + [
        c for c in df.columns if c not in ("product_id", "name", "description")
    ]]


# ---------------------------------------------------------------------------
# Option B: Load directly from SQL (PostgreSQL by default)
# ---------------------------------------------------------------------------
def get_engine(connection_string: str):
    """
    Create a SQLAlchemy engine.

    Example connection_string:
        "postgresql://username:password@host:port/database_name"

    Use a READ-ONLY database user here if possible.
    """
    return create_engine(connection_string)


def load_interactions_from_sql(connection_string: str, since: str = None) -> pd.DataFrame:
    """
    Load interaction events from the operational database.

    since: optional ISO date string (e.g. "2026-01-01") to only pull recent rows.
    """
    engine = get_engine(connection_string)
    # Parameterized query: never build SQL by pasting user input into a string.
    if since:
        query = text(
            "SELECT user_id, product_id, action, timestamp FROM interactions "
            "WHERE timestamp > :since"
        )
        return pd.read_sql(query, engine, params={"since": since})
    query = text("SELECT user_id, product_id, action, timestamp FROM interactions")
    return pd.read_sql(query, engine)


def load_products_from_sql(connection_string: str) -> pd.DataFrame:
    """Load the product catalog from the operational database."""
    engine = get_engine(connection_string)
    query = "SELECT product_id, name, description FROM products"
    return pd.read_sql(query, engine)


def load_reviews_from_sql(connection_string: str) -> pd.DataFrame:
    """Load product reviews from the operational database."""
    engine = get_engine(connection_string)
    query = text("SELECT product_id, review_text, rating FROM reviews")
    return pd.read_sql(query, engine)
