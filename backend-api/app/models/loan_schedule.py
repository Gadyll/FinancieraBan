from __future__ import annotations

from datetime import date, datetime
from decimal import Decimal
from enum import Enum

from sqlalchemy import Date, DateTime, Enum as SAEnum, ForeignKey, Integer, Numeric, UniqueConstraint, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database.base import Base


class ScheduleStatus(str, Enum):
    PENDING = "PENDING"
    PAID = "PAID"
    PARTIAL = "PARTIAL"
    LATE = "LATE"


class LoanSchedule(Base):
    """
    Calendario de pagos por préstamo.
    Cada registro representa una cuota (#) con su fecha y monto.
    """

    __tablename__ = "loan_schedule"
    __table_args__ = (
        # Evita duplicar el mismo número de cuota dentro del mismo préstamo
        UniqueConstraint("loan_id", "installment_number", name="uq_schedule_loan_installment"),
    )

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)

    loan_id: Mapped[int] = mapped_column(
        Integer,
        ForeignKey("loans.id", ondelete="CASCADE"),
        index=True,
        nullable=False,
    )

    # Pago #1, #2, #3...
    installment_number: Mapped[int] = mapped_column(Integer, nullable=False)

    # Fecha programada del pago
    due_date: Mapped[date] = mapped_column(Date, nullable=False, index=True)

    # Monto que debe pagarse en esta cuota (normalmente payment_amount)
    amount_due: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False)

    status: Mapped[ScheduleStatus] = mapped_column(SAEnum(ScheduleStatus), nullable=False, default=ScheduleStatus.PENDING)

    # Si se completó el pago (opcional)
    paid_at: Mapped[datetime | None] = mapped_column(DateTime(timezone=True), nullable=True)

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
            f"<LoanSchedule id={self.id} loan_id={self.loan_id} "
            f"inst={self.installment_number} due={self.due_date} status={self.status}>"
        )
