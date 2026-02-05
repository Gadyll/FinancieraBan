from __future__ import annotations

from datetime import datetime

from sqlalchemy import Boolean, DateTime, ForeignKey, Integer, UniqueConstraint, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database.base import Base


class ClientAssignment(Base):
    """
    Relación: qué cliente está asignado a qué cobrador (USER).
    El ADMIN asigna clientes a usuarios de la app.
    """

    __tablename__ = "client_assignments"
    __table_args__ = (
        # Evita duplicar la misma asignación activa para el mismo user/cliente
        UniqueConstraint("user_id", "client_id", name="uq_client_assignment_user_client"),
    )

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)

    user_id: Mapped[int] = mapped_column(
        Integer,
        ForeignKey("users.id", ondelete="RESTRICT"),
        index=True,
        nullable=False,
    )

    client_id: Mapped[int] = mapped_column(
        Integer,
        ForeignKey("clients.id", ondelete="RESTRICT"),
        index=True,
        nullable=False,
    )

    assigned_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        server_default=func.now(),
        nullable=False,
    )

    # Para no borrar histórico: desactivas asignación y puedes reasignar después
    is_active: Mapped[bool] = mapped_column(Boolean, nullable=False, default=True)

    def __repr__(self) -> str:
        return f"<ClientAssignment id={self.id} user_id={self.user_id} client_id={self.client_id} active={self.is_active}>"
