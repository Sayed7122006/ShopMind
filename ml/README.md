# ML Module — E-commerce Project (TPT-Project)

Implements the two required AI/ML Engineer deliverables:
1. **Recommendation System** — item-based collaborative filtering (`recommender.py`)
2. **Smart Search** — semantic product search with sentence embeddings (`search.py`)

Both are served through a FastAPI app (`api.py`) that the Frontend team can call directly.

## Setup

```bash
python -m venv venv
venv\Scripts\activate        # Windows
# source venv/bin/activate   # Mac/Linux

pip install -r requirements.txt
```

## 1. Try it with sample data first

No real database yet? Generate fake test data:

```bash
python sample_data.py
```

This creates `interactions.csv` and `products.csv` in this folder with realistic-looking
fake events, so you can test every script before the Backend is ready.

## 2. Run the API

```bash
uvicorn api:app --reload --port 8001
```

Then open:
- `http://127.0.0.1:8001/recommend/u1` — recommended products for user u1
- `http://127.0.0.1:8001/search?q=shoes+for+running` — semantic search

## Using the real product catalog

Drop the frontend's real `products.json` into this folder (same name). The API
automatically uses it instead of the sample `products.csv` if it's present —
no code changes needed. Works even if it has no `description` field.

There's currently no real interaction-tracking data (the frontend cart/wishlist
is client-side only, not sent to a backend yet), so the Recommender keeps using
the generated sample `interactions.csv` until that exists. This is a reasonable
stand-in for a demo/submission: the Smart Search runs on real product data, and
the Recommender demonstrates the full working approach on realistic synthetic data.

## 3. Switching to real data later

### From CSV (handed over by Backend)
Replace `interactions.csv` / `products.csv` with the real exports — same column names,
nothing else changes.

### From SQL directly
In `api.py`, swap:
```python
raw_interactions = load_interactions_from_csv("interactions.csv")
raw_products = load_products_from_csv("products.csv")
```
for:
```python
from data_loader import load_interactions_from_sql, load_products_from_sql

CONNECTION_STRING = "postgresql://username:password@host:port/database_name"
raw_interactions = load_interactions_from_sql(CONNECTION_STRING)
raw_products = load_products_from_sql(CONNECTION_STRING)
```
Ask the Backend developer for a **read-only** database user — don't use a user with
write access for this.

## File overview

| File | Purpose |
|---|---|
| `data_loader.py` | Loads raw data from CSV or SQL |
| `data_cleaning.py` | Cleans data + builds the user-item matrix |
| `recommender.py` | Model 1: Recommendation System |
| `search.py` | Model 2: Smart Search |
| `api.py` | FastAPI app exposing both models |
| `sample_data.py` | Generates fake test data |

## Notes
- The recommender uses weighted interactions: `purchase` > `add_to_cart` > `view`.
  Adjust the weights in `data_cleaning.py` -> `build_user_item_matrix` if needed.
- With very few products (like the sample data), a user who's touched most of the
  catalog will get an empty recommendation list — that's expected on tiny datasets,
  not a bug. It resolves itself with a real product catalog.
- `search.py` downloads a small pretrained model (`all-MiniLM-L6-v2`) the first time
  it runs — needs an internet connection once, then it's cached locally.
