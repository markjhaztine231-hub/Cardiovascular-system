# Guideline RAG (pgvector)

Semantic retrieval over the **Philippine Heart Association CAD 2014** guideline.
Patient records, SOAP notes, documents, and diagnoses are **not** part of this
index and are never sent to the embedding provider.

## How it fits together

```
PHA PDF ──LlamaCloud──▶ markdown ──chunk──▶ OpenRouter embeddings ──▶ rag_chunks
                                                                       (pgvector)
patient diagnosis ──▶ query embedding ──▶ cosine search ──▶ top-3 sections
                                                              │
                                          AiController ───────┘
                                          (keyword fallback when RAG is off)
```

| Piece | File |
| --- | --- |
| Settings | `config/rag.php` |
| Schema / extension | `app/Services/Rag/RagSchema.php` |
| PDF download, parse, chunk | `app/Services/Rag/GuidelineRepository.php` |
| Embeddings client | `app/Services/Rag/OpenRouterEmbedder.php` |
| Indexer | `app/Services/Rag/GuidelineIndexer.php` |
| Retriever | `app/Services/Rag/GuidelineRetriever.php` |
| Commands | `app/Console/Commands/IndexGuidelines.php`, `SearchGuidelines.php` |
| Migration | `database/migrations/2026_11_13_000001_create_rag_chunks_table.php` |

## Requirements

- PostgreSQL with pgvector. RDS PostgreSQL 18.3 ships **0.8.1**; the database
  user must be a member of `rds_superuser` to run `CREATE EXTENSION vector`.
- `OPENROUTER_API_KEY` and `LLAMA_CLOUD_API_KEY`.
- Embedding model: `openai/text-embedding-3-small` (1536 dimensions).

Without pgvector the application still runs: the retriever returns `null` and
`AiController` falls back to local term-frequency scoring. The migration only
creates the vector column when the server supports it.

## Setup

```bash
php artisan migrate --force        # creates rag_chunks (and vector column if available)
php artisan rag:index              # parse + embed the guidelines (first run takes minutes)
php artisan rag:search "stable angina" --top=3
```

Then set `RAG_ENABLED=true` in `.env` and rebuild the config cache:

```bash
php artisan config:cache
```

`rag:index` is incremental: chunks whose hash already has an embedding are
skipped. Use `--force` after changing the embedding model or dimensions.
`--dry-run` shows counts without calling OpenRouter.

## Environment

```dotenv
RAG_ENABLED=false
RAG_EMBEDDING_MODEL=openai/text-embedding-3-small
RAG_EMBEDDING_DIMENSIONS=1536
RAG_EMBEDDING_BATCH_SIZE=32
RAG_EMBEDDING_TIMEOUT=60
RAG_TOP_K=3
RAG_MIN_SIMILARITY=0.2
RAG_GUIDELINE_URL=https://www.philheart.org/images/guidelines/cad2014.pdf
```

## Operational notes

- The parsed sections are cached for 30 days under
  `philheart_cad_2014_chunks_v3`; delete that cache entry to force a re-parse.
- Changing the embedding model or dimensions requires a new migration (the
  vector column type is fixed at 1536) and `rag:index --force`.
- `rag:search` ignores `RAG_ENABLED`, so the index can be verified before
  switching the application over.
- Cosine similarity threshold `RAG_MIN_SIMILARITY` filters weak matches. If all
  matches are filtered out, retrieval falls back to keyword scoring.
- If OpenRouter is unavailable during a request, retrieval logs a warning and
  falls back to keyword scoring; patient abstracts still generate.

## Deployment order

1. Deploy code and run `php artisan migrate --force` (creates the table).
2. Run `php artisan rag:index` and verify with `php artisan rag:search`.
3. Set `RAG_ENABLED=true`, run `php artisan config:cache`.
4. Optionally roll back by setting `RAG_ENABLED=false` and rebuilding config —
   no data migration is required.
