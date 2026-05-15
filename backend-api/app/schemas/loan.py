from datetime import date, datetime
from decimal import Decimal, ROUND_HALF_UP
from enum import Enum

from pydantic import BaseModel, Field, field_validator, model_validator


class LoanFrequency(str, Enum):
    WEEKLY   = "WEEKLY"
    BIWEEKLY = "BIWEEKLY"
    MONTHLY  = "MONTHLY"


class LoanCreate(BaseModel):
    client_id:    int     = Field(..., gt=0)
    cycle_number: int | None = Field(default=None, gt=0)

    # ── Nueva lógica FinancieraBan: interés FIJO pactado (no porcentaje) ──
    principal_amount: Decimal = Field(..., gt=0, description="Capital prestado")
    interest_amount:  Decimal = Field(..., ge=0, description="Interés fijo pactado en $")

    # Cuota de cobro manual (opcional) — para redondeos como $2,300 en lugar de $2,280
    payment_amount_override: Decimal | None = Field(
        default=None, ge=0,
        description="Cuota manual redondeada (ej: $2,300 vs $2,280 calculado)"
    )

    payments_count: int = Field(..., gt=0, le=520)
    frequency:      LoanFrequency
    start_date:     date

    @field_validator("principal_amount", "interest_amount")
    @classmethod
    def _quantize_money(cls, v: Decimal) -> Decimal:
        return v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)

    @field_validator("payment_amount_override")
    @classmethod
    def _quantize_override(cls, v: Decimal | None) -> Decimal | None:
        if v is None:
            return None
        return v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)

    @field_validator("payments_count")
    @classmethod
    def _validate_payments_count(cls, payments_count: int, info) -> int:
        freq = info.data.get("frequency")
        if not freq:
            return payments_count

        if freq == LoanFrequency.WEEKLY:
            if payments_count < 16 or payments_count > 104:
                raise ValueError("SEMANAL: mínimo 16 semanas (4 meses), máximo 104 (2 años).")

        elif freq == LoanFrequency.BIWEEKLY:
            if payments_count < 8 or payments_count > 52:
                raise ValueError("QUINCENAL: mínimo 8 pagos (4 meses), máximo 52.")

        elif freq == LoanFrequency.MONTHLY:
            if payments_count < 4 or payments_count > 24:
                raise ValueError("MENSUAL: mínimo 4 meses, máximo 24 meses.")

        return payments_count


class LoanOut(BaseModel):
    id:               int
    client_id:        int
    cycle_number:     int
    principal_amount: Decimal
    interest_amount:  Decimal
    interest_rate:    Decimal   # legacy
    iva_rate:         Decimal
    iva_amount:       Decimal
    total_amount:     Decimal
    payment_amount:   Decimal
    frequency:        str
    payments_count:   int
    start_date:       date
    status:           str
    created_at:       datetime
    updated_at:       datetime

    class Config:
        from_attributes = True


class ScheduleOut(BaseModel):
    id:                 int
    loan_id:            int
    installment_number: int
    due_date:           date
    amount_due:         Decimal
    status:             str
    paid_at:            datetime | None
    created_at:         datetime
    updated_at:         datetime

    class Config:
        from_attributes = True


class LoanWithScheduleOut(LoanOut):
    schedule: list[ScheduleOut] = []