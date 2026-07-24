from __future__ import annotations

import smtplib
import threading
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
import logging

from app.core.config import settings

logger = logging.getLogger(__name__)


def _build_otp_html(username: str, code: str, expire_minutes: int) -> str:
    return f"""
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Codigo de verificacion</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 20px;">
    <tr>
      <td align="center">
        <table width="520" cellpadding="0" cellspacing="0"
               style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,#0e4fa8 0%,#1a6fcf 100%);padding:32px 40px;text-align:center;">
              <p style="margin:0;color:rgba(255,255,255,0.7);font-size:13px;letter-spacing:2px;text-transform:uppercase;font-weight:600;">
                FINANCIERABAN
              </p>
              <h1 style="margin:8px 0 0;color:#ffffff;font-size:24px;font-weight:900;letter-spacing:1px;">
                Verificacion de Dispositivo
              </h1>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style="padding:36px 40px 28px;">
              <p style="margin:0 0 16px;color:#334155;font-size:15px;line-height:1.6;">
                Hola, <strong>{username}</strong>.
              </p>
              <p style="margin:0 0 24px;color:#64748b;font-size:14px;line-height:1.6;">
                Detectamos un inicio de sesion desde un dispositivo nuevo en la aplicacion movil de FinancieraBan.
                Usa el siguiente codigo para verificar tu dispositivo:
              </p>
              <!-- OTP Code -->
              <div style="background:#f0f9ff;border:2px dashed #0ea5e9;border-radius:12px;padding:24px;text-align:center;margin-bottom:24px;">
                <p style="margin:0 0 8px;color:#0369a1;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">
                  Tu codigo de verificacion
                </p>
                <p style="margin:0;font-size:42px;font-weight:900;letter-spacing:10px;color:#0e4fa8;font-family:monospace;">
                  {code}
                </p>
              </div>
              <!-- Warning -->
              <table width="100%" cellpadding="0" cellspacing="0"
                     style="background:#fef3c7;border-left:4px solid #f59e0b;border-radius:8px;margin-bottom:24px;">
                <tr>
                  <td style="padding:12px 16px;">
                    <p style="margin:0;color:#92400e;font-size:13px;font-weight:600;">
                      &#9203; Este codigo expira en {expire_minutes} minutos.
                    </p>
                    <p style="margin:4px 0 0;color:#b45309;font-size:12px;">
                      Si no intentaste iniciar sesion, ignora este correo. Tu cuenta sigue segura.
                    </p>
                  </td>
                </tr>
              </table>
              <p style="margin:0;color:#94a3b8;font-size:12px;text-align:center;line-height:1.5;">
                No compartas este codigo con nadie.<br/>
                FinancieraBan nunca te pedira tu contrasena por correo.
              </p>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="background:#f8fafc;padding:16px 40px;text-align:center;border-top:1px solid #e2e8f0;">
              <p style="margin:0;color:#94a3b8;font-size:11px;">
                &copy; 2026 FinancieraBan &mdash; Todos los derechos reservados.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
"""


def _send_email_sync(to_email: str, subject: str, html_body: str) -> None:
    """Envio SMTP sincrono (ejecutado en hilo separado)."""
    if not settings.SMTP_USER or not settings.SMTP_PASSWORD:
        logger.warning("SMTP no configurado. El email NO fue enviado a %s", to_email)
        logger.info("Modo simulacion - OTP email a %s - Asunto: %s", to_email, subject)
        return

    msg = MIMEMultipart("alternative")
    msg["Subject"] = subject
    msg["From"] = settings.SMTP_FROM
    msg["To"] = to_email

    msg.attach(MIMEText(html_body, "html", "utf-8"))

    try:
        if settings.SMTP_TLS:
            server = smtplib.SMTP(settings.SMTP_HOST, settings.SMTP_PORT, timeout=15)
            server.ehlo()
            server.starttls()
            server.ehlo()
        else:
            server = smtplib.SMTP_SSL(settings.SMTP_HOST, settings.SMTP_PORT, timeout=15)

        server.login(settings.SMTP_USER, settings.SMTP_PASSWORD)
        server.sendmail(settings.SMTP_USER, [to_email], msg.as_string())
        server.quit()
        logger.info("Email OTP enviado exitosamente a %s", to_email)
    except Exception as exc:
        logger.error("Error enviando email a %s: %s", to_email, exc)
        raise


def send_otp_email(to_email: str, username: str, code: str) -> None:
    """
    Envia el codigo OTP por correo de forma no bloqueante
    (en un hilo separado para responder inmediatamente a la app).
    """
    subject = f"Tu codigo de verificacion es {code} — FinancieraBan"
    html_body = _build_otp_html(
        username=username,
        code=code,
        expire_minutes=settings.OTP_EXPIRE_MINUTES,
    )

    # Hilo daemon: el servidor no espera a que termine para responder
    thread = threading.Thread(
        target=_send_email_sync,
        args=(to_email, subject, html_body),
        daemon=True,
    )
    thread.start()
