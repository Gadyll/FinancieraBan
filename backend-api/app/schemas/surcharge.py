from datetime import datetime
from decimal import Decimal

from pydantic import BaseModel, Field


class SurchargeCreate(BaseModel):
    amount: Decimal = Field(..., gt=0, description="Monto del recargo autorizado por el admin")
    reason: str | None = Field(default=None, max_length=255, description="Motivo del recargo (mora, penalización, etc.)")


class SurchargeOut(BaseModel):
    id: int
    loan_id: int
    authorized_by: int
    amount: Decimal
    reason: str | None
    status: str
    payment_id: int | None
    created_at: datetime

    class Config:
        from_attributes = True
