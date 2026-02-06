import os
import re
import hashlib
from pathlib import Path
from dotenv import load_dotenv

import chromadb
from chromadb.utils import embedding_functions

load_dotenv()

BASE_DIR = Path(__file__).resolve().parent.parent
RAG_DIR = BASE_DIR / "rag_data"
PERSIST_DIR = BASE_DIR / "chroma_db"

COLLECTION_NAME = os.getenv("CHROMA_COLLECTION", "ielts_writing_rubric")
EMBED_MODEL = os.getenv("EMBED_MODEL", "text-embedding-3-small")
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY")

if not OPENAI_API_KEY:
    raise RuntimeError("Missing OPENAI_API_KEY in .env")

def chunk_text(text: str, max_chars: int = 1400, overlap: int = 200):
    text = text.replace("\r\n", "\n")
    text = re.sub(r"\n{3,}", "\n\n", text).strip()

    chunks = []
    i = 0
    step = max_chars - overlap
    while i < len(text):
        chunk = text[i:i + max_chars].strip()
        if chunk:
            chunks.append(chunk)
        i += step
    return chunks

def parse_band(stem: str):
    m = re.search(r"band[_-]?(\d(\.5)?)", stem.lower())
    return float(m.group(1)) if m else None

def parse_criterion(stem: str):
    s = stem.lower()
    if s.startswith("tr_") or s.startswith("tr-") or s == "tr":
        return "TR"
    if s.startswith("cc_") or s.startswith("cc-") or s == "cc":
        return "CC"
    if s.startswith("lr_") or s.startswith("lr-") or s == "lr":
        return "LR"
    if s.startswith("gra_") or s.startswith("gra-") or s.startswith("ga_") or s == "gra":
        return "GRA"
    return None

def parse_task_type(path: Path):
    p = str(path).lower()
    if "/task_2/" in p or "\\task_2\\" in p:
        return "task_2"
    if "/general_task_1/" in p or "\\general_task_1\\" in p:
        return "general_task_1"
    if "/academic_task_1/" in p or "\\academic_task_1\\" in p:
        return "academic_task_1"
    if "/common/" in p or "\\common\\" in p:
        return "common"
    return "unknown"

def stable_id(file_path: Path, chunk_index: int, chunk: str):
    h = hashlib.sha1()
    h.update(str(file_path).encode("utf-8"))
    h.update(str(chunk_index).encode("utf-8"))
    h.update(chunk.encode("utf-8"))
    return h.hexdigest()

def main():
    if not RAG_DIR.exists():
        raise RuntimeError(f"Missing rag_data folder: {RAG_DIR}")

    PERSIST_DIR.mkdir(parents=True, exist_ok=True)

    chroma_client = chromadb.PersistentClient(path=str(PERSIST_DIR))

    embedder = embedding_functions.OpenAIEmbeddingFunction(
        api_key=OPENAI_API_KEY,
        model_name=EMBED_MODEL
    )

    existing = [c.name for c in chroma_client.list_collections()]
    if COLLECTION_NAME in existing:
        chroma_client.delete_collection(COLLECTION_NAME)

    collection = chroma_client.get_or_create_collection(
        name=COLLECTION_NAME,
        embedding_function=embedder
    )

    files = list(RAG_DIR.rglob("*.md")) + list(RAG_DIR.rglob("*.txt"))
    files = [f for f in files if f.is_file()]

    if not files:
        print(f"No .md/.txt files found in {RAG_DIR}")
        return

    docs, metas, ids = [], [], []
    ingested_files = 0

    for path in files:
        raw = path.read_text(encoding="utf-8", errors="ignore").strip()
        if not raw:
            continue

        task_type = parse_task_type(path)
        criterion = parse_criterion(path.stem)
        band = parse_band(path.stem)

        chunks = chunk_text(raw)
        for idx, chunk in enumerate(chunks):
            meta = {
                "task_type": task_type,
                "source_file": str(path.relative_to(BASE_DIR)),
                "chunk_index": idx,
            }
            if criterion:
                meta["criterion"] = criterion
            if band is not None:
                meta["band"] = band

            docs.append(chunk)
            metas.append(meta)
            ids.append(stable_id(path, idx, chunk))

        ingested_files += 1

    if not docs:
        print("No content ingested (all files empty?)")
        return

    collection.add(documents=docs, metadatas=metas, ids=ids)

    print(f"Collection: {COLLECTION_NAME}")
    print(f"Files ingested: {ingested_files}")
    print(f"Chunks stored: {len(docs)}")
    print(f"DB location: {PERSIST_DIR}")
    print(f"Collection count: {collection.count()}")

if __name__ == "__main__":
    main()
