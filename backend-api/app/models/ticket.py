from __future__ import annotations

from datetime import datetime
from enum import Enum

from sqlalchemy import DateTime, Enum as SAEnum, ForeignKey, Integer, String, UniqueConstraint, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database.base import Base


class TicketGeneratedBy(str, Enum):
    ADMIN = "ADMIN"
    USER = "USER"


class Ticket(Base):
    __tablename__ = "tickets"
    __table_args__ = (
        UniqueConstraint("ticket_number", name="uq_ticket_number"),
        UniqueConstraint("payment_id", name="uq_ticket_payment_id"),
    )

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)

    # Folio único del ticket (ej: MBK-20260205-000001)
    ticket_number: Mapped[str] = mapped_column(String(50), unique=True, index=True, nullable=False)

    # Un ticket por pago
    payment_id: Mapped[int] = mapped_column(
        Integer,
        ForeignKey("payments.id", ondelete="CASCADE"),
        index=True,
        nullable=False,
    )

    generated_by: Mapped[TicketGeneratedBy] = mapped_column(
        SAEnum(TicketGeneratedBy),
        nullable=False,
        default=TicketGeneratedBy.USER,
    )

    # Para el futuro (guardar ruta o URL del PDF)
    pdf_path: Mapped[str | None] = mapped_column(String(255), nullable=True)

    created_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now(), nullable=False)

    def __repr__(self) -> str:
        return f"<Ticket id={self.id} ticket_number={self.ticket_number} payment_id={self.payment_id}>"
