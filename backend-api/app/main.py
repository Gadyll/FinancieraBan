from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from fastapi.staticfiles import StaticFiles
import os

from app.database.init_db import init_db
from app.core.config import settings
from app.core.exceptions import MyBankException
from app.api.v1.router import router as v1_router

app = FastAPI(title=settings.APP_NAME)

@app.on_event("startup")
def on_startup():
    init_db()

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))  # backend-api/
TICKETS_DIR = os.path.join(BASE_DIR, "storage", "tickets")
os.makedirs(TICKETS_DIR, exist_ok=True)

app.mount("/static", StaticFiles(directory=os.path.join(BASE_DIR, "storage")), name="static")

# CORS
origins = [o.strip() for o in settings.CORS_ORIGINS.split(",") if o.strip()]
app.add_middleware(
    CORSMiddleware,
    allow_origins=origins if origins else ["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Routers
app.include_router(v1_router)

# Health check
@app.get("/health")
async def health():
    return {"status": "ok", "app": settings.APP_NAME}

# Handler general para excepciones personalizadas
@app.exception_handler(MyBankException)
async def mybank_exception_handler(request: Request, exc: MyBankException):
    return JSONResponse(
        status_code=400,
        content={"error": exc.message, "details": exc.details},
    )

# Handler genérico
@app.exception_handler(Exception)
async def generic_exception_handler(request: Request, exc: Exception):
    return JSONResponse(
        status_code=500,
        content={"error": "Error interno del servidor"},
    )
