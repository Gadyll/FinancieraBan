from __future__ import annotations

from datetime import date, datetime
from decimal import Decimal
import re

from pydantic import BaseModel, Field, field_validator


MARITAL_OPTIONS = {"SOLTERO", "CASADO", "UNION LIBRE", "VIUDO", "DIVORCIADO"}


class ClientCreate(BaseModel):
    full_name: str = Field(..., min_length=3, max_length=150)
    phone: str = Field(..., min_length=10, max_length=10)
    address: str = Field(..., min_length=3, max_length=255)

    marital_status: str = Field(..., min_length=2, max_length=30)
    spouse_full_name: str | None = Field(default=None, max_length=150)

    # ✅ Campos adicionales opcionales para calificación
    birth_date: date | None = Field(default=None)
    occupation: str | None = Field(default=None, max_length=100)
    monthly_income: Decimal | None = Field(default=None, ge=0)

    guarantor_full_name: str = Field(..., min_length=3, max_length=150)
    guarantor_address: str = Field(..., min_length=3, max_length=255)
    guarantor_phone: str = Field(..., min_length=10, max_length=10)
    guarantor_marital_status: str = Field(..., min_length=2, max_length=30)

    @field_validator(
        "full_name",
        "address",
        "marital_status",
        "spouse_full_name",
        "occupation",
        "guarantor_full_name",
        "guarantor_address",
        "guarantor_marital_status",
        mode="before",
    )
    @classmethod
    def strip_strings(cls, v):
        if v is None:
            return None
        return str(v).strip()

    @field_validator("phone", "guarantor_phone")
    @classmethod
    def validate_phone(cls, v: str) -> str:
        v = str(v).strip()
        if not re.fullmatch(r"\d{10}", v):
            raise ValueError("El teléfono debe tener exactamente 10 dígitos numéricos.")
        return v

    @field_validator("marital_status", "guarantor_marital_status")
    @classmethod
    def validate_marital_status(cls, v: str) -> str:
        if v not in MARITAL_OPTIONS:
            raise ValueError("Estado civil inválido.")
        return v


class ClientUpdate(BaseModel):
    full_name: str | None = Field(default=None, min_length=3, max_length=150)
    phone: str | None = Field(default=None, min_length=10, max_length=10)
    address: str | None = Field(default=None, min_length=3, max_length=255)

    marital_status: str | None = Field(default=None, min_length=2, max_length=30)
    spouse_full_name: str | None = Field(default=None, max_length=150)

    birth_date: date | None = Field(default=None)
    occupation: str | None = Field(default=None, max_length=100)
    monthly_income: Decimal | None = Field(default=None, ge=0)

    guarantor_full_name: str | None = Field(default=None, min_length=3, max_length=150)
    guarantor_address: str | None = Field(default=None, min_length=3, max_length=255)
    guarantor_phone: str | None = Field(default=None, min_length=10, max_length=10)
    guarantor_marital_status: str | None = Field(default=None, min_length=2, max_length=30)

    @field_validator(
        "full_name",
        "address",
        "marital_status",
        "spouse_full_name",
        "occupation",
        "guarantor_full_name",
        "guarantor_address",
        "guarantor_marital_status",
        mode="before",
    )
    @classmethod
    def strip_strings(cls, v):
        if v is None:
            return None
        return str(v).strip()

    @field_validator("phone", "guarantor_phone")
    @classmethod
    def validate_phone(cls, v: str | None) -> str | None:
        if v is None:
            return None
        v = str(v).strip()
        if not re.fullmatch(r"\d{10}", v):
            raise ValueError("El teléfono debe tener exactamente 10 dígitos numéricos.")
        return v

    @field_validator("marital_status", "guarantor_marital_status")
    @classmethod
    def validate_marital_status(cls, v: str | None) -> str | None:
        if v is None:
            return None
        if v not in MARITAL_OPTIONS:
            raise ValueError("Estado civil inválido.")
        return v


class GuarantorOut(BaseModel):
    id: int
    full_name: str
    phone: str | None = None
    address: str | None = None
    marital_status: str | None = None

    class Config:
        from_attributes = True


class ClientOut(BaseModel):
    id: int
    client_number: str
    full_name: str
    phone: str | None = None
    address: str | None = None
    marital_status: str | None = None
    spouse_full_name: str | None = None

    # ✅ Campos nuevos
    birth_date: date | None = None
    occupation: str | None = None
    monthly_income: Decimal | None = None

    guarantor: GuarantorOut | None = None

    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class ClientOutAdmin(ClientOut):
    assigned_user_id: int | None = None
    assigned_username: str | None = None
    loan_status: str | None = None
    overdue_count: int | None = 0
    next_due_date: date | None = None
