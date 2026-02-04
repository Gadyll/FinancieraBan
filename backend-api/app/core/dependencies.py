from fastapi import Depends, Header
from app.core.exceptions import UnauthorizedException
from app.core.security import decode_token, get_token_subject


def get_current_user_id(authorization: str = Header(default="")) -> str:
    """
    Extrae user_id del JWT (Authorization: Bearer <token>)
    """
    if not authorization.startswith("Bearer "):
        raise UnauthorizedException("Falta token Bearer")

    token = authorization.replace("Bearer ", "").strip()
    payload = decode_token(token)
    user_id = get_token_subject(payload)

    if not user_id:
        raise UnauthorizedException("Token sin subject (sub)")

    return user_id
