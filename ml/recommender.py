"""
recommender.py
----------------
Model 1: Recommendation System

Approach: item-based collaborative filtering.
  - We build a user x product matrix of interaction strength (see data_cleaning.py).
  - We compute similarity between products based on which users interacted
    with them similarly.
  - To recommend for a user, we look at products they already liked and
    suggest the most similar products they haven't interacted with yet.

This is a simple, fast, and explainable approach -- a good fit for a
project-timeline deliverable.
"""

import pandas as pd
from sklearn.metrics.pairwise import cosine_similarity


class Recommender:
    def __init__(self, user_item_matrix: pd.DataFrame):
        """
        user_item_matrix: rows = user_id, columns = product_id,
        values = interaction strength (see build_user_item_matrix).
        """
        self.matrix = user_item_matrix
        self.item_similarity = None
        self._fit()

    def _fit(self):
        """Compute product-to-product similarity from the user-item matrix."""
        # Transpose so rows become products, columns become users
        item_vectors = self.matrix.T
        similarity = cosine_similarity(item_vectors)
        self.item_similarity = pd.DataFrame(
            similarity, index=item_vectors.index, columns=item_vectors.index
        )

    def recommend_for_user(self, user_id: str, top_n: int = 5) -> list:
        """
        Return up to `top_n` recommended product_ids for a given user.
        Falls back to an empty list if the user is unknown (cold start).
        """
        if user_id not in self.matrix.index:
            return []

        user_row = self.matrix.loc[user_id]
        already_seen = set(user_row[user_row > 0].index)

        # Score every candidate product by summing similarity to products
        # the user already engaged with, weighted by how strongly they did.
        scores = pd.Series(0.0, index=self.item_similarity.index)
        for product_id, weight in user_row[user_row > 0].items():
            scores = scores.add(self.item_similarity[product_id] * weight, fill_value=0)

        scores = scores.drop(labels=already_seen, errors="ignore")
        scores = scores.sort_values(ascending=False)

        return scores.head(top_n).index.tolist()

    def similar_products(self, product_id: str, top_n: int = 5) -> list:
        """Return the `top_n` products most similar to a given product."""
        if product_id not in self.item_similarity.index:
            return []
        scores = self.item_similarity[product_id].drop(labels=[product_id])
        return scores.sort_values(ascending=False).head(top_n).index.tolist()
