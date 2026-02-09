from fastapi import APIRouter, Depends, HTTPException, status, Response
from sqlalchemy.orm import Session
from sqlalchemy.exc import IntegrityError
from sqlalchemy import func

from app.core.dependencies import require_admin
from app.core.security import hash_password
from app.database.session import get_db
from app.models.user import User, UserRole
from app.schemas.user import UserCreate, UserOut

router = APIRouter(prefix="/users")


@router.post("", response_model=UserOut, status_code=status.HTTP_201_CREATED)
def create_user(
    data: UserCreate,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    """
    Crear un nuevo usuario (solo cobradores/USER).
    Solo accesible por ADMIN.
    """
    username_lower = data.username.lower()

    # Verificar username único (case-insensitive robusto)
    exists = db.query(User).filter(func.lower(User.username) == username_lower).first()
    if exists:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Username ya existe")

    # Verificar email único si viene (case-insensitive robusto)
    if data.email:
        email_lower = data.email.lower()
        email_exists = db.query(User).filter(func.lower(User.email) == email_lower).first()
        if email_exists:
            raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Email ya existe")

    try:
        user = User(
            username=username_lower,
            email=data.email.lower() if data.email else None,
            password_hash=hash_password(data.password),
            is_active=True,
            role=UserRole.USER,  # Siempre USER
        )

        db.add(user)
        db.commit()
        db.refresh(user)
        return user

    except IntegrityError:
        db.rollback()
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Error al crear usuario: posible duplicado en base de datos",
        )


@router.get("", response_model=list[UserOut])
def list_users(
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    """
    Listar todos los usuarios con paginación.
    Solo accesible por ADMIN.
    """
    return (
        db.query(User)
        .order_by(User.id.desc())
        .offset(skip)
        .limit(limit)
        .all()
    )


@router.get("/{user_id}", response_model=UserOut)
def get_user(
    user_id: int,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    """
    Obtener un usuario por ID.
    Solo accesible por ADMIN.
    """
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Usuario no encontrado")
    return user


@router.patch("/{user_id}/toggle-active", response_model=UserOut)
def toggle_user_active(
    user_id: int,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    """
    Activar/desactivar un usuario.
    No permite desactivar usuarios ADMIN.
    Solo accesible por ADMIN.
    """
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Usuario no encontrado")

    if user.role == UserRole.ADMIN:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="No puedes desactivar usuarios ADMIN")

    user.is_active = not user.is_active
    db.commit()
    db.refresh(user)
    return user


@router.delete("/{user_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_user(
    user_id: int,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    """
    Soft delete: marcar usuario como inactivo.
    No permite eliminar usuarios ADMIN.
    Solo accesible por ADMIN.
    """
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Usuario no encontrado")

    if user.role == UserRole.ADMIN:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="No puedes eliminar usuarios ADMIN")

    user.is_active = False
    db.commit()
    return Response(status_code=status.HTTP_204_NO_CONTENT)
