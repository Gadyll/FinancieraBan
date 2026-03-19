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
    cycle_number: int | None = Field(default=None, gt=0)

    principal_amount: Decimal = Field(..., gt=0)

    # ✅ interés con 4 decimales
    interest_rate: Decimal = Field(..., ge=0, le=1000)

    payments_count: int = Field(..., gt=0, le=520)
    frequency: LoanFrequency
    start_date: date

    @field_validator("principal_amount")
    @classmethod
    def _quantize_money(cls, v: Decimal) -> Decimal:
        return v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)

    @field_validator("interest_rate")
    @classmethod
    def _quantize_rate(cls, v: Decimal) -> Decimal:
        return v.quantize(Decimal("0.0001"), rounding=ROUND_HALF_UP)

    # ✅ Reglas nivel banco según tu definición
    @field_validator("payments_count")
    @classmethod
    def _validate_payments_count(cls, payments_count: int, info):
        freq = info.data.get("frequency")

        # Si todavía no viene frequency, no validamos aquí
        if not freq:
            return payments_count

        if freq == LoanFrequency.WEEKLY:
            # 4 a 18 meses (aprox) => usamos regla simple: múltiplo de 4 entre 16 y 72
            if payments_count < 16 or payments_count > 72 or (payments_count % 4 != 0):
                raise ValueError("SEMANAL: payments_count debe ser múltiplo de 4 entre 16 y 72 (4 a 18 meses).")

        elif freq == LoanFrequency.BIWEEKLY:
            # 5 a 18 meses => 10 a 36 quincenas (múltiplo de 2)
            if payments_count < 10 or payments_count > 36 or (payments_count % 2 != 0):
                raise ValueError("QUINCENAL: payments_count debe ser múltiplo de 2 entre 10 y 36 (5 a 18 meses).")

        elif freq == LoanFrequency.MONTHLY:
            # 1 a 18 meses => 1 a 18 pagos
            if payments_count < 1 or payments_count > 18:
                raise ValueError("MENSUAL: payments_count debe estar entre 1 y 18 (1 a 18 meses).")

        return payments_count


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