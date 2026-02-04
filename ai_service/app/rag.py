import os
from dotenv import load_dotenv

load_dotenv()

def retrieve_rubric_context(task_type: str, k: int = 8) -> str:
    try:
        import chromadb
        from chromadb.utils import embedding_functions

        persist_dir = os.getenv("CHROMA_PERSIST_DIR", "./chroma_db")
        collection_name = os.getenv("CHROMA_COLLECTION", "ielts_writing_rubric")

        chroma_client = chromadb.Client(
            chromadb.config.Settings(persist_directory=persist_dir)
        )

        embedder = embedding_functions.OpenAIEmbeddingFunction(
            api_key=os.getenv("OPENAI_API_KEY"),
            model_name=os.getenv("EMBED_MODEL", "text-embedding-3-small"),
        )

        collection = chroma_client.get_collection(
            name=collection_name,
            embedding_function=embedder
        )

        query = f"IELTS {task_type} writing band descriptors rubric TR CC LR GRA"
        res = collection.query(
            query_texts=[query],
            n_results=k,
            where={"task_type": task_type}
        )

        print("RAG context length:", len(rubric_context))

        docs = res.get("documents", [[]])[0]
        if not docs:
            return ""

        return "\n\n".join(docs)

    except Exception:
        return ""
