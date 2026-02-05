from __future__ import annotations

from datetime import datetime

from sqlalchemy import DateTime, Integer, String, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database.base import Base


class Client(Base):
    __tablename__ = "clients"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)

    # Número de cliente (OBLIGATORIO y ÚNICO)
    client_number: Mapped[str] = mapped_column(String(30), unique=True, index=True, nullable=False)

    # Nombre del cliente (para ticket)
    full_name: Mapped[str] = mapped_column(String(150), index=True, nullable=False)

    # Campos opcionales (pueden usarse después sin romper diseño)
    phone: Mapped[str | None] = mapped_column(String(25), nullable=True)
    address: Mapped[str | None] = mapped_column(String(255), nullable=True)

    # Auditoría
    created_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), server_default=func.now(), nullable=False)
    updated_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        server_default=func.now(),
        onupdate=func.now(),
        nullable=False,
    )

    def __repr__(self) -> str:
        return f"<Client id={self.id} client_number={self.client_number} full_name={self.full_name}>"
