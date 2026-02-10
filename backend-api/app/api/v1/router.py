from fastapi import APIRouter
from app.api.v1.endpoints import auth, users, clients, loans

router = APIRouter(prefix="/api/v1")

from app.api.v1.endpoints import payments, tickets
router.include_router(payments.router, tags=["payments"])
router.include_router(tickets.router, tags=["tickets"])


router.include_router(auth.router, tags=["auth"])
router.include_router(users.router, tags=["users"])
router.include_router(clients.router, tags=["clients"])
router.include_router(loans.router, tags=["loans"])

@router.get("/")
async def api_v1_root():
    return {"message": "MYBANK API v1", "version": "1.0.0", "docs": "/docs"}
