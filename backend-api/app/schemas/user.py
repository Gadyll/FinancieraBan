from __future__ import annotations

from typing import Optional, Literal
from pydantic import BaseModel, Field, field_validator


class UserBase(BaseModel):
    username: str = Field(..., min_length=3, max_length=50)
    email: Optional[str] = None


class UserCreate(UserBase):
    password: str = Field(..., min_length=8, max_length=128)
    role: Literal["USER", "ADMIN"] = "USER"

    @field_validator("password")
    @classmethod
    def validate_password_strength(cls, v: str) -> str:
        if v is None:
            raise ValueError("La contraseña es obligatoria.")
        if len(v) < 8:
            raise ValueError("La contraseña debe tener mínimo 8 caracteres.")
        if not any(c.isupper() for c in v):
            raise ValueError("La contraseña debe incluir al menos 1 mayúscula.")
        if not any(c.isdigit() for c in v):
            raise ValueError("La contraseña debe incluir al menos 1 número.")
        if not any((not c.isalnum()) for c in v):
            raise ValueError("La contraseña debe incluir al menos 1 caracter especial.")
        return v


class UserUpdate(BaseModel):
    username: Optional[str] = Field(None, min_length=3, max_length=50)
    email: Optional[str] = None
    password: Optional[str] = Field(None, min_length=8, max_length=128)
    role: Optional[Literal["USER", "ADMIN"]] = None
    is_active: Optional[bool] = None

    @field_validator("password")
    @classmethod
    def validate_password_strength_optional(cls, v: Optional[str]) -> Optional[str]:
        if v is None:
            return v
        if len(v) < 8:
            raise ValueError("La contraseña debe tener mínimo 8 caracteres.")
        if not any(c.isupper() for c in v):
            raise ValueError("La contraseña debe incluir al menos 1 mayúscula.")
        if not any(c.isdigit() for c in v):
            raise ValueError("La contraseña debe incluir al menos 1 número.")
        if not any((not c.isalnum()) for c in v):
            raise ValueError("La contraseña debe incluir al menos 1 caracter especial.")
        return v


class UserOut(BaseModel):
    id: int
    user_number: Optional[int] = None   # ✅ Nullable: admin no necesita folio secuencial
    username: str
    email: Optional[str] = None
    role: str
    is_active: bool

    class Config:
        from_attributes = True