from fastapi import APIRouter

from app.api.v1.endpoints import auth, users

router = APIRouter(prefix="/api/v1")

# AUTH
router.include_router(
    auth.router,
    tags=["auth"],
)
# USERS (ADMIN)

router.include_router(
    users.router,
    tags=["users"],
)

# ROOT

@router.get("/")
async def api_v1_root():
    return {
        "message": "MYBANK API v1",
        "version": "1.0.0",
        "docs": "/docs",
    }
