from fastapi import APIRouter

router = APIRouter(prefix="/api/v1")


@router.get("/")
async def api_v1_root():
    return {
        "message": "MYBANK API v1",
        "version": "1.0.0",
        "docs": "/docs"
    }
