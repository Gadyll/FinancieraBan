from __future__ import annotations

from datetime import datetime
from decimal import Decimal

from sqlalchemy import DateTime, ForeignKey, Integer, Numeric, String, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database.base import Base


class LoanSurcharge(Base):
    """
    Recargo por mora autorizado por el administrador.
    Solo el admin puede crear, el cobrador solo lo cobra.
    """
    __tablename__ = "loan_surcharges"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)

    loan_id: Mapped[int] = mapped_column(
        Integer,
        ForeignKey("loans.id", ondelete="CASCADE"),
        index=True,
        nullable=False,
    )

    # Admin que autoriza el recargo
    authorized_by: Mapped[int] = mapped_column(
        Integer,
        ForeignKey("users.id", ondelete="RESTRICT"),
        nullable=False,
    )

    amount: Mapped[Decimal] = mapped_column(Numeric(12, 2), nullable=False)
    reason: Mapped[str | None] = mapped_column(String(255), nullable=True)

    # Estado: PENDING = pendiente de pago, PAID = cobrado
    status: Mapped[str] = mapped_column(String(20), nullable=False, default="PENDING")

    # Si ya fue cobrado: referencia al pago
    payment_id: Mapped[int | None] = mapped_column(
        Integer,
        ForeignKey("payments.id", ondelete="SET NULL"),
        nullable=True,
    )

    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False
    )

    def __repr__(self) -> str:
        return f"<LoanSurcharge id={self.id} loan_id={self.loan_id} amount={self.amount} status={self.status}>"
