"""Fakes so the tests run offline, without downloading any model."""
import re
import zlib


def fake_classifier(texts):
    """Keyword-based stand-in for the transformers sentiment pipeline."""
    out = []
    for t in texts:
        t = t.lower()
        if "love" in t:
            out.append({"label": "POSITIVE", "score": 0.99})
        elif "great" in t:
            out.append({"label": "POSITIVE", "score": 0.95})
        elif "terrible" in t or "broke" in t or "awful" in t:
            out.append({"label": "NEGATIVE", "score": 0.97})
        else:
            out.append({"label": "POSITIVE", "score": 0.52})  # low confidence
    return out


class FakeSentenceModel:
    """Bag-of-words 'embedding' with the same encode() interface as SentenceTransformer."""
    DIM = 512

    def __init__(self, *args, **kwargs):
        pass

    def _vec(self, text):
        import torch
        v = torch.zeros(self.DIM)
        for w in re.findall(r"[a-z]+", text.lower()):
            v[zlib.crc32(w.encode()) % self.DIM] += 1.0
        return v

    def encode(self, texts, convert_to_tensor=False):
        import torch
        if isinstance(texts, str):
            return self._vec(texts)
        return torch.stack([self._vec(t) for t in texts])
