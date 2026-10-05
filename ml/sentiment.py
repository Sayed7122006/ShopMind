"""
sentiment.py
-------------
Model 3: Review Sentiment Analysis

Approach: run every customer review through a pretrained transformer
sentiment classifier (DistilBERT fine-tuned on SST-2), then aggregate the
per-review results into a per-product summary: how many reviews are
positive / negative / neutral, an overall verdict, and the most
representative positive and negative reviews (an extractive summary).

Like SmartSearch, all reviews are analysed once at startup, so the API
answers instantly afterwards.

The classifier is injectable (`classifier=` argument): any callable that
takes a list of texts and returns a list of {"label", "score"} dicts works.
That keeps the class easy to unit-test without downloading a model.
"""

import pandas as pd

DEFAULT_MODEL = "distilbert-base-uncased-finetuned-sst-2-english"


def _load_default_classifier(model_name: str = DEFAULT_MODEL):
    """Build the real transformers classifier, or a lightweight fallback if not installed."""
    try:
        from transformers import pipeline  # imported lazily: heavy import
        pipe = pipeline("sentiment-analysis", model=model_name)

        def classify(texts: list) -> list:
            return pipe(texts, truncation=True, max_length=512, batch_size=16)

        return classify
    except Exception:
        # Lightweight fallback when transformers is not installed
        POSITIVE_WORDS = {
            "good", "great", "excellent", "love", "amazing", "perfect", "best", "nice",
            "fast", "clear", "smooth", "happy", "recommend", "awesome", "solid", "durable",
            "worth", "satisfied", "fantastic", "quality", "ممتاز", "جيد", "رائع", "جميل", "حلو"
        }
        NEGATIVE_WORDS = {
            "bad", "terrible", "worst", "poor", "broken", "slow", "horrible", "awful",
            "hate", "disappointed", "waste", "cheap", "faulty", "failed", "freeze",
            "سيء", "رديء", "بطيء", "خربان", "مكسور", "غير راض"
        }

        def fallback_classify(texts: list) -> list:
            results = []
            for text in texts:
                words = set(str(text).lower().split())
                pos_count = len(words.intersection(POSITIVE_WORDS))
                neg_count = len(words.intersection(NEGATIVE_WORDS))
                if pos_count >= neg_count:
                    score = min(0.95, 0.65 + pos_count * 0.1)
                    results.append({"label": "POSITIVE", "score": score})
                else:
                    score = min(0.95, 0.65 + neg_count * 0.1)
                    results.append({"label": "NEGATIVE", "score": score})
            return results

        return fallback_classify


class ReviewSentiment:
    def __init__(self, reviews: pd.DataFrame, classifier=None,
                 neutral_threshold: float = 0.6):
        """
        reviews: cleaned reviews with columns [product_id, review_text]
                 and optionally [rating].
        classifier: optional callable(list[str]) -> list[{"label","score"}].
        neutral_threshold: predictions with confidence below this are
                 treated as "neutral" (the base model is binary, so a
                 low-confidence call usually means a mixed review).
        """
        self.neutral_threshold = neutral_threshold
        self.classifier = classifier or _load_default_classifier()

        self.reviews = reviews.reset_index(drop=True).copy()
        if "product_id" in self.reviews.columns:
            self.reviews["product_id"] = self.reviews["product_id"].astype(str)
        self._analyze_all()

    # ------------------------------------------------------------------
    def _to_result(self, raw: dict) -> dict:
        """Convert a raw classifier output to our label/score format."""
        confidence = float(raw["score"])
        is_positive = str(raw["label"]).upper().startswith("POS")

        if confidence < self.neutral_threshold:
            return {"label": "neutral", "confidence": confidence, "score": 0.0}
        return {
            "label": "positive" if is_positive else "negative",
            "confidence": confidence,
            # signed score in [-1, 1]: handy for averaging
            "score": confidence if is_positive else -confidence,
        }

    def _analyze_all(self):
        """Classify every review once and store the results as columns."""
        texts = self.reviews["review_text"].astype(str).tolist() if len(self.reviews) else []
        raw_results = self.classifier(texts) if texts else []
        results = [self._to_result(r) for r in raw_results]

        self.reviews["sentiment_label"] = [r["label"] for r in results]
        self.reviews["sentiment_score"] = [r["score"] for r in results]
        self.reviews["confidence"] = [r["confidence"] for r in results]

    # ------------------------------------------------------------------
    def analyze_text(self, text: str) -> dict:
        """Classify a single piece of text (used by the /sentiment endpoint)."""
        if not isinstance(text, str) or not text.strip():
            raise ValueError("text must be a non-empty string")
        return self._to_result(self.classifier([text.strip()])[0])

    def summarize_product(self, product_id: str):
        """
        Summarize all reviews of one product.
        Returns None if the product has no reviews.
        """
        subset = self.reviews[self.reviews["product_id"] == str(product_id)]
        if subset.empty:
            return None

        n = len(subset)
        counts = subset["sentiment_label"].value_counts()
        positive = int(counts.get("positive", 0))
        negative = int(counts.get("negative", 0))
        neutral = int(counts.get("neutral", 0))

        avg_sentiment = float(subset["sentiment_score"].mean())
        if avg_sentiment >= 0.25:
            overall = "positive"
        elif avg_sentiment <= -0.25:
            overall = "negative"
        else:
            overall = "mixed"

        avg_rating = None
        if "rating" in subset.columns and subset["rating"].notna().any():
            avg_rating = round(float(subset["rating"].mean()), 2)

        positives = subset[subset["sentiment_label"] == "positive"]
        negatives = subset[subset["sentiment_label"] == "negative"]
        top_positive = (
            positives.loc[positives["sentiment_score"].idxmax(), "review_text"]
            if not positives.empty else None
        )
        top_negative = (
            negatives.loc[negatives["sentiment_score"].idxmin(), "review_text"]
            if not negatives.empty else None
        )

        positive_pct = round(100 * positive / n, 1)
        negative_pct = round(100 * negative / n, 1)
        summary = (
            f"{n} review{'s' if n != 1 else ''}: {positive_pct}% positive, "
            f"{negative_pct}% negative. Overall sentiment: {overall}."
        )
        if avg_rating is not None:
            summary += f" Average rating: {avg_rating}/5."

        return {
            "product_id": str(product_id),
            "review_count": n,
            "positive": positive,
            "negative": negative,
            "neutral": neutral,
            "positive_pct": positive_pct,
            "negative_pct": negative_pct,
            "avg_sentiment": round(avg_sentiment, 3),
            "overall_sentiment": overall,
            "avg_rating": avg_rating,
            "top_positive_review": top_positive,
            "top_negative_review": top_negative,
            "summary": summary,
        }

    def summarize_all(self) -> list:
        """
        Summaries for every product that has reviews, most-liked first.
        Handy for showing a sentiment badge next to every product.
        """
        summaries = [
            self.summarize_product(pid) for pid in self.reviews["product_id"].unique()
        ]
        return sorted(summaries, key=lambda s: s["avg_sentiment"], reverse=True)
