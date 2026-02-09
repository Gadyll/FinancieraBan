from datetime import datetime
import re
from pydantic import BaseModel, Field, field_validator


class ClientCreate(BaseModel):
    client_number: str = Field(..., min_length=1, max_length=30)
    full_name: str = Field(..., min_length=3, max_length=150)
    phone: str = Field(..., description="Teléfono obligatorio de 10 dígitos")
    address: str | None = Field(default=None, max_length=255)

    @field_validator("client_number")
    @classmethod
    def validate_client_number(cls, v: str) -> str:
        v = v.strip()
        if not v:
            raise ValueError("client_number es obligatorio")
        return v

    @field_validator("full_name")
    @classmethod
    def validate_full_name(cls, v: str) -> str:
        v = v.strip()
        if len(v) < 3:
            raise ValueError("Nombre debe tener al menos 3 caracteres")
        return v

    @field_validator("phone")
    @classmethod
    def validate_phone(cls, v: str) -> str:
        v = v.strip()
        if not re.fullmatch(r"\d{10}", v):
            raise ValueError("El teléfono debe tener exactamente 10 dígitos numéricos")
        return v


class ClientUpdate(BaseModel):
    full_name: str | None = Field(default=None, min_length=3, max_length=150)
    phone: str | None = None
    address: str | None = Field(default=None, max_length=255)

    @field_validator("phone")
    @classmethod
    def validate_phone(cls, v: str | None) -> str | None:
        if v is None:
            return None
        v = v.strip()
        if not re.fullmatch(r"\d{10}", v):
            raise ValueError("El teléfono debe tener exactamente 10 dígitos numéricos")
        return v


class ClientOut(BaseModel):
    id: int
    client_number: str
    full_name: str
    phone: str
    address: str | None
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class ClientOutAdmin(ClientOut):
    assigned_user_id: int | None = None
    assigned_username: str | None = None
