from __future__ import annotations

from datetime import datetime
from decimal import Decimal
from enum import Enum

from sqlalchemy import DateTime, Enum as SAEnum, ForeignKey, Integer, Numeric, String, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database.base import Base


class PaymentMethod(str, Enum):
    CASH = "CASH"
    TRANSFER = "TRANSFER"
    CARD = "CARD"
    OTHER = "OTHER"


class Payment(Base):
    __tablename__ = "payments"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)

    # A qué préstamo pertenece el abono
    loan_id: Mapped[int] = mapped_column(
        Integer,
        ForeignKey("loans.id", ondelete="RESTRICT"),
        index=True,
        nullable=False,
    )

    # Qué cuota del calendario cubre (puede ser NULL si es abono libre o adelantado)
    schedule_id: Mapped[int | None] = mapped_column(
        Integer,
        ForeignKey("loan_schedule.id", ondelete="SET NULL"),
        index=True,
        nullable=True,
    )

    # Quién cobró (usuario de la app)
    user_id: Mapped[int] = mapped_column(
        Integer,
        ForeignKey("users.id", ondelete="RESTRICT"),
        index=True,
        nullable=False,
    )

    amount_paid: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False)

    payment_method: Mapped[PaymentMethod] = mapped_column(
        SAEnum(PaymentMethod),
        nullable=False,
        default=PaymentMethod.CASH,
    )

    notes: Mapped[str | None] = mapped_column(String(255), nullable=True)

    paid_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        server_default=func.now(),
        nullable=False,
        index=True,
    )

    # Auditoría
    created_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now(), nullable=False)

    def __repr__(self) -> str:
        return f"<Payment id={self.id} loan_id={self.loan_id} user_id={self.user_id} amount={self.amount_paid}>"
