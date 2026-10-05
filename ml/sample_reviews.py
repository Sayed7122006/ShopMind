"""
sample_reviews.py
------------------
Generates reviews.csv: small fake product reviews tied to the product ids of
the real products.json (if it is in this folder) or else to the sample
products p1..p10 from sample_data.py, so Review Sentiment Analysis can be tested before
real review data exists. Run once, after sample_data.py:

    python sample_reviews.py

Each product gets two positive reviews, one negative and one mixed.
"""

import json
import os
import random
from datetime import datetime, timedelta

import pandas as pd

random.seed(42)

# product_id -> [(review_text, rating), ...]
REVIEWS = {
    "p1": [
        ("Amazing sound quality and the noise cancellation is fantastic. Love them!", 5),
        ("Very comfortable, I wear them all day. Great battery life too.", 5),
        ("Terrible. The left ear stopped working after two weeks.", 1),
        ("Sound is good but the ear cups get hot after an hour.", 3),
    ],
    "p2": [
        ("Super light and comfortable, perfect for my morning runs. Highly recommend!", 5),
        ("Great grip and very breathable. Best running shoes I've owned.", 5),
        ("The sole started coming apart after a month. Very disappointed.", 2),
        ("Nice design, but they run a half size small.", 3),
    ],
    "p3": [
        ("Wakes me up with fresh coffee every morning. Works perfectly and looks great.", 5),
        ("Easy to use and easy to clean. Excellent value for the price.", 4),
        ("It leaks water everywhere. Awful product, a complete waste of money.", 1),
        ("Makes decent coffee, but the timer is confusing to set.", 3),
    ],
    "p4": [
        ("Thick, cushioned and doesn't slip at all. I love this mat!", 5),
        ("Really comfortable for long yoga sessions. Great quality.", 5),
        ("Smells terrible and the surface started peeling quickly.", 1),
        ("Good thickness, though it's a bit heavy to carry around.", 3),
    ],
    "p5": [
        ("Fits my laptop perfectly and has so many useful pockets. Fantastic bag!", 5),
        ("Kept everything dry in heavy rain. Very sturdy and stylish.", 5),
        ("The zipper broke within a week. Poor quality, do not buy.", 1),
        ("Looks nice and holds a lot, but the straps could be more padded.", 3),
    ],
    "p6": [
        ("Accurate heart rate tracking and the battery lasts for days. Love it!", 5),
        ("Great fitness features and a beautiful screen. Worth every penny.", 5),
        ("The screen cracked easily and the app keeps crashing. Terrible experience.", 1),
        ("Tracks workouts well, but notifications are slow to arrive.", 3),
    ],
    "p7": [
        ("Bright, adjustable and perfect for my desk. Really happy with it.", 5),
        ("Elegant design and the light is easy on the eyes. Excellent lamp.", 5),
        ("It flickers constantly and stopped working after a few days. Awful.", 1),
        ("Good light, but the base is a little unstable.", 3),
    ],
    "p8": [
        ("Keeps my water ice cold all day long. Fantastic bottle, I love it!", 5),
        ("Sturdy, leak-proof and the perfect size. Great purchase.", 5),
        ("Leaks from the lid and the paint chipped quickly. Very disappointing.", 2),
        ("Keeps drinks cold, but it's quite heavy when full.", 3),
    ],
    "p9": [
        ("Loud, clear sound and survived a full day at the beach. Awesome speaker!", 5),
        ("Pairs instantly and the battery life is superb. Highly recommended.", 5),
        ("Distorted sound at high volume and it disconnects constantly. Terrible.", 1),
        ("Decent sound for the price, though the bass is weak.", 3),
    ],
    "p10": [
        ("My back pain is gone since I got this chair. Wonderful support!", 5),
        ("Comfortable, easy to assemble and looks professional. Excellent value.", 5),
        ("The armrest broke after a month and customer service was useless. Horrible.", 1),
        ("Comfortable overall, but the assembly instructions were confusing.", 3),
    ],
}


# Used when the real products.json is present: generic reviews per category,
# in the order [positive, positive, negative, mixed, negative]. Each product
# gets a different mix (see PROFILES) so the demo shows varied verdicts.
CATEGORY_REVIEWS = {
    "electronics": [
        ("Excellent quality and works perfectly. Highly recommend it!", 5),
        ("Great performance for the price, very happy with this purchase.", 5),
        ("Terrible. It stopped working after two weeks and support was useless.", 1),
        ("Good overall, but setup was confusing and the manual is poor.", 3),
        ("Awful. It arrived damaged and the screen flickers. Do not buy.", 1),
    ],
    "mobiles": [
        ("Fantastic phone, the battery lasts all day and the screen is great. Love it!", 5),
        ("Fast, smooth and the camera is surprisingly good for the price.", 4),
        ("Awful experience. The phone freezes constantly and the battery drains fast.", 1),
        ("Decent phone, but the camera is average and it heats up sometimes.", 3),
        ("Horrible. The screen cracked easily and the phone keeps restarting.", 1),
    ],
    "appliances": [
        ("Works perfectly and makes my kitchen work so much easier. Great buy!", 5),
        ("Sturdy, easy to clean and very efficient. Excellent value.", 5),
        ("Broke after a month and it is very noisy. Terrible quality.", 1),
        ("Does the job, but it is bulky and the instructions are unclear.", 3),
        ("Awful. It leaks, smells burnt and stopped working. Waste of money.", 1),
    ],
}
DEFAULT_REVIEWS = CATEGORY_REVIEWS["electronics"]

# Which templates a product gets, chosen by product id % 4:
# balanced, very positive, mostly negative, negative-only.
PROFILES = [[0, 1, 2, 3], [0, 1, 3], [2, 4, 3, 0], [2, 4]]


def reviews_for_real_products():
    """Build {product_id: [(text, rating), ...]} from products.json, or None."""
    path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "products.json")
    if not os.path.exists(path):
        return None
    with open(path, "r", encoding="utf-8") as f:
        raw = json.load(f)
    items = raw.get("products", raw) if isinstance(raw, dict) else raw
    result = {}
    for item in items:
        category = str(item.get("categories", item.get("category", ""))).lower()
        templates = CATEGORY_REVIEWS.get(category, DEFAULT_REVIEWS)
        profile = PROFILES[int(item["id"]) % len(PROFILES)]
        result[str(item["id"])] = [templates[i] for i in profile]
    return result


def generate_reviews_csv(path: str = "reviews.csv"):
    reviews = reviews_for_real_products() or REVIEWS
    rows = []
    start = datetime(2026, 1, 1)
    review_id = 1
    for product_id, items in reviews.items():
        for text, rating in items:
            rows.append((
                f"r{review_id}",
                product_id,
                f"u{random.randint(1, 20)}",
                text,
                rating,
                (start + timedelta(hours=random.randint(0, 24 * 60))).isoformat(),
            ))
            review_id += 1

    df = pd.DataFrame(
        rows,
        columns=["review_id", "product_id", "user_id", "review_text", "rating", "timestamp"],
    )
    df.to_csv(path, index=False)
    print(f"Wrote {len(df)} reviews to {path}")


if __name__ == "__main__":
    generate_reviews_csv()
