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
"""

import json
import pandas as pd
from sqlalchemy import create_engine


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


# ---------------------------------------------------------------------------
# Option C: Load products straight from the frontend's products.json
# ---------------------------------------------------------------------------
def load_products_from_json(path: str) -> pd.DataFrame:
    """
    Load the product catalog from the frontend's products.json.

    Handles both a top-level list of products, and a dict with the list
    under a "products" key. Column names are normalized to what the rest
    of the ML code expects: product_id, name, description.
    Missing fields (e.g. no description yet) are filled with an empty string
    so Smart Search doesn't break.
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
    Load interaction events from a generic 'interactions' table, if one
    exists. Most ShopMind-style schemas won't have this -- use
    load_interactions_from_shopmind_sql() instead.
    """
    engine = get_engine(connection_string)
    query = "SELECT user_id, product_id, action, timestamp FROM interactions"
    if since:
        query += f" WHERE timestamp > '{since}'"
    return pd.read_sql(query, engine)


def load_interactions_from_shopmind_sql(connection_string: str) -> pd.DataFrame:
    """
    Build an interactions table from ShopMind's real schema: there's no
    dedicated event-log table, but there IS a real `cart` table (current
    add-to-cart state per user) and real `orders` + `order_items` tables
    (completed purchases). We combine both into the same
    [user_id, product_id, action, timestamp] shape the rest of the ML
    code expects.

    Note: `cart` only reflects CURRENT cart contents (items get deleted
    on removal/checkout), not a full history -- still a real signal,
    just not as rich as a proper event log would be.
    """
    engine = get_engine(connection_string)

    cart_df = pd.read_sql(
        """
        SELECT user_id, product_id, 'add_to_cart' AS action, NOW() AS timestamp
        FROM cart
        """,
        engine,
    )

    purchases_df = pd.read_sql(
        """
        SELECT u.id AS user_id, oi.product_id, 'purchase' AS action, o.order_date AS timestamp
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN users u ON u.Email = o.user_email
        """,
        engine,
    )

    return pd.concat([cart_df, purchases_df], ignore_index=True)


def load_products_from_sql(connection_string: str) -> pd.DataFrame:
    """
    Load the product catalog from ShopMind's real `products` table.
    No `description` column exists in this schema, so Smart Search runs
    on product name alone (clean_products() fills description with "").
    """
    engine = get_engine(connection_string)
    query = "SELECT id AS product_id, name, category FROM products"
    return pd.read_sql(query, engine)
