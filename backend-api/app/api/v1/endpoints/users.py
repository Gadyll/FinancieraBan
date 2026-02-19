from __future__ import annotations

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from sqlalchemy import or_

from app.database.session import get_db
from app.core.dependencies import require_admin
from app.core.security import hash_password
from app.models.user import User, UserRole
from app.models.payment import Payment
from app.schemas.user import UserCreate, UserOut

router = APIRouter(prefix="/users", tags=["users"])


# =========================
# GET /users
# =========================
@router.get("", response_model=list[UserOut])
def list_users(
    skip: int = 0,
    limit: int = 200,
    _: User = Depends(require_admin),
    db: Session = Depends(get_db),
):
    return db.query(User).order_by(User.id.asc()).offset(skip).limit(limit).all()


# =========================
# POST /users  (Create USER cobrador)
# =========================
@router.post("", response_model=UserOut, status_code=status.HTTP_201_CREATED)
def create_user(
    payload: UserCreate,
    _: User = Depends(require_admin),
    db: Session = Depends(get_db),
):
    # ✅ Solo crear cobradores USER (ADMIN no se crea desde aquí)
    if payload.role != "USER":
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Solo se permite crear cobradores con rol USER.",
        )

    # ✅ Username/email únicos
    exists = db.query(User).filter(
        or_(
            User.username == payload.username.strip(),
            User.email == str(payload.email).strip(),
        )
    ).first()

    if exists:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Username o email ya existe.",
        )

    user = User(
        username=payload.username.strip(),
        email=str(payload.email).strip(),
        password_hash=hash_password(payload.password),
        role=UserRole.USER,
        is_active=True,
    )

    db.add(user)
    db.commit()
    db.refresh(user)
    return user


# =========================
# GET /users/{user_id}
# =========================
@router.get("/{user_id}", response_model=UserOut)
def get_user(
    user_id: int,
    _: User = Depends(require_admin),
    db: Session = Depends(get_db),
):
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado.")
    return user


# =========================
# PATCH /users/{user_id}/toggle-active
# =========================
@router.patch("/{user_id}/toggle-active", response_model=UserOut)
def toggle_user_active(
    user_id: int,
    _: User = Depends(require_admin),
    db: Session = Depends(get_db),
):
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado.")

    # ✅ ADMIN no se toca
    if user.role == UserRole.ADMIN:
        raise HTTPException(status_code=403, detail="No puedes modificar un ADMIN.")

    user.is_active = not bool(user.is_active)
    db.commit()
    db.refresh(user)
    return user


# =========================
# DELETE /users/{user_id}
# Hard delete “nivel banco”:
# - Si tiene historial (payments.user_id), NO se borra (409) -> solo desactivar
# =========================
@router.delete("/{user_id}")
def delete_user(
    user_id: int,
    _: User = Depends(require_admin),
    db: Session = Depends(get_db),
):
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado.")

    # ✅ ADMIN jamás se elimina
    if user.role == UserRole.ADMIN:
        raise HTTPException(status_code=403, detail="No puedes eliminar un ADMIN.")

    # ✅ Historial real: pagos cobrados por este USER
    payments_count = db.query(Payment).filter(Payment.user_id == user_id).count()

    if payments_count > 0:
        # Nivel banco: no borras si hay historial -> solo desactivar
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail=f"No se puede eliminar: este cobrador tiene historial de pagos ({payments_count}). Solo puedes desactivar.",
        )

    # ✅ Hard delete definitivo
    db.delete(user)
    db.commit()
    return {"ok": True, "message": "Usuario eliminado definitivamente."}
