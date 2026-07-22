from datetime import date, datetime
from decimal import Decimal, ROUND_HALF_UP
from enum import Enum

from pydantic import BaseModel, Field, field_validator, model_validator

from app.services.loan_service import TASAS_VALIDAS


class LoanFrequency(str, Enum):
    WEEKLY   = "WEEKLY"
    BIWEEKLY = "BIWEEKLY"
    MONTHLY  = "MONTHLY"
    YEARLY   = "YEARLY"


class LoanCreate(BaseModel):
    client_id:    int     = Field(..., gt=0)
    cycle_number: int | None = Field(default=None, gt=0)

    # ── Lógica FinancieraBan con Tabla de Tasas Oficial ──
    principal_amount: Decimal = Field(..., gt=0, description="Capital prestado")
    interest_rate:    Decimal = Field(
        ..., ge=Decimal("3.50"), le=Decimal("7.50"),
        description="Tasa de interés (%) de la tabla oficial"
    )

    # Cuota de cobro manual (opcional) — para redondeos manuales
    payment_amount_override: Decimal | None = Field(
        default=None, ge=0,
        description="Cuota manual redondeada (ej: $2,300 vs $2,280 calculado)"
    )

    # frequency MUST come before payments_count so cross-field validator works
    frequency:      LoanFrequency
    payments_count: int = Field(..., gt=0, le=520)
    start_date:     date

    @field_validator("principal_amount")
    @classmethod
    def _quantize_principal(cls, v: Decimal) -> Decimal:
        return v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)

    @field_validator("interest_rate")
    @classmethod
    def _validate_rate(cls, v: Decimal) -> Decimal:
        """Verifica que la tasa exista en la tabla oficial autorizada."""
        rate = v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)
        if rate not in TASAS_VALIDAS:
            raise ValueError(
                f"La tasa {rate}% no existe en la tabla oficial autorizada. "
                f"Rango válido: 3.50% a 7.50% (incrementos de 0.10)."
            )
        return rate

    @field_validator("payment_amount_override")
    @classmethod
    def _quantize_override(cls, v: Decimal | None) -> Decimal | None:
        if v is None:
            return None
        return v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)

    @model_validator(mode="after")
    def _validate_payments_count(self) -> "LoanCreate":
        freq = self.frequency
        payments_count = self.payments_count

        if freq == LoanFrequency.WEEKLY:
            if payments_count < 16 or payments_count > 104:
                raise ValueError("SEMANAL: mínimo 16 semanas (4 meses), máximo 104 (2 años).")

        elif freq == LoanFrequency.BIWEEKLY:
            if payments_count < 8 or payments_count > 52:
                raise ValueError("QUINCENAL: mínimo 8 pagos (4 meses), máximo 52.")

        elif freq == LoanFrequency.MONTHLY:
            if payments_count < 4 or payments_count > 24:
                raise ValueError("MENSUAL: mínimo 4 meses, máximo 24 meses.")

        elif freq == LoanFrequency.YEARLY:
            if payments_count < 1 or payments_count > 10:
                raise ValueError("ANUAL: mínimo 1 año, máximo 10 años.")

        return self


class LoanOut(BaseModel):
    id:               int
    client_id:        int
    cycle_number:     int
    principal_amount: Decimal
    interest_amount:  Decimal
    interest_rate:    Decimal
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