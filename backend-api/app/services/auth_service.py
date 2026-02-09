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


def generate_tokens_for_user(user: User) -> dict:
    payload = {
        "sub": str(user.id),
        "role": user.role.value,
        "username": user.username,
    }
    access_token = create_access_token(payload)
    refresh_token = create_refresh_token(payload)
    return {
        "access_token": access_token,
        "refresh_token": refresh_token,
        "token_type": "bearer",
    }
