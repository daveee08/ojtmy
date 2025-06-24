import argparse
import os
from langchain_ollama import OllamaLLM as Ollama
from langchain.prompts import PromptTemplate
from langchain_community.document_loaders import PyPDFLoader

# Step 1: Parse CLI arguments
parser = argparse.ArgumentParser()
parser.add_argument("--text", type=str, help="Text content to summarize")
parser.add_argument("--pdf", type=str, help="Path to PDF file")
parser.add_argument("--conditions", type=str, required=True, help="Summary instructions")

args = parser.parse_args()

# Step 2: Load text content
if args.pdf:
    if not os.path.isfile(args.pdf):
        print("PDF file not found.")
        exit()
    loader = PyPDFLoader(args.pdf)
    pages = loader.load()
    text = "\n".join([page.page_content for page in pages])
elif args.text:
    text = args.text
else:
    print("Please provide --text or --pdf.")
    exit()

# Step 3: Create summarization prompt
template = """
You are an intelligent and concise summarization assistant.

Your task is to read the content below and generate a summary that follows the user's specific request.

------------------------
Content:
{text}

Summary Instructions:
{conditions}
------------------------

Please follow the instructions carefully and provide only the summary.
"""

prompt = PromptTemplate.from_template(template)
llm = Ollama(model="gemma3:4b")
chain = prompt | llm

# Step 4: Run summary once and return output (for Laravel)
response = chain.invoke({"text": text, "conditions": args.conditions})
print(response.strip())
