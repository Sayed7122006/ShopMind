"""
api.py
-------
Wraps both ML models (Recommender + SmartSearch) in a FastAPI app so the
Frontend can call them like any other API.

Run locally with:
    uvicorn api:app --reload --port 8001

Endpoints:
    GET /recommend/{user_id}?top_n=5
    GET /search?q=running+shoes&top_n=5
"""

from fastapi import FastAPI, Query
from fastapi.middleware.cors import CORSMiddleware
import pandas as pd

import os

from data_loader import load_interactions_from_csv, load_products_from_csv, load_products_from_json
from data_cleaning import clean_interactions, clean_products, build_user_item_matrix
from recommender import Recommender
from search import SmartSearch

app = FastAPI(title="E-commerce ML API")

# Allow the frontend (running on a different port/origin) to call this API.
# "*" is fine for local development; narrow it to the real frontend URL
# before going to production.
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# ---------------------------------------------------------------------------
# Products: load from the real frontend products.json if it's present in this
# folder, otherwise fall back to the sample products.csv for local testing.
# Interactions: no real event-tracking exists yet (frontend cart/wishlist is
# client-side only), so we use the generated sample interactions.csv for now.
# Once the backend exposes real order/event data, swap these loader calls for
# load_interactions_from_sql / load_products_from_sql -- nothing else here
# needs to change.
# ---------------------------------------------------------------------------
PRODUCTS_JSON_PATH = "products.json"

raw_interactions = load_interactions_from_csv("interactions.csv")
if os.path.exists(PRODUCTS_JSON_PATH):
    raw_products = load_products_from_json(PRODUCTS_JSON_PATH)
else:
    raw_products = load_products_from_csv("products.csv")

interactions = clean_interactions(raw_interactions)
products = clean_products(raw_products)

user_item_matrix = build_user_item_matrix(interactions)
recommender = Recommender(user_item_matrix)
smart_search = SmartSearch(products)


@app.get("/")
def root():
    return {"status": "ok", "message": "E-commerce ML API is running"}


@app.get("/recommend/{user_id}")
def recommend(user_id: str, top_n: int = Query(5, ge=1, le=20)):
    """Return recommended product_ids for a given user."""
    product_ids = recommender.recommend_for_user(user_id, top_n=top_n)
    if not product_ids:
        return {"user_id": user_id, "recommendations": [], "note": "no data for this user yet"}

    results = products[products["product_id"].isin(product_ids)][["product_id", "name"]]
    return {"user_id": user_id, "recommendations": results.to_dict(orient="records")}


@app.get("/search")
def search(q: str, top_n: int = Query(5, ge=1, le=20)):
    """Return products matching a free-text search query, ranked by relevance."""
    results = smart_search.search(q, top_n=top_n)
    return {"query": q, "results": results}
