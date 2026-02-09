from enum import Enum
import re

from pydantic import BaseModel, Field, EmailStr, field_validator


class UserRole(str, Enum):
    USER = "USER"


class UserCreate(BaseModel):
    username: str = Field(..., min_length=3, max_length=50)
    email: EmailStr | None = None  # ✅ entrada estricta (si quieres)
    password: str = Field(..., min_length=6, max_length=72)
    role: UserRole = UserRole.USER

    @field_validator("username")
    @classmethod
    def validate_username(cls, v: str) -> str:
        if not re.match(r"^[a-zA-Z0-9_-]+$", v):
            raise ValueError("Username solo puede contener letras, números, guiones y guión bajo")
        return v.lower()

    @field_validator("password")
    @classmethod
    def validate_password(cls, v: str) -> str:
        if not any(c.isupper() for c in v):
            raise ValueError("Password debe contener al menos una mayúscula")
        if not any(c.isdigit() for c in v):
            raise ValueError("Password debe contener al menos un número")
        return v


class UserOut(BaseModel):
    id: int
    username: str
    email: str | None  # ✅ salida flexible (evita 500 con .local)
    role: str
    is_active: bool

    class Config:
        from_attributes = True
