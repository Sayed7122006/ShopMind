import pandas as pd

from recommender import Recommender


def make_recommender():
    #        p1 p2 p3 p4
    data = {"p1": [5, 5, 0, 1],
            "p2": [3, 3, 0, 0],
            "p3": [0, 5, 5, 0],
            "p4": [0, 0, 1, 0]}
    matrix = pd.DataFrame(data, index=["u1", "u2", "u3", "u4"])
    return Recommender(matrix)


def test_recommend_never_returns_already_seen_products():
    recs = make_recommender().recommend_for_user("u1", top_n=5)
    assert "p1" not in recs and "p2" not in recs


def test_recommend_ranks_co_purchased_product_first():
    # u1 has p1+p2; u2 bought p1+p2+p3, so p3 is the natural suggestion (p4 shares nobody).
    recs = make_recommender().recommend_for_user("u1", top_n=5)
    assert recs[0] == "p3"
    assert recs == ["p3", "p4"]


def test_recommend_respects_top_n():
    assert len(make_recommender().recommend_for_user("u1", top_n=1)) == 1


def test_recommend_unknown_user_cold_start_returns_empty_list():
    assert make_recommender().recommend_for_user("nobody") == []


def test_recommend_user_who_saw_everything_gets_empty_list():
    matrix = pd.DataFrame({"p1": [1, 1], "p2": [1, 1]}, index=["u1", "u2"])
    assert Recommender(matrix).recommend_for_user("u1") == []


def test_similar_products_excludes_itself_and_ranks_best_first():
    sims = make_recommender().similar_products("p1", top_n=3)
    assert "p1" not in sims
    assert sims[0] == "p2"   # p1 and p2 are bought by exactly the same users


def test_similar_products_unknown_product_returns_empty_list():
    assert make_recommender().similar_products("nope") == []


def test_similarity_matrix_is_symmetric_with_unit_diagonal():
    sim = make_recommender().item_similarity
    assert (sim.values == sim.values.T).all() or abs(sim.values - sim.values.T).max() < 1e-9
    assert all(abs(sim.loc[p, p] - 1.0) < 1e-9 for p in sim.index)


def test_product_nobody_touched_does_not_crash():
    matrix = pd.DataFrame({"p1": [1, 2], "p2": [0, 0]}, index=["u1", "u2"])
    r = Recommender(matrix)
    assert r.similar_products("p1") == ["p2"]
