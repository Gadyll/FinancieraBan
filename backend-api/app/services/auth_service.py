from sqlalchemy.orm import Session

from app.core.security import verify_password, create_access_token, create_refresh_token
from app.models.user import User


def authenticate_user(db: Session, username: str, password: str) -> User | None:
    user = db.query(User).filter(User.username == username).first()
    if not user:
        return None
    if not user.is_active:
        return None
    if not verify_password(password, user.password_hash):
        return None
    return user


def generate_tokens_for_user(user: User, db: Session | None = None) -> dict:
    """
    Genera access y refresh tokens para el usuario.
    Si se pasa `db`, incrementa token_version ANTES de generar (sesión única concurrente).
    Cada nuevo login invalida tokens anteriores porque el payload lleva el nuevo version.
    """
    if db is not None:
        # ✅ Incrementar versión → invalida todos los tokens anteriores
        user.token_version = (user.token_version or 1) + 1
        db.commit()
        db.refresh(user)

    payload = {
        "sub": str(user.id),
        "role": user.role.value,
        "username": user.username,
        "tv": user.token_version,  # token_version para validar sesión única
    }
    access_token = create_access_token(payload)
    refresh_token = create_refresh_token(payload)
    return {
        "access_token": access_token,
        "refresh_token": refresh_token,
        "token_type": "bearer",
    }
