from datetime import datetime
from decimal import Decimal
from pydantic import BaseModel, Field


class PaymentCreate(BaseModel):
    loan_id: int = Field(..., gt=0)

    # ✅ opcional: si mandas schedule_id, empieza desde esa cuota.
    # si no mandas, el sistema aplica desde la primer cuota pendiente.
    schedule_id: int | None = Field(default=None, gt=0)

    amount_paid: Decimal = Field(..., gt=0)
    payment_method: str | None = Field(default="CASH", max_length=20)
    notes: str | None = Field(default=None, max_length=255)


class PaymentOut(BaseModel):
    id: int
    loan_id: int
    schedule_id: int | None
    user_id: int
    amount_paid: Decimal
    paid_at: datetime
    payment_method: str | None
    notes: str | None

    class Config:
        from_attributes = True


class TicketOut(BaseModel):
    id: int
    ticket_number: str
    payment_id: int
    generated_by: str
    created_at: datetime
    pdf_url: str | None = None

    class Config:
        from_attributes = True


class PaymentWithTicketOut(BaseModel):
    payment: PaymentOut
    ticket: TicketOut
    applied: list[dict] = []  # detalle de cómo se aplicó a cuotas


class PaymentListItem(BaseModel):
    id: int
    loan_id: int
    client_id: int
    cycle_number: int
    schedule_id: int | None
    amount_paid: Decimal
    paid_at: datetime
    ticket_number: str
    pdf_url: str

    class Config:
        from_attributes = True
