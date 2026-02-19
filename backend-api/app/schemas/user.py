from pydantic import BaseModel, EmailStr, Field
from typing import Optional, Literal


# =========================
# REQUESTS
# =========================

class UserCreate(BaseModel):
    username: str = Field(..., min_length=3, max_length=32, pattern=r"^[a-zA-Z0-9._-]+$")
    email: EmailStr
    password: str = Field(..., min_length=8, max_length=72)  # bcrypt safe limit
    role: Literal["USER"] = "USER"  # Solo cobradores por endpoint

class UserLogin(BaseModel):
    username: str
    password: str


# =========================
# RESPONSES
# =========================

class UserResponse(BaseModel):
    id: int
    username: str
    email: Optional[str] = None
    role: str
    is_active: bool

    class Config:
        from_attributes = True
