import json
import os
import tempfile

import pytest

pytest.importorskip("sqlalchemy")

from data_loader import load_products_from_json  # noqa: E402


def load(payload):
    """Write `payload` to a temp products.json and load it."""
    with tempfile.TemporaryDirectory() as d:
        path = os.path.join(d, "products.json")
        with open(path, "w", encoding="utf-8") as f:
            json.dump(payload, f)
        return load_products_from_json(path)


def test_frontend_format_renames_id_and_builds_description_from_category():
    df = load([{"id": 0, "name": "Redmi 13C", "price": 280, "categories": "mobiles"}])
    assert list(df.columns[:3]) == ["product_id", "name", "description"]
    assert df["product_id"].tolist() == [0]
    assert "phone" in df["description"].iloc[0]


def test_existing_description_is_kept():
    df = load([{"id": 1, "name": "Lamp", "description": "LED desk lamp", "categories": "appliances"}])
    assert df["description"].iloc[0] == "LED desk lamp"


def test_unknown_category_uses_category_name_only():
    df = load([{"id": 1, "name": "Thing", "categories": "garden"}])
    assert df["description"].iloc[0] == "garden"


def test_no_category_and_no_description_gives_empty_description():
    df = load([{"id": 1, "name": "Thing"}])
    assert df["description"].iloc[0] == ""


def test_dict_with_products_key_is_supported():
    df = load({"products": [{"id": 1, "name": "Thing", "categories": "mobiles"}]})
    assert df["name"].tolist() == ["Thing"]
