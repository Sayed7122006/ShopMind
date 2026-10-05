"""
search.py
----------
Model 2: Smart Search (semantic, multilingual & typo-tolerant)

Understands user intent across Arabic and English, typos (e.g. "موبل" -> "موبايل" / "mobile"),
category names, and brand keywords using character-level TF-IDF n-grams and synonym expansion.
Works out of the box with scikit-learn, and automatically uses sentence-transformers if installed.
"""

import re
import numpy as np
import pandas as pd

try:
    from sentence_transformers import SentenceTransformer, util
    _HAS_SENTENCE_TRANSFORMERS = True
except ImportError:
    _HAS_SENTENCE_TRANSFORMERS = False

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

SYNONYMS = {
    'mobiles': [
        'موبايل', 'موبل', 'موبيل', 'تلفون', 'تليفون', 'هاتف', 'جوال', 'محمول', 'فون', 'سمارت فون',
        'mobile', 'phone', 'smartphone', 'cellular', 'redmi', 'oppo', 'samsung', 'xiaomi', 'realme', 'infinix', 'honor'
    ],
    'electronics': [
        'الكترونيات', 'الكترونك', 'اجهزة الكترونية', 'electronics', 'electronic', 'tech', 'gadgets'
    ],
    'appliances': [
        'اجهزة منزلية', 'ادوات منزلية', 'منزلية', 'مطبخ', 'خلاط', 'مكنسة', 'مكواة', 'قلاية', 'كاتل', 'غلاية',
        'appliances', 'appliance', 'home', 'kitchen', 'air fryer', 'blender', 'iron', 'vacuum', 'kettle', 'cooker'
    ],
    'laptop': [
        'لاب', 'لابتوب', 'لاب توب', 'حاسوب', 'كمبيوتر', 'لابتوبات', 'حواسب',
        'laptop', 'notebook', 'pc', 'computer', 'gaming', 'dell', 'hp', 'lenovo', 'asus'
    ],
    'tv': [
        'تلفزيون', 'تلفاز', 'شاشة', 'شاشه', 'شاشات', 'رسيفر', 'ريسيفر',
        'tv', 'television', 'screen', 'monitor', 'display', 'receiver', 'curved', '4k', 'uhd', 'oled'
    ],
    'camera': [
        'كاميرا', 'كاميره', 'كاميرات', 'تصوير', 'فوتو',
        'camera', 'canon', 'nikon', 'sony', 'dslr', 'mirrorless', 'lens'
    ],
    'watch': [
        'ساعة', 'ساعه', 'ساعات', 'سمارت',
        'watch', 'smartwatch', 'band', 'fitness'
    ],
    'audio': [
        'سماعة', 'سماعه', 'سماعات', 'ايربودز', 'هيدفون', 'صوت',
        'headphone', 'earphone', 'airpods', 'headset', 'audio', 'sound', 'speaker', 'bluetooth'
    ]
}

def normalize_text(text: str) -> str:
    """Normalize Arabic and English text for robust matching."""
    if not isinstance(text, str):
        return ""
    text = text.lower()
    text = re.sub(r'[أإآ]', 'ا', text)
    text = re.sub(r'ة', 'ه', text)
    text = re.sub(r'ى', 'ي', text)
    text = re.sub(r'[\r\n\t]+', ' ', text)
    text = re.sub(r'[^\w\s]', ' ', text)
    return re.sub(r'\s+', ' ', text).strip()


class SmartSearch:
    def __init__(self, products: pd.DataFrame, model_name: str = "all-MiniLM-L6-v2"):
        """
        products: cleaned product catalog with columns [product_id, name, description]
        """
        self.products = products.reset_index(drop=True)
        self.use_transformer = False
        if _HAS_SENTENCE_TRANSFORMERS:
            try:
                self.model = SentenceTransformer(model_name)
                self.use_transformer = True
            except Exception:
                self.use_transformer = False
        
        self._build_index()

    def _build_search_text(self, row: pd.Series) -> str:
        name = str(row.get('name', ''))
        cat = str(row.get('category', row.get('categories', '')))
        desc = str(row.get('description', ''))
        
        extra = []
        cat_lower = cat.lower()
        if cat_lower in SYNONYMS:
            extra.extend(SYNONYMS[cat_lower])
        
        combined_lower = f"{name} {desc}".lower()
        for key, syns in SYNONYMS.items():
            if key in combined_lower or any(s in combined_lower for s in syns if s.isascii()):
                extra.extend(syns)
        
        syn_str = ' '.join(extra)
        full_text = f"{name} {cat} {desc} {syn_str}"
        return normalize_text(full_text)

    def _build_index(self):
        """Pre-compute search index."""
        texts = [self._build_search_text(row) for _, row in self.products.iterrows()]
        if self.use_transformer:
            self.embeddings = self.model.encode(texts, convert_to_tensor=True)
        else:
            self.vectorizer = TfidfVectorizer(
                analyzer='char_wb',
                ngram_range=(2, 4),
                sublinear_tf=True
            )
            self.matrix = self.vectorizer.fit_transform(texts)

    def search(self, query: str, top_n: int = 12) -> list:
        """
        Return the top_n most relevant products for a free-text query.
        Understands Arabic/English keywords, typos ("موبل"), categories, and brand names.
        """
        norm_q = normalize_text(query)
        if not norm_q:
            return []

        if self.use_transformer:
            query_embedding = self.model.encode(norm_q, convert_to_tensor=True)
            scores = util.cos_sim(query_embedding, self.embeddings)[0]
            top_results = scores.topk(k=min(top_n, len(self.products)))
            results = []
            for score, idx in zip(top_results.values, top_results.indices):
                row = self.products.iloc[int(idx)]
                results.append({
                    "product_id": str(row["product_id"]),
                    "name": str(row["name"]),
                    "score": float(score)
                })
            return results
        else:
            query_vec = self.vectorizer.transform([norm_q])
            sim = cosine_similarity(query_vec, self.matrix)[0]
            top_indices = np.argsort(sim)[::-1]
            
            results = []
            for idx in top_indices:
                score = float(sim[idx])
                if score > 0.05:  # meaningful match threshold
                    row = self.products.iloc[int(idx)]
                    results.append({
                        "product_id": str(row["product_id"]),
                        "name": str(row["name"]),
                        "score": round(score, 3)
                    })
                    if len(results) >= top_n:
                        break

            return results
