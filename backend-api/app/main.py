from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from fastapi.staticfiles import StaticFiles
import os

from app.database.init_db import init_db
from app.core.config import settings
from app.core.exceptions import MyBankException
from app.api.v1.router import router as v1_router

from contextlib import asynccontextmanager

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))  # backend-api/
TICKETS_DIR = os.path.join(BASE_DIR, "storage", "tickets")
os.makedirs(TICKETS_DIR, exist_ok=True)


@asynccontextmanager
async def lifespan(app: FastAPI):
    init_db()
    yield


from uvicorn.middleware.proxy_headers import ProxyHeadersMiddleware

app = FastAPI(title=settings.APP_NAME, lifespan=lifespan)

# Soporte de Reverse Proxy (Nginx HTTPS y Forwarded IPs)
app.add_middleware(ProxyHeadersMiddleware, trusted_hosts="*")

app.mount("/static", StaticFiles(directory=os.path.join(BASE_DIR, "storage")), name="static")

# CORS
origins = [o.strip() for o in settings.CORS_ORIGINS.split(",") if o.strip()]
cors_kwargs = {
    "allow_credentials": True,
    "allow_methods": ["*"],
    "allow_headers": ["*"],
}
if origins:
    app.add_middleware(CORSMiddleware, allow_origins=origins, **cors_kwargs)
else:
    app.add_middleware(CORSMiddleware, allow_origin_regex=".*", **cors_kwargs)

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

import logging

logger = logging.getLogger("uvicorn.error")

# Handler genérico
@app.exception_handler(Exception)
async def generic_exception_handler(request: Request, exc: Exception):
    logger.exception(f"Unhandled error processing {request.method} {request.url.path}: {exc}")
    return JSONResponse(
        status_code=500,
        content={"error": "Error interno del servidor"},
    )
