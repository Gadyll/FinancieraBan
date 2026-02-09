from fastapi import APIRouter

from app.api.v1.endpoints import auth, users, clients

router = APIRouter(prefix="/api/v1")

router.include_router(auth.router, tags=["auth"])
router.include_router(users.router, tags=["users"])
router.include_router(clients.router, tags=["clients"])


@router.get("/")
async def api_v1_root():
    return {"message": "MYBANK API v1", "version": "1.0.0", "docs": "/docs"}
