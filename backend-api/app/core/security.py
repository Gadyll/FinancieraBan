from __future__ import annotations

from datetime import datetime, timedelta, timezone
from typing import Any, Dict, Optional

from jose import JWTError, jwt
from passlib.context import CryptContext

from app.core.config import settings

pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")


# =========================
# PASSWORD HASHING
# =========================
def hash_password(password: str) -> str:
    return pwd_context.hash(password)


def verify_password(plain_password: str, hashed_password: str) -> bool:
    return pwd_context.verify(plain_password, hashed_password)


# =========================
# TOKEN CREATION
# =========================
def create_access_token(data: dict, expires_delta: Optional[timedelta] = None) -> str:
    to_encode = data.copy()

    expire = datetime.now(timezone.utc) + (
        expires_delta if expires_delta else timedelta(minutes=settings.ACCESS_TOKEN_EXPIRE_MINUTES)
    )

    to_encode.update(
        {
            "exp": expire,
            "iat": datetime.now(timezone.utc),
            "type": "access",
        }
    )

    return jwt.encode(to_encode, settings.JWT_SECRET, algorithm=settings.JWT_ALGORITHM)


def create_refresh_token(data: dict) -> str:
    to_encode = data.copy()

    expire = datetime.now(timezone.utc) + timedelta(days=settings.REFRESH_TOKEN_EXPIRE_DAYS)

    to_encode.update(
        {
            "exp": expire,
            "iat": datetime.now(timezone.utc),
            "type": "refresh",
        }
    )

    return jwt.encode(to_encode, settings.JWT_SECRET, algorithm=settings.JWT_ALGORITHM)


# =========================
# TOKEN DECODING (GENERIC)
# =========================
def decode_token(token: str) -> Dict[str, Any]:
    """
    Decodifica token sin validar si es access/refresh.
    Útil si en el futuro necesitas lógica genérica.
    """
    try:
        payload = jwt.decode(token, settings.JWT_SECRET, algorithms=[settings.JWT_ALGORITHM])
        return payload
    except JWTError as e:
        raise ValueError(f"Token inválido: {str(e)}")


def verify_token_type(payload: dict, expected_type: str) -> bool:
    return payload.get("type") == expected_type


def get_token_subject(payload: dict) -> Optional[str]:
    return payload.get("sub")


# =========================
# TOKEN DECODING (STRICT)
# =========================
def decode_access_token(token: str) -> Dict[str, Any]:
    """
    Decodifica y valida un ACCESS token.
    Rechaza refresh tokens y tokens sin sub.
    """
    payload = decode_token(token)

    if payload.get("type") != "access":
        raise ValueError("Token inválido: se requiere access token")

    if not payload.get("sub"):
        raise ValueError("Token inválido: falta subject (sub)")

    return payload


def decode_refresh_token(token: str) -> Dict[str, Any]:
    """
    Decodifica y valida un REFRESH token.
    """
    payload = decode_token(token)

    if payload.get("type") != "refresh":
        raise ValueError("Token inválido: se requiere refresh token")

    if not payload.get("sub"):
        raise ValueError("Token inválido: falta subject (sub)")

    return payload


def get_subject(payload: Dict[str, Any]) -> str:
    return str(payload["sub"])


def get_role(payload: Dict[str, Any]) -> Optional[str]:
    return payload.get("role")
