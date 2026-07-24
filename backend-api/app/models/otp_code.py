from __future__ import annotations

from datetime import datetime
from sqlalchemy import Boolean, DateTime, ForeignKey, Integer, String, func
from sqlalchemy.orm import Mapped, mapped_column

from app.database.base import Base


class OtpCode(Base):
    """
    Tabla de codigos OTP de verificacion.
    Se genera uno por usuario + device_id, expira en 5 minutos,
    y se marca como used=True al ser verificado exitosamente.
    """
    __tablename__ = "otp_codes"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, autoincrement=True)

    user_id: Mapped[int] = mapped_column(
        Integer, ForeignKey("users.id", ondelete="CASCADE"), nullable=False, index=True
    )

    # UUID del dispositivo que solicito el OTP
    device_id: Mapped[str] = mapped_column(String(64), nullable=False, index=True)

    # Codigo numerico de 6 digitos (almacenado como string con ceros iniciales)
    code: Mapped[str] = mapped_column(String(6), nullable=False)

    # Cuantas veces se intento con este OTP (max 5)
    attempts: Mapped[int] = mapped_column(Integer, nullable=False, default=0)

    # Si ya fue usado exitosamente
    used: Mapped[bool] = mapped_column(Boolean, nullable=False, default=False)

    # Cuando expira (UTC)
    expires_at: Mapped[datetime] = mapped_column(DateTime(timezone=True), nullable=False)

    created_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True), server_default=func.now(), nullable=False
    )

    def __repr__(self) -> str:
        return f"<OtpCode user_id={self.user_id} device_id={self.device_id[:8]}... used={self.used}>"
