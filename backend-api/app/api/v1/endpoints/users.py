from __future__ import annotations

import secrets
import string

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
# POST /users (Create USER cobrador)
# =========================
@router.post("", response_model=UserOut, status_code=status.HTTP_201_CREATED)
def create_user(
    payload: UserCreate,
    _: User = Depends(require_admin),
    db: Session = Depends(get_db),
):
    if payload.role != "USER":
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Solo se permite crear cobradores con rol USER.",
        )

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

    if user.role == UserRole.ADMIN:
        raise HTTPException(status_code=403, detail="No puedes modificar un ADMIN.")

    user.is_active = not bool(user.is_active)
    db.commit()
    db.refresh(user)
    return user


# =========================
# DELETE /users/{user_id}
# Hard delete:
# - si tiene historial (payments.user_id) => 409 (solo desactivar)
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

    if user.role == UserRole.ADMIN:
        raise HTTPException(status_code=403, detail="No puedes eliminar un ADMIN.")

    payments_count = db.query(Payment).filter(Payment.user_id == user_id).count()
    if payments_count > 0:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail=f"No se puede eliminar: este cobrador tiene historial de pagos ({payments_count}). Solo puedes desactivar.",
        )

    db.delete(user)
    db.commit()
    return {"ok": True, "message": "Usuario eliminado definitivamente."}


# =========================
# POST /users/{user_id}/reset-password
# Solo ADMIN, solo USER, devuelve temp_password
# =========================
@router.post("/{user_id}/reset-password")
def reset_password(
    user_id: int,
    _: User = Depends(require_admin),
    db: Session = Depends(get_db),
):
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado.")

    # ADMIN no se toca
    if user.role == UserRole.ADMIN:
        raise HTTPException(status_code=403, detail="No puedes resetear contraseña de un ADMIN.")

    # Si está inactivo, igual puedes resetear para recuperar acceso (nivel banco)
    # (si quieres bloquearlo, dime y lo cambiamos)

    # Generador de contraseña temporal “bank-grade”
    # - 12 chars
    # - al menos 1 mayúscula, 1 minúscula, 1 número, 1 especial
    upper = string.ascii_uppercase
    lower = string.ascii_lowercase
    nums = string.digits
    spec = "!@#$%^&*()-_=+[]{}:,.?"

    temp = [
        secrets.choice(upper),
        secrets.choice(lower),
        secrets.choice(nums),
        secrets.choice(spec),
    ]
    alphabet = upper + lower + nums + spec
    temp += [secrets.choice(alphabet) for _ in range(8)]  # total 12
    secrets.SystemRandom().shuffle(temp)
    temp_password = "".join(temp)

    user.password_hash = hash_password(temp_password)
    db.commit()

    return {
        "ok": True,
        "user_id": user.id,
        "username": user.username,
        "temp_password": temp_password,
        "message": "Contraseña reseteada. Entrega la contraseña temporal al cobrador.",
    }