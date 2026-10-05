# ML Module — E-commerce Project (TPT-Project)

Implements the AI/ML Engineer deliverables (the plan requires at least 2; we ship 3):
1. **Recommendation System** — item-based collaborative filtering (`recommender.py`)
2. **Smart Search** — semantic product search with sentence embeddings (`search.py`)
3. **Review Sentiment Analysis** — transformer sentiment classifier + per-product review summary (`sentiment.py`)

All three are served through a FastAPI app (`api.py`) that the Frontend team can call directly.

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
fake events, so you can test every script before the Backend is ready. Then generate the
sample reviews (they reference the product ids `p1`..`p10` from the step above):

```bash
python sample_reviews.py
```

This creates `reviews.csv`. If it is missing, the API still starts — the review endpoints
simply report "no reviews".

## 2. Run the API

```bash
uvicorn api:app --reload --port 8001
```

Then open:
- `http://127.0.0.1:8001/recommend/u1` — recommended products for user u1
- `http://127.0.0.1:8001/search?q=shoes+for+running` — semantic search
- `http://127.0.0.1:8001/sentiment?text=I+love+this+product` — sentiment of any text
- `http://127.0.0.1:8001/products/p1/review-summary` — review summary for one product
- `http://127.0.0.1:8001/review-summaries` — sentiment verdict for every product, most-liked first
- `http://127.0.0.1:8001/docs` — interactive Swagger UI for every endpoint

The first start downloads two small pretrained models (the sentence-embedding model and the
DistilBERT sentiment model); this needs an internet connection once, then they are cached.

## Using the real product catalog

`products.json` (copied from `frontend/products.json`) is already in this folder, and the API
uses it automatically instead of the sample `products.csv`. The real file has no `description`
field, so `data_loader.py` builds one from the category (e.g. `mobiles` -> "mobiles mobile phone
smartphone") to give Smart Search more than the product name to work with. If the frontend adds
real descriptions later, they are used as-is. Keep the file in sync by re-copying it whenever the
frontend catalog changes, then restart the server.

There is still no real interaction-tracking data (the frontend cart/wishlist is client-side
only), so `python sample_data.py` generates fake interactions and `python sample_reviews.py` fake
reviews, **using the real product ids from `products.json`** (so recommendations and review
summaries work for the real catalog). Re-run both after the catalog changes. Smart Search runs on
the real product data; the Recommender and Sentiment models demonstrate the full approach on
synthetic data until the Backend provides real events and reviews.

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
| `data_loader.py` | Loads raw data (interactions, products, reviews) from CSV, JSON or SQL |
| `data_cleaning.py` | Cleans data + builds the user-item matrix |
| `recommender.py` | Model 1: Recommendation System |
| `search.py` | Model 2: Smart Search |
| `sentiment.py` | Model 3: Review Sentiment Analysis |
| `api.py` | FastAPI app exposing all three models |
| `sample_data.py` | Generates fake interactions + products |
| `sample_reviews.py` | Generates fake product reviews |
| `check_sentiment.py` | Runs the REAL sentiment model on known sentences + prints every product's verdict |
| `tests/` | Unit tests (pytest) |

## Review Sentiment Analysis

`sentiment.py` classifies each review with `distilbert-base-uncased-finetuned-sst-2-english`.
That model is binary (positive/negative), so predictions with confidence below 0.6 are
reported as `neutral`. Per product, `/products/{id}/review-summary` returns the
positive/negative/neutral counts, an overall verdict (`positive` / `mixed` / `negative`), the
average star rating, and the most confident positive and negative reviews as an extractive
summary.

To verify the real model works on your machine: `python check_sentiment.py` (prints PASS/FAIL per
test sentence, then the positive/negative verdict of every product).

## Running the tests

```bash
pip install -r requirements-dev.txt
pytest
```

The tests use small fake models, so they run offline and fast. `test_search.py` and
`test_api.py` need `torch` + `sentence-transformers` installed (they are skipped otherwise).
`test_api.py` expects the sample data files (`products.csv`, `interactions.csv`, `reviews.csv`).

## Notes
- The recommender uses weighted interactions: `purchase` > `add_to_cart` > `view`.
  Adjust the weights in `data_cleaning.py` -> `build_user_item_matrix` if needed.
- With very few products (like the sample data), a user who's touched most of the
  catalog will get an empty recommendation list — that's expected on tiny datasets,
  not a bug. It resolves itself with a real product catalog.
- `search.py` downloads a small pretrained model (`all-MiniLM-L6-v2`) the first time
  it runs — needs an internet connection once, then it's cached locally.
