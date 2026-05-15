from __future__ import annotations

from datetime import date, datetime
from decimal import Decimal
from enum import Enum

from sqlalchemy import Date, DateTime, Enum as SAEnum, ForeignKey, Integer, Numeric, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database.base import Base


class LoanFrequency(str, Enum):
    WEEKLY   = "WEEKLY"     # Semanal
    BIWEEKLY = "BIWEEKLY"  # Quincenal
    MONTHLY  = "MONTHLY"   # Mensual


class LoanStatus(str, Enum):
    ACTIVE   = "ACTIVE"
    PAID     = "PAID"
    LATE     = "LATE"
    CANCELED = "CANCELED"


class Loan(Base):
    __tablename__ = "loans"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)

    client_id: Mapped[int] = mapped_column(
        Integer,
        ForeignKey("clients.id", ondelete="RESTRICT"),
        index=True,
        nullable=False,
    )

    cycle_number: Mapped[int] = mapped_column(Integer, index=True, nullable=False)

    principal_amount: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False)

    # Interés pactado fijo (monto en $, NO porcentaje) — nueva lógica FinancieraBan
    interest_amount: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False, default=Decimal("0.00"))

    # Legacy: tasa porcentual (ya no se usa en nuevos préstamos, conservado para historial)
    interest_rate: Mapped[Decimal] = mapped_column(Numeric(9, 4), nullable=False, default=Decimal("0.0000"))

    # IVA: conservado pero en $0 para nuevos préstamos
    iva_rate: Mapped[Decimal] = mapped_column(Numeric(9, 4), nullable=False, default=Decimal("0.0000"))
    iva_amount: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False, default=Decimal("0.00"))

    total_amount: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False)

    payment_amount: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False)

    frequency: Mapped[LoanFrequency] = mapped_column(SAEnum(LoanFrequency), nullable=False)
    payments_count: Mapped[int] = mapped_column(Integer, nullable=False)

    start_date: Mapped[date] = mapped_column(Date, nullable=False)

    status: Mapped[LoanStatus] = mapped_column(SAEnum(LoanStatus), nullable=False, default=LoanStatus.ACTIVE)

    created_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now(), nullable=False)
    updated_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        server_default=func.now(),
        onupdate=func.now(),
        nullable=False,
    )

    def __repr__(self) -> str:
        return (
            f"<Loan id={self.id} client_id={self.client_id} cycle={self.cycle_number} "
            f"total={self.total_amount} iva={self.iva_amount} status={self.status}>"
        )
