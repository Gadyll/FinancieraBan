from __future__ import annotations

from datetime import datetime
from sqlalchemy import DateTime, ForeignKey, Integer, String, UniqueConstraint, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database.base import Base


class TrustedDevice(Base):
    """
    Dispositivos verificados por usuario.
    Una vez que el cobrador verifica el OTP en un dispositivo,
    se guarda aqui y no se le vuelve a pedir el codigo.
    """
    __tablename__ = "trusted_devices"
    __table_args__ = (
        UniqueConstraint("user_id", "device_id", name="uq_trusted_device"),
    )

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)

    user_id: Mapped[int] = mapped_column(
        Integer, ForeignKey("users.id", ondelete="CASCADE"), nullable=False, index=True
    )

    # UUID unico generado en el dispositivo movil (persistente en AsyncStorage)
    device_id: Mapped[str] = mapped_column(String(64), nullable=False, index=True)

    # Nombre amigable opcional del dispositivo (se puede agregar despues)
    device_name: Mapped[str | None] = mapped_column(String(100), nullable=True)

    verified_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False
    )

    def __repr__(self) -> str:
        return f"<TrustedDevice user_id={self.user_id} device_id={self.device_id[:8]}...>"
