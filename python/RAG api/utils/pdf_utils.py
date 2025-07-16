import re
import faiss

def extract_chapter_page_map(doc):
    chapter_page_map = {}
    chapter_order = []
    current_chapter = None

    for item in doc.document.texts:
        heading = item.text.strip() if item.text else ""
        page_nos = {prov.page_no for prov in item.prov}

        match = re.match(r"^Chapter (\d+):", heading, re.IGNORECASE)
        if match:
            current_chapter = int(match.group(1))
            if current_chapter not in chapter_page_map:
                chapter_order.append(current_chapter)
            chapter_page_map.setdefault(current_chapter, set()).update(page_nos)
        elif current_chapter is not None:
            chapter_page_map[current_chapter].update(page_nos)

    # --- Fix overlapping chapter end pages ---
    adjusted_page_ranges = {}
    sorted_chapters = sorted(chapter_order)
    for idx, chapter_number in enumerate(sorted_chapters):
        pages = sorted(chapter_page_map.get(chapter_number, []))
        if not pages:
            continue
        start = pages[0]
        if idx + 1 < len(sorted_chapters):
            next_pages = sorted(chapter_page_map.get(sorted_chapters[idx + 1], []))
            end = min(next_pages) - 1 if next_pages else pages[-1]
        else:
            end = pages[-1]
        adjusted_page_ranges[chapter_number] = (start, end)

    return adjusted_page_ranges  # Now returns accurate (start, end) tuples per chapter




def parse_chapter(chapter_text):
    match = re.match(r"^## Chapter (\d+):\s*(.+)", chapter_text.strip(), re.IGNORECASE)
    if not match:
        return None
    number = int(match.group(1))
    title = match.group(2)
    content = "\n".join(chapter_text.strip().splitlines()[1:]).strip()
    return number, title, content


def get_page_range(pages):
    pages = sorted(pages)
    return (pages[0], pages[-1]) if pages else (None, None)

def chunk_texts(content, chapter_id, book_id, global_id, tokenizer):
    texts, records, buffer = [], [], ""
    sentences = re.split(r"\n\s*\n", content)

    for sentence in sentences:
        candidate = buffer + "\n\n" + sentence if buffer else sentence
        if len(tokenizer.tokenize(candidate)) <= 512:
            buffer = candidate
        else:
            if buffer.strip():
                texts.append(buffer.strip())
                records.append((global_id, chapter_id, book_id, buffer.strip()))
                global_id += 1
            buffer = sentence

    if buffer.strip():
        texts.append(buffer.strip())
        records.append((global_id, chapter_id, book_id, buffer.strip()))
        global_id += 1

    return records, texts, global_id


def build_faiss_index(texts, faiss_path, embedder):
    embeddings = embedder.encode(texts, convert_to_numpy=True).astype("float32")
    index = faiss.IndexFlatL2(embeddings.shape[1])
    index.add(embeddings)
    faiss.write_index(index, faiss_path)
    return index