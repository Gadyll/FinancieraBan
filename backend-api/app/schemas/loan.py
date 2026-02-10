from datetime import date, datetime
from decimal import Decimal, ROUND_HALF_UP
from enum import Enum

from pydantic import BaseModel, Field, field_validator


class LoanFrequency(str, Enum):
    WEEKLY = "WEEKLY"
    BIWEEKLY = "BIWEEKLY"
    MONTHLY = "MONTHLY"


class LoanCreate(BaseModel):
    client_id: int = Field(..., gt=0)
    cycle_number: int | None = Field(default=None, gt=0)  # si no viene, se autogenera
    principal_amount: Decimal = Field(..., gt=0)
    interest_rate: Decimal = Field(..., ge=0, le=1000)  # % (ej 20 = 20%)
    payments_count: int = Field(..., gt=0, le=520)  # hasta 10 años semanal
    frequency: LoanFrequency
    start_date: date

    @field_validator("principal_amount", "interest_rate")
    @classmethod
    def _quantize_money(cls, v: Decimal) -> Decimal:
        # Normaliza a 2 decimales
        return v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)


class LoanOut(BaseModel):
    id: int
    client_id: int
    cycle_number: int
    principal_amount: Decimal
    interest_rate: Decimal
    total_amount: Decimal
    payment_amount: Decimal
    frequency: str
    payments_count: int
    start_date: date
    status: str
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class ScheduleOut(BaseModel):
    id: int
    loan_id: int
    installment_number: int
    due_date: date
    amount_due: Decimal
    status: str
    paid_at: datetime | None
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class LoanWithScheduleOut(LoanOut):
    schedule: list[ScheduleOut] = []
