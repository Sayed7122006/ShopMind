"""
search.py
----------
Model 2: Smart Search (semantic search)

Approach: embed each product's name + description into a vector using a
sentence-transformer model (or TF-IDF fallback), rank products by cosine similarity.
This understands intent and meaning rather than exact keyword matches, and is
naturally tolerant of typos and rephrasing.
"""

import pandas as pd
import numpy as np

try:
    from sentence_transformers import SentenceTransformer, util
    _HAS_SENTENCE_TRANSFORMERS = True
except ImportError:
    _HAS_SENTENCE_TRANSFORMERS = False
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.metrics.pairwise import cosine_similarity


class SmartSearch:
    def __init__(self, products: pd.DataFrame, model_name: str = "all-MiniLM-L6-v2"):
        """
        products: cleaned product catalog with columns
                  [product_id, name, description]
        """
        self.products = products.reset_index(drop=True)
        if _HAS_SENTENCE_TRANSFORMERS:
            try:
                self.model = SentenceTransformer(model_name)
                self.mode = "sentence_transformer"
            except Exception:
                self.mode = "tfidf"
                from sklearn.feature_extraction.text import TfidfVectorizer
                from sklearn.metrics.pairwise import cosine_similarity
        else:
            self.mode = "tfidf"
            from sklearn.feature_extraction.text import TfidfVectorizer
            from sklearn.metrics.pairwise import cosine_similarity
        self._build_index()

    def _build_index(self):
        """Pre-compute embeddings/vectors for every product (name + description)."""
        texts = (self.products["name"].fillna("").astype(str) + ". " + self.products["description"].fillna("").astype(str)).tolist()
        if getattr(self, "mode", "tfidf") == "sentence_transformer":
            self.embeddings = self.model.encode(texts, convert_to_tensor=True)
        else:
            from sklearn.feature_extraction.text import TfidfVectorizer
            self.vectorizer = TfidfVectorizer(stop_words="english", ngram_range=(1, 2))
            self.tfidf_matrix = self.vectorizer.fit_transform(texts)

    def search(self, query: str, top_n: int = 5) -> list:
        """
        Return the top_n most relevant products for a free-text query.
        Each result includes product_id, name, and a similarity score.
        """
        if getattr(self, "mode", "tfidf") == "sentence_transformer":
            query_embedding = self.model.encode(query, convert_to_tensor=True)
            scores = util.cos_sim(query_embedding, self.embeddings)[0]
            top_results = scores.topk(k=min(top_n, len(self.products)))
            results = []
            for score, idx in zip(top_results.values, top_results.indices):
                row = self.products.iloc[int(idx)]
                results.append({
                    "product_id": row["product_id"],
                    "name": row["name"],
                    "score": float(score),
                })
            return results
        else:
            from sklearn.metrics.pairwise import cosine_similarity
            query_vec = self.vectorizer.transform([query])
            sim = cosine_similarity(query_vec, self.tfidf_matrix)[0]
            top_indices = np.argsort(sim)[::-1][:min(top_n, len(self.products))]
            results = []
            for idx in top_indices:
                if sim[idx] > 0:
                    row = self.products.iloc[int(idx)]
                    results.append({
                        "product_id": row["product_id"],
                        "name": row["name"],
                        "score": float(sim[idx]),
                    })
            return results
