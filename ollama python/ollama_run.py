from fastapi import FastAPI
from chat_router_feb import chat_router  # make sure filename matches

app = FastAPI()
app.include_router(chat_router)

