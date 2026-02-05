from __future__ import annotations

from datetime import date, datetime
from decimal import Decimal
from enum import Enum

from sqlalchemy import Date, DateTime, Enum as SAEnum, ForeignKey, Integer, Numeric, String, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database.base import Base


class LoanFrequency(str, Enum):
    WEEKLY = "WEEKLY"       # Semanal
    BIWEEKLY = "BIWEEKLY"   # Quincenal
    MONTHLY = "MONTHLY"     # Mensual


class LoanStatus(str, Enum):
    ACTIVE = "ACTIVE"
    PAID = "PAID"
    LATE = "LATE"
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

    # Número de ciclo (por cliente puede tener varios ciclos)
    cycle_number: Mapped[int] = mapped_column(Integer, index=True, nullable=False)

    # Monto (principal)
    principal_amount: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False)

    # Interés (porcentaje) ejemplo: 20.00 = 20%
    interest_rate: Mapped[Decimal] = mapped_column(Numeric(5, 2), nullable=False, default=Decimal("0.00"))

    # Total (monto + interés) lo guardamos calculado para auditoría y ticket
    total_amount: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False)

    # Monto de pago por cuota
    payment_amount: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False)

    # Frecuencia y número de pagos
    frequency: Mapped[LoanFrequency] = mapped_column(SAEnum(LoanFrequency), nullable=False)
    payments_count: Mapped[int] = mapped_column(Integer, nullable=False)

    # Fecha de inicio del plan (de aquí se genera el calendario)
    start_date: Mapped[date] = mapped_column(Date, nullable=False)

    status: Mapped[LoanStatus] = mapped_column(SAEnum(LoanStatus), nullable=False, default=LoanStatus.ACTIVE)

    # Auditoría
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
            f"total={self.total_amount} status={self.status}>"
        )
