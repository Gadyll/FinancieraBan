from typing import Union
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.database.session import get_db
from app.schemas.auth import (
    LoginRequest,
    OTPVerifyRequest,
    OTPRequiredResponse,
    TokenResponse,
    RefreshRequest,
)
from app.services.auth_service import authenticate_user, generate_tokens_for_user
from app.services.otp_service import (
    is_device_trusted,
    generate_otp,
    trust_device,
    verify_otp,
    OTPResult,
)
from app.services.email_service import send_otp_email
from app.core.dependencies import get_current_user, require_admin
from app.core.security import decode_refresh_token, get_subject
from app.models.user import User, UserRole
from pydantic import BaseModel


router = APIRouter(prefix="/auth", tags=["auth"])


class WebLoginRequest(BaseModel):
    username: str
    password: str


# ─────────────────────────────────────────────────────────────────────────────
# PANEL WEB — login sin OTP (ADMIN y USER con rol de cobrador pueden entrar)
# ─────────────────────────────────────────────────────────────────────────────

@router.post("/web-login", response_model=TokenResponse)
def web_login(data: WebLoginRequest, db: Session = Depends(get_db)):
    """
    Login para el panel web administrativo.
    - No requiere OTP ni device_id.
    - Acepta roles ADMIN y USER.
    - Implementa sesión única: al hacer login se incrementa token_version,
      invalidando automáticamente cualquier sesión anterior en otros dispositivos.
    """
    user = authenticate_user(db, data.username, data.password)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Credenciales invalidas.",
        )

    # ✅ Generar tokens + incrementar token_version (invalida sesiones previas)
    tokens = generate_tokens_for_user(user, db=db)
    return TokenResponse(**tokens)


@router.post("/admin-login", response_model=TokenResponse)
def admin_login(data: WebLoginRequest, db: Session = Depends(get_db)):
    """
    Alias mantenido por compatibilidad. Ahora acepta ADMIN y USER.
    Redirige internamente a web-login.
    """
    user = authenticate_user(db, data.username, data.password)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Credenciales invalidas.",
        )

    tokens = generate_tokens_for_user(user, db=db)
    return TokenResponse(**tokens)


# ─────────────────────────────────────────────────────────────────────────────
# APP MÓVIL — login con OTP y device_id
# ─────────────────────────────────────────────────────────────────────────────

def _mask_email(email: str) -> str:
    """Enmascara el correo: ma***@gmail.com"""
    if not email or "@" not in email:
        return "tu correo registrado"
    local, domain = email.split("@", 1)
    if len(local) <= 2:
        masked_local = local[0] + "***"
    else:
        masked_local = local[:2] + "***"
    return f"{masked_local}@{domain}"


@router.post("/login", response_model=Union[TokenResponse, OTPRequiredResponse])
def login(data: LoginRequest, db: Session = Depends(get_db)):
    """
    Paso 1 del login (app móvil).
    - Valida credenciales.
    - Si el dispositivo ya es de confianza: retorna tokens directamente.
    - Si es un dispositivo nuevo: envia OTP al correo y retorna otp_required=True.
    """
    # 1) Autenticar credenciales
    user = authenticate_user(db, data.username, data.password)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Credenciales invalidas. Verifica usuario y contrasena.",
        )

    # 2) Verificar si el dispositivo ya es de confianza
    if is_device_trusted(db, user.id, data.device_id):
        tokens = generate_tokens_for_user(user, db=db)
        return TokenResponse(**tokens, otp_required=False)

    # 3) Dispositivo nuevo: verificar que el usuario tenga correo
    if not user.email:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Tu cuenta no tiene un correo registrado. Contacta al administrador para asignar uno.",
        )

    # 4) Generar y enviar OTP (el email se envia en hilo separado - respuesta inmediata)
    code = generate_otp(db, user.id, data.device_id)
    send_otp_email(
        to_email=user.email,
        username=user.username,
        code=code,
    )

    return OTPRequiredResponse(
        otp_required=True,
        message="Se envio un codigo de verificacion a tu correo registrado.",
        masked_email=_mask_email(user.email),
    )


@router.post("/verify-otp", response_model=TokenResponse)
def verify_otp_endpoint(data: OTPVerifyRequest, db: Session = Depends(get_db)):
    """
    Paso 2 del login (solo dispositivos nuevos).
    - Valida el OTP.
    - Si es correcto, registra el dispositivo como de confianza y retorna tokens.
    """
    # 1) Buscar usuario
    user = db.query(User).filter(User.username == data.username).first()
    if not user or not user.is_active:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Usuario invalido.",
        )

    # 2) Verificar OTP
    result = verify_otp(db, user.id, data.device_id, data.otp_code)

    if result == OTPResult.OK:
        # Registrar dispositivo como de confianza
        trust_device(db, user.id, data.device_id, data.device_name)
        tokens = generate_tokens_for_user(user, db=db)
        return TokenResponse(**tokens, otp_required=False)

    elif result == OTPResult.EXPIRED:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="El codigo ha expirado. Solicita uno nuevo.",
        )
    elif result == OTPResult.MAX_ATTEMPTS:
        raise HTTPException(
            status_code=status.HTTP_429_TOO_MANY_REQUESTS,
            detail="Demasiados intentos incorrectos. Solicita un nuevo codigo.",
        )
    elif result == OTPResult.NOT_FOUND:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="No hay un codigo de verificacion activo. Solicita uno nuevo.",
        )
    else:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Codigo incorrecto. Verifica e intenta de nuevo.",
        )


@router.post("/resend-otp")
def resend_otp(data: LoginRequest, db: Session = Depends(get_db)):
    """
    Reenviar OTP cuando expira o el usuario lo solicita.
    Requiere las mismas credenciales del login.
    """
    user = authenticate_user(db, data.username, data.password)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Credenciales invalidas.",
        )

    if not user.email:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Tu cuenta no tiene un correo registrado.",
        )

    # Si el dispositivo ya es de confianza, no necesita OTP
    if is_device_trusted(db, user.id, data.device_id):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Este dispositivo ya esta verificado.",
        )

    code = generate_otp(db, user.id, data.device_id)
    send_otp_email(to_email=user.email, username=user.username, code=code)

    return {
        "message": "Se envio un nuevo codigo de verificacion.",
        "masked_email": _mask_email(user.email),
    }


@router.post("/refresh", response_model=TokenResponse)
def refresh(data: RefreshRequest, db: Session = Depends(get_db)):
    try:
        payload = decode_refresh_token(data.refresh_token)
        user_id = int(get_subject(payload))
    except Exception:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Refresh token invalido o expirado",
        )

    user = db.query(User).filter(User.id == user_id).first()
    if not user or not user.is_active:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Usuario invalido o inactivo",
        )

    # ✅ Validar token_version en el refresh token también
    token_version_in_jwt = payload.get("tv")
    if token_version_in_jwt is not None and int(token_version_in_jwt) != user.token_version:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Sesión expirada. Otro dispositivo inició sesión con este usuario.",
        )

    # No incrementamos token_version en refresh (solo en login)
    return TokenResponse(**generate_tokens_for_user(user, db=None))


@router.get("/me")
def me(current_user: User = Depends(get_current_user)):
    return {
        "id": current_user.id,
        "username": current_user.username,
        "email": current_user.email,
        "role": current_user.role.value if hasattr(current_user.role, "value") else str(current_user.role),
        "is_active": current_user.is_active,
    }


@router.get("/admin-check")
def admin_check(current_user: User = Depends(require_admin)):
    return {"ok": True, "message": "Eres ADMIN"}
