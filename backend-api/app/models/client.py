from __future__ import annotations

from datetime import datetime
from sqlalchemy import String, DateTime, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database.base import Base


class Client(Base):
    __tablename__ = "clients"

    id: Mapped[int] = mapped_column(primary_key=True, autoincrement=True)

    client_number: Mapped[str] = mapped_column(String(30), unique=True, index=True)
    full_name: Mapped[str] = mapped_column(String(150))
    phone: Mapped[str] = mapped_column(String(10))
    address: Mapped[str] = mapped_column(String(255))

    marital_status: Mapped[str] = mapped_column(String(20))
    spouse_full_name: Mapped[str | None] = mapped_column(String(150), nullable=True)

    created_at: Mapped[datetime] = mapped_column(DateTime, server_default=func.now())
    updated_at: Mapped[datetime] = mapped_column(DateTime, server_default=func.now(), onupdate=func.now())