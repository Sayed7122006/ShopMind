import pathlib
import sys

# Make the ml/ folder importable (api.py, recommender.py, ...) from tests/.
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent.parent))
