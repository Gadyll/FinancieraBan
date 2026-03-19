from datetime import datetime
import re
from pydantic import BaseModel, Field, field_validator

marital_status: str
spouse_full_name: str | None = None

guarantor_full_name: str
guarantor_address: str
guarantor_phone: str
guarantor_marital_status: str


def _clean(v: str | None) -> str | None:
    if v is None:
        return None
    v = v.strip()
    return v if v else None


class ClientCreate(BaseModel):
    client_number: str = Field(..., min_length=1, max_length=30)
    full_name: str = Field(..., min_length=3, max_length=150)

    address: str = Field(..., min_length=3, max_length=255)
    phone: str = Field(..., description="Teléfono obligatorio de 10 dígitos")

    marital_status: str = Field(..., min_length=2, max_length=30)
    spouse_full_name: str | None = Field(default=None, max_length=150)

    # ✅ AVAL obligatorio
    guarantor_full_name: str = Field(..., min_length=3, max_length=150)
    guarantor_address: str = Field(..., min_length=3, max_length=255)
    guarantor_phone: str = Field(..., description="Teléfono aval obligatorio de 10 dígitos")
    guarantor_marital_status: str = Field(..., min_length=2, max_length=30)

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

    @field_validator("phone", "guarantor_phone")
    @classmethod
    def validate_phone(cls, v: str) -> str:
        v = v.strip()
        if not re.fullmatch(r"\d{10}", v):
            raise ValueError("El teléfono debe tener exactamente 10 dígitos numéricos")
        return v

    @field_validator(
        "address",
        "marital_status",
        "guarantor_full_name",
        "guarantor_address",
        "guarantor_marital_status",
        "spouse_full_name",
    )
    @classmethod
    def _strip_any(cls, v: str | None) -> str | None:
        return _clean(v)


class ClientUpdate(BaseModel):
    full_name: str | None = Field(default=None, min_length=3, max_length=150)
    address: str | None = Field(default=None, max_length=255)
    phone: str | None = None

    marital_status: str | None = Field(default=None, max_length=30)
    spouse_full_name: str | None = Field(default=None, max_length=150)

    guarantor_full_name: str | None = Field(default=None, max_length=150)
    guarantor_address: str | None = Field(default=None, max_length=255)
    guarantor_phone: str | None = None
    guarantor_marital_status: str | None = Field(default=None, max_length=30)

    @field_validator("phone", "guarantor_phone")
    @classmethod
    def validate_phone(cls, v: str | None) -> str | None:
        if v is None:
            return None
        v = v.strip()
        if not re.fullmatch(r"\d{10}", v):
            raise ValueError("El teléfono debe tener exactamente 10 dígitos numéricos")
        return v

    @field_validator(
        "full_name",
        "address",
        "marital_status",
        "spouse_full_name",
        "guarantor_full_name",
        "guarantor_address",
        "guarantor_marital_status",
    )
    @classmethod
    def _strip_any(cls, v: str | None) -> str | None:
        return _clean(v)


class ClientOut(BaseModel):
    id: int
    client_number: str
    full_name: str

    phone: str | None
    address: str | None

    marital_status: str | None
    spouse_full_name: str | None

    guarantor_full_name: str | None
    guarantor_address: str | None
    guarantor_phone: str | None
    guarantor_marital_status: str | None

    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class ClientOutAdmin(ClientOut):
    assigned_user_id: int | None = None
    assigned_username: str | None = None

    # ✅ Para pintar “al corriente / atrasado” en lista
    loan_status: str | None = None          # SIN_PRESTAMO | AL_CORRIENTE | ATRASADO
    overdue_count: int | None = None
    next_due_date: str | None = None        # ISO string