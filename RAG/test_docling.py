# from docling.document_converter import DocumentConverter
# from docling.chunking import HybridChunker
# from transformers import AutoTokenizer

# # --- Config ---
# PDF_PATH = "test_book.pdf"  # or a full URL
# TOKENIZER = "NousResearch/Llama-2-7b-chat-hf"

# # --- Load and Convert ---
# print("📄 Converting PDF to DoclingDocument...")
# doc = DocumentConverter().convert(PDF_PATH)

# document = doc.document

# markdown_output = document.export_to_markdown()

# print(markdown_output)

# # --- Tokenizer + Chunker ---
# tokenizer = AutoTokenizer.from_pretrained(TOKENIZER)
# chunker = HybridChunker(tokenizer=tokenizer, max_tokens=8192, merge_peers=True)
# chunks = list(chunker.chunk(dl_doc=doc.document))
# print(f"✅ Total chunks: {len(chunks)}")

# # --- Display Chunk Info ---
# for i, chunk in enumerate(chunks):
#     heading = chunk.meta.headings[0] if chunk.meta.headings else "None"
#     pages = sorted({p.page_no for d in chunk.meta.doc_items for p in d.prov})
#     print(f"\n🔹 Chunk {i}")
#     print(f"   • Heading     : {heading}")
#     print(f"   • Pages       : {pages}")
#     print(f"   • Text Start  : {chunk.text[:120]}...")  # Preview first 120 chars

# print("🧾 Full document structure:\n")
# for i, item in enumerate(doc.document.form_items):
#     try:
#         print(f"🔹 Block {i}")
#         print(f"• Text       : {item.text[:150]}...")
#         print(f"• Font Size  : {item.style.font_size if item.style else 'N/A'}")
#         print(f"• Font Weight: {item.style.font_weight if item.style else 'N/A'}")
#         print(f"• Is Heading : {'Yes' if item.is_heading else 'No'}")
#         print(f"• Page No    : {item.prov[0].page_no if item.prov else 'N/A'}\n")
#     except Exception as e:
#         print(f"⚠️ Skipped block {i} due to error: {e}")

from docling.document_converter import DocumentConverter
import re

# --- Config ---
PDF_PATH = "test_book.pdf"

# --- Load Document ---
doc = DocumentConverter().convert(PDF_PATH)
document = doc.document

# --- Initialize State ---
current_chapter_num = 0
current_chapter_title = "Unknown"

print("📘 Extracting chapters...\n")

for item in document.form_items:
    text = item.text.strip()

    # Match pattern like: Chapter 1: Title
    match = re.match(r"^Chapter\s+(\d+):\s*(.+)", text)
    if match:
        current_chapter_num = int(match.group(1))
        current_chapter_title = match.group(2)
        print(f"✅ Found Chapter {current_chapter_num}: {current_chapter_title}")
    
    elif current_chapter_num > 0:
        # Print chunks under the current chapter
        print(f"\n🔹 From Chapter {current_chapter_num} ({current_chapter_title}):")
        print(f"→ {text[:100]}...\n")
