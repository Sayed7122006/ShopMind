"""
sample_data.py
----------------
Generates small fake datasets so you can test the pipeline end-to-end
before the Backend/database is ready. Run this once to create
interactions.csv and products.csv in this folder.
"""

import json
import os
import random
import pandas as pd
from datetime import datetime, timedelta

random.seed(42)

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

PRODUCTS = [
    ("p1", "Wireless Headphones", "Over-ear bluetooth headphones with noise cancellation"),
    ("p2", "Running Shoes", "Lightweight breathable running shoes for daily training"),
    ("p3", "Coffee Maker", "Automatic drip coffee maker with timer"),
    ("p4", "Yoga Mat", "Non-slip yoga mat, extra thick for comfort"),
    ("p5", "Backpack", "Water-resistant laptop backpack with many pockets"),
    ("p6", "Smart Watch", "Fitness tracking smartwatch with heart rate monitor"),
    ("p7", "Desk Lamp", "LED desk lamp with adjustable brightness"),
    ("p8", "Water Bottle", "Insulated stainless steel water bottle, 1 liter"),
    ("p9", "Bluetooth Speaker", "Portable waterproof bluetooth speaker"),
    ("p10", "Office Chair", "Ergonomic office chair with lumbar support"),
]

def load_real_products():
    """Use the frontend's products.json (copied into this folder) if present,
    so the fake interactions/reviews refer to the REAL product ids."""
    path = os.path.join(BASE_DIR, "products.json")
    if not os.path.exists(path):
        return None
    from data_loader import load_products_from_json
    df = load_products_from_json(path)
    return [(str(r.product_id), r.name, r.description) for r in df.itertuples()]


REAL_PRODUCTS = load_real_products()
if REAL_PRODUCTS:
    PRODUCTS = REAL_PRODUCTS

USERS = [f"u{i}" for i in range(1, 21)]
ACTIONS = ["view", "add_to_cart", "purchase"]
ACTION_WEIGHTS = [0.6, 0.25, 0.15]  # most events are just views


def generate_products_csv(path: str = "products.csv"):
    df = pd.DataFrame(PRODUCTS, columns=["product_id", "name", "description"])
    df.to_csv(path, index=False)
    print(f"Wrote {len(df)} products to {path}")


def generate_interactions_csv(path: str = "interactions.csv", n_events: int = 500):
    rows = []
    start = datetime(2026, 1, 1)
    for _ in range(n_events):
        user_id = random.choice(USERS)
        product_id = random.choice(PRODUCTS)[0]
        action = random.choices(ACTIONS, weights=ACTION_WEIGHTS)[0]
        timestamp = start + timedelta(hours=random.randint(0, 24 * 60))
        rows.append((user_id, product_id, action, timestamp.isoformat()))

    df = pd.DataFrame(rows, columns=["user_id", "product_id", "action", "timestamp"])
    df.to_csv(path, index=False)
    print(f"Wrote {len(df)} interactions to {path}")


if __name__ == "__main__":
    generate_products_csv()
    generate_interactions_csv()
