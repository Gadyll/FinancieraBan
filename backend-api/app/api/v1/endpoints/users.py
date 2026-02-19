from typing import List

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.database.session import get_db

from app.models.user import User
from app.models.loan import Loan
from app.models.payment import Payment
from app.models.ticket import Ticket

from app.schemas.user import UserCreate, UserResponse

from app.core.dependencies import require_admin

from app.core.dependencies import get_current_user, require_admin

from app.core.security import hash_password


router = APIRouter(prefix="/users", tags=["users"])


# ============================================================
# POST /api/v1/users  (Create User)
# ============================================================

@router.post("", response_model=UserResponse, status_code=status.HTTP_201_CREATED)
def create_user(
    payload: UserCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(require_admin),
):
    # Solo permite crear USER
    if payload.role != "USER":
        raise HTTPException(status_code=400, detail="Solo se pueden crear usuarios con rol USER.")

    # username único
    if db.query(User).filter(User.username == payload.username).first():
        raise HTTPException(status_code=400, detail="El username ya existe.")

    # email único
    if payload.email and db.query(User).filter(User.email == payload.email).first():
        raise HTTPException(status_code=400, detail="El email ya está registrado.")

    new_user = User(
        username=payload.username,
        email=payload.email,
        hashed_password = hash_password(payload.password),
        role="USER",
        is_active=True,
    )

    db.add(new_user)
    db.commit()
    db.refresh(new_user)
    return new_user


# ============================================================
# GET /api/v1/users  (List Users)
# ============================================================

@router.get("", response_model=List[UserResponse])
def list_users(
    db: Session = Depends(get_db),
    current_user: User = Depends(require_admin),
):
    return db.query(User).order_by(User.id.desc()).all()


# ============================================================
# GET /api/v1/users/{user_id}  (Get User)
# ============================================================

@router.get("/{user_id}", response_model=UserResponse)
def get_user(
    user_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(require_admin),
):
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado.")
    return user


# ============================================================
# PATCH /api/v1/users/{user_id}/toggle-active  (Toggle)
# ============================================================

@router.patch("/{user_id}/toggle-active", response_model=UserResponse)
def toggle_user_active(
    user_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(require_admin),
):
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado.")

    if user.role == "ADMIN":
        raise HTTPException(status_code=400, detail="ADMIN no se puede desactivar.")

    user.is_active = not bool(user.is_active)
    db.commit()
    db.refresh(user)
    return user


# ============================================================
# DELETE /api/v1/users/{user_id}  (Delete User)
#   - HARD DELETE si NO tiene historial
#   - Si tiene historial -> bloquear y pedir desactivar
# ============================================================

@router.delete("/{user_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_user(
    user_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(require_admin),
):
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado.")

    if user.role == "ADMIN":
        raise HTTPException(status_code=400, detail="ADMIN no se puede eliminar.")

    if user.id == current_user.id:
        raise HTTPException(status_code=400, detail="No puedes eliminar tu propio usuario.")

    # Historial (lo profesional):
    has_loan = db.query(Loan).filter(Loan.user_id == user_id).first()
    has_payment = db.query(Payment).filter(Payment.user_id == user_id).first()
    has_ticket = db.query(Ticket).filter(Ticket.user_id == user_id).first()

    if has_loan or has_payment or has_ticket:
        raise HTTPException(
            status_code=400,
            detail="No se puede eliminar: el cobrador tiene historial. Usa desactivar.",
        )

    db.delete(user)
    db.commit()
    return
