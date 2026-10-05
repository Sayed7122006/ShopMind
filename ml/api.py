"""
api.py
-------
Wraps the three ML models (Recommender, SmartSearch, ReviewSentiment) in a FastAPI app so the
Frontend can call them like any other API.
 
Run locally with:
    uvicorn api:app --reload --port 8001
 
Endpoints:
    GET /recommend/{user_id}?top_n=5
    GET /search?q=running+shoes&top_n=5
    GET /sentiment?text=I+love+this+product
    GET /products/{product_id}/review-summary
    GET /review-summaries
"""
 
from fastapi import FastAPI, HTTPException, Query, Request
from fastapi.responses import HTMLResponse, JSONResponse
from fastapi.middleware.cors import CORSMiddleware
import pandas as pd
 
import os
 
from data_loader import (
    load_interactions_from_csv,
    load_products_from_csv,
    load_products_from_json,
    load_reviews_from_csv,
)
from data_cleaning import clean_interactions, clean_products, clean_reviews, build_user_item_matrix
from recommender import Recommender
from search import SmartSearch
from sentiment import ReviewSentiment
 
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
# Paths are resolved relative to this file (not the current working directory),
# so the API works no matter where uvicorn is launched from.
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
PRODUCTS_JSON_PATH = os.path.join(BASE_DIR, "products.json")
REVIEWS_CSV_PATH = os.path.join(BASE_DIR, "reviews.csv")

raw_interactions = load_interactions_from_csv(os.path.join(BASE_DIR, "interactions.csv"))
if os.path.exists(PRODUCTS_JSON_PATH):
    raw_products = load_products_from_json(PRODUCTS_JSON_PATH)
else:
    raw_products = load_products_from_csv(os.path.join(BASE_DIR, "products.csv"))

# Reviews: no real review data exists yet, so use the generated reviews.csv
# (python sample_reviews.py). If the file is missing, start with no reviews
# instead of crashing -- the other endpoints keep working.
if os.path.exists(REVIEWS_CSV_PATH):
    raw_reviews = load_reviews_from_csv(REVIEWS_CSV_PATH)
else:
    raw_reviews = pd.DataFrame(columns=["product_id", "review_text", "rating"])

interactions = clean_interactions(raw_interactions)
products = clean_products(raw_products)
reviews = clean_reviews(raw_reviews)

user_item_matrix = build_user_item_matrix(interactions)
recommender = Recommender(user_item_matrix)
smart_search = SmartSearch(products)
review_sentiment = ReviewSentiment(reviews)

PRODUCT_NAMES = dict(zip(products["product_id"], products["name"]))
 
 
@app.get("/", response_class=HTMLResponse)
def root(request: Request):
    accept = request.headers.get("accept", "")
    if "application/json" in accept and "text/html" not in accept:
        return JSONResponse({"status": "ok", "message": "E-commerce ML API is running"})

    html_content = """<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopMind AI Engine</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 35px;
            max-width: 620px;
            width: 100%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
        .badge {
            display: inline-block;
            background: #10b981;
            color: #fff;
            font-weight: bold;
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        h1 {
            margin: 0 0 12px 0;
            font-size: 26px;
            color: #38bdf8;
        }
        p {
            color: #94a3b8;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .status-box {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: right;
            font-size: 14px;
            color: #cbd5e1;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #1e293b;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .ok-text {
            color: #10b981;
            font-weight: bold;
        }
        .links {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .btn {
            display: block;
            padding: 13px 20px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            font-size: 15px;
            transition: all 0.2s;
        }
        .btn-store {
            background: #2563eb;
            color: #fff;
        }
        .btn-store:hover {
            background: #1d4ed8;
        }
        .btn-admin {
            background: #475569;
            color: #fff;
        }
        .btn-admin:hover {
            background: #334155;
        }
        .btn-docs {
            background: #0284c7;
            color: #fff;
        }
        .btn-docs:hover {
            background: #0369a1;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">● محرك الذكاء الاصطناعي متصل ويعمل (Port 8001)</div>
        <h1>ShopMind AI & Machine Learning Service</h1>
        <p>هذا هو المحرك البرمجي الخلفي (AI Engine) الخاص بمتجر <strong>ShopMind</strong> لتشغيل ميزات الذكاء الاصطناعي تلقائياً داخل المتجر.</p>
        
        <div class="status-box">
            <div class="status-item">
                <span>البحث الذكي الدلالي (Smart Semantic Search):</span>
                <span class="ok-text">نشط ومفعل ✓</span>
            </div>
            <div class="status-item">
                <span>نظام التوصيات (Product Recommender):</span>
                <span class="ok-text">نشط ومفعل ✓</span>
            </div>
            <div class="status-item">
                <span>تحليل آراء وتقييمات العملاء (Sentiment Analysis):</span>
                <span class="ok-text">نشط ومفعل ✓</span>
            </div>
        </div>

        <div class="links">
            <a class="btn btn-store" href="http://localhost/ShopMind/frontend/index.php">🛒 الدخول إلى متجر ShopMind الرئيسي</a>
            <a class="btn btn-admin" href="http://localhost/ShopMind/backend/index.php">⚙️ لوحة إدارة المتجر (Admin Dashboard)</a>
            <a class="btn btn-docs" href="/docs">📖 تصفح واجهات الـ API التفاعلية (Swagger UI)</a>
        </div>
    </div>
</body>
</html>"""
    return HTMLResponse(content=html_content)
 
 
@app.get("/recommend/{user_id}")
def recommend(user_id: str, top_n: int = Query(5, ge=1, le=20)):
    """Return recommended product_ids for a given user."""
    product_ids = recommender.recommend_for_user(user_id, top_n=top_n)
    if not product_ids:
        return {"user_id": user_id, "recommendations": [], "note": "no data for this user yet"}
 
    # Keep the recommender's ranking (isin() on the DataFrame would return the
    # products in catalog order instead of best-first order).
    results = [
        {"product_id": pid, "name": PRODUCT_NAMES[pid]}
        for pid in product_ids
        if pid in PRODUCT_NAMES
    ]
    return {"user_id": user_id, "recommendations": results}
 
 
@app.get("/search")
def search(q: str = Query(..., min_length=1), top_n: int = Query(5, ge=1, le=20)):
    """Return products matching a free-text search query, ranked by relevance."""
    results = smart_search.search(q, top_n=top_n)
    return {"query": q, "results": results}


@app.get("/sentiment")
def sentiment(text: str = Query(..., min_length=1, max_length=2000)):
    """Classify the sentiment of a piece of text (positive / negative / neutral)."""
    try:
        result = review_sentiment.analyze_text(text)
    except ValueError as e:
        raise HTTPException(status_code=422, detail=str(e))
    return {"text": text, **result}


@app.get("/products/{product_id}/review-summary")
def review_summary(product_id: str):
    """Summarize the sentiment of all reviews of one product."""
    if product_id not in PRODUCT_NAMES:
        raise HTTPException(status_code=404, detail="unknown product")

    summary = review_sentiment.summarize_product(product_id)
    if summary is None:
        return {"product_id": product_id, "name": PRODUCT_NAMES[product_id],
                "review_count": 0, "note": "no reviews for this product yet"}
    return {"name": PRODUCT_NAMES[product_id], **summary}


@app.get("/review-summaries")
def review_summaries():
    """Sentiment verdict for every product that has reviews (most-liked first)."""
    results = []
    for s in review_sentiment.summarize_all():
        results.append({
            "product_id": s["product_id"],
            "name": PRODUCT_NAMES.get(s["product_id"]),
            "overall_sentiment": s["overall_sentiment"],
            "positive_pct": s["positive_pct"],
            "negative_pct": s["negative_pct"],
            "review_count": s["review_count"],
            "avg_rating": s["avg_rating"],
        })
    return {"count": len(results), "products": results}
