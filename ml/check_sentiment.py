"""
check_sentiment.py
-------------------
Quick end-to-end check of the REAL sentiment model (not a fake one).
Run it on your machine -- the first run downloads the model (~250MB):

    python check_sentiment.py

It (1) classifies a few obviously positive/negative sentences and reports
PASS/FAIL, then (2) prints the verdict for every product in reviews.csv.
Exit code is 1 if any sentence is classified wrongly.
"""

import os
import sys

import pandas as pd

from data_cleaning import clean_products, clean_reviews
from data_loader import load_products_from_csv, load_products_from_json, load_reviews_from_csv
from sentiment import ReviewSentiment

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

SAMPLES = [
    ("I absolutely love this phone, the battery is amazing!", "positive"),
    ("Excellent quality, works perfectly and great value.", "positive"),
    ("Terrible quality, it broke after two days.", "negative"),
    ("Worst purchase ever, a complete waste of money.", "negative"),
]


def main() -> int:
    print("Loading the sentiment model (first run downloads it)...")
    reviews = clean_reviews(load_reviews_from_csv(os.path.join(BASE_DIR, "reviews.csv")))
    model = ReviewSentiment(reviews)

    print("\n--- 1) Sanity check on known sentences ---")
    failures = 0
    for text, expected in SAMPLES:
        result = model.analyze_text(text)
        ok = result["label"] == expected
        failures += not ok
        print(f"[{'PASS' if ok else 'FAIL'}] expected={expected:8} got={result['label']:8} "
              f"({result['confidence']:.2f})  {text}")

    print("\n--- 2) Verdict per product (from reviews.csv) ---")
    json_path = os.path.join(BASE_DIR, "products.json")
    products = clean_products(
        load_products_from_json(json_path) if os.path.exists(json_path)
        else load_products_from_csv(os.path.join(BASE_DIR, "products.csv"))
    )
    names = dict(zip(products["product_id"], products["name"]))
    for s in model.summarize_all():
        print(f"{s['overall_sentiment']:9} {s['positive_pct']:5.1f}% pos / {s['negative_pct']:5.1f}% neg"
              f"  | {names.get(s['product_id'], s['product_id'])}")

    print("\nRESULT:", "ALL GOOD" if failures == 0 else f"{failures} sentence(s) misclassified")
    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())
