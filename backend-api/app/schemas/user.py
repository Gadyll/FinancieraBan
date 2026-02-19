from __future__ import annotations

from typing import Optional, Literal
from pydantic import BaseModel, EmailStr, Field


class UserBase(BaseModel):
    username: str = Field(..., min_length=3, max_length=50)
    email: EmailStr


class UserCreate(UserBase):
    password: str = Field(..., min_length=8, max_length=128)
    role: Literal["USER", "ADMIN"] = "USER"


# ✅ Para evitar tu error ImportError (y para futuro: reset/update)
class UserUpdate(BaseModel):
    username: Optional[str] = Field(None, min_length=3, max_length=50)
    email: Optional[EmailStr] = None
    password: Optional[str] = Field(None, min_length=8, max_length=128)
    role: Optional[Literal["USER", "ADMIN"]] = None
    is_active: Optional[bool] = None


class UserOut(BaseModel):
    id: int
    username: str
    email: Optional[str] = None
    role: str
    is_active: bool

    class Config:
        from_attributes = True
