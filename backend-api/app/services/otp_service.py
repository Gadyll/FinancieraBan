from __future__ import annotations

import random
import string
from datetime import datetime, timedelta, timezone
from sqlalchemy.orm import Session

from app.core.config import settings
from app.models.otp_code import OtpCode
from app.models.trusted_device import TrustedDevice
from app.models.user import User


# ─── Helpers ────────────────────────────────────────────────────────────────

def _generate_code() -> str:
    """Genera un codigo OTP de 6 digitos numericos aleatorio."""
    return "".join(random.choices(string.digits, k=6))


def _utcnow() -> datetime:
    return datetime.now(timezone.utc)


# ─── Dispositivos de Confianza ───────────────────────────────────────────────

def is_device_trusted(db: Session, user_id: int, device_id: str) -> bool:
    """Retorna True si este dispositivo ya fue verificado para este usuario."""
    record = (
        db.query(TrustedDevice)
        .filter(
            TrustedDevice.user_id == user_id,
            TrustedDevice.device_id == device_id,
        )
        .first()
    )
    return record is not None


def trust_device(db: Session, user_id: int, device_id: str, device_name: str | None = None) -> TrustedDevice:
    """Registra el dispositivo como de confianza (idempotente)."""
    existing = (
        db.query(TrustedDevice)
        .filter(
            TrustedDevice.user_id == user_id,
            TrustedDevice.device_id == device_id,
        )
        .first()
    )
    if existing:
        return existing

    trusted = TrustedDevice(
        user_id=user_id,
        device_id=device_id,
        device_name=device_name,
    )
    db.add(trusted)
    db.commit()
    db.refresh(trusted)
    return trusted


# ─── OTP ─────────────────────────────────────────────────────────────────────

def generate_otp(db: Session, user_id: int, device_id: str) -> str:
    """
    Genera un nuevo OTP para el usuario + dispositivo.
    Invalida cualquier OTP anterior pendiente del mismo par user+device.
    Retorna el codigo generado (para enviarlo por email).
    """
    # Invalidar OTPs anteriores del mismo user+device que no hayan expirado
    db.query(OtpCode).filter(
        OtpCode.user_id == user_id,
        OtpCode.device_id == device_id,
        OtpCode.used == False,  # noqa: E712
    ).update({"used": True})
    db.commit()

    code = _generate_code()
    expires_at = _utcnow() + timedelta(minutes=settings.OTP_EXPIRE_MINUTES)

    otp = OtpCode(
        user_id=user_id,
        device_id=device_id,
        code=code,
        attempts=0,
        used=False,
        expires_at=expires_at,
    )
    db.add(otp)
    db.commit()
    db.refresh(otp)
    return code


class OTPResult:
    OK = "ok"
    INVALID = "invalid"
    EXPIRED = "expired"
    MAX_ATTEMPTS = "max_attempts"
    NOT_FOUND = "not_found"


def verify_otp(db: Session, user_id: int, device_id: str, code: str) -> str:
    """
    Verifica el OTP ingresado.
    Retorna uno de los valores de OTPResult.
    Si es correcto, marca el OTP como used.
    """
    now = _utcnow()

    # Buscar el OTP activo mas reciente
    otp = (
        db.query(OtpCode)
        .filter(
            OtpCode.user_id == user_id,
            OtpCode.device_id == device_id,
            OtpCode.used == False,  # noqa: E712
        )
        .order_by(OtpCode.created_at.desc())
        .first()
    )

    if not otp:
        return OTPResult.NOT_FOUND

    # Verificar si expiro
    # Hacer ambos offset-aware para comparar
    expires_at = otp.expires_at
    if expires_at.tzinfo is None:
        from datetime import timezone as tz
        expires_at = expires_at.replace(tzinfo=tz.utc)

    if now > expires_at:
        otp.used = True
        db.commit()
        return OTPResult.EXPIRED

    # Verificar intentos maximos
    if otp.attempts >= settings.OTP_MAX_ATTEMPTS:
        otp.used = True
        db.commit()
        return OTPResult.MAX_ATTEMPTS

    # Incrementar intentos
    otp.attempts += 1

    if otp.code != code:
        db.commit()
        return OTPResult.INVALID

    # Codigo correcto: marcar como usado
    otp.used = True
    db.commit()
    return OTPResult.OK
