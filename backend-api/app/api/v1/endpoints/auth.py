from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.core.dependencies import get_current_user, require_admin
from app.database.session import get_db
from app.models.user import User
from app.schemas.auth import LoginRequest, TokenResponse
from app.services.auth_service import authenticate_user, generate_tokens_for_user

router = APIRouter(prefix="/auth")


@router.post("/login", response_model=TokenResponse)
def login(data: LoginRequest, db: Session = Depends(get_db)):
    user = authenticate_user(db, data.username, data.password)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Credenciales inválidas",
        )

    return generate_tokens_for_user(user)


@router.get("/me")
def me(current_user: User = Depends(get_current_user)):
    return {
        "id": current_user.id,
        "username": current_user.username,
        "email": current_user.email,
        "role": current_user.role.value,
        "is_active": current_user.is_active,
    }


@router.get("/admin-check")
def admin_check(current_user: User = Depends(require_admin)):
    return {"ok": True, "message": "Eres ADMIN"}
