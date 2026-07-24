from pydantic import BaseModel, Field
from typing import Optional


class LoginRequest(BaseModel):
    username: str
    password: str
    device_id: str = Field(..., min_length=8, max_length=64, description="UUID unico del dispositivo")


class OTPVerifyRequest(BaseModel):
    username: str
    otp_code: str = Field(..., min_length=6, max_length=6, description="Codigo OTP de 6 digitos")
    device_id: str = Field(..., min_length=8, max_length=64)
    device_name: Optional[str] = Field(None, max_length=100)


class OTPRequiredResponse(BaseModel):
    otp_required: bool = True
    message: str
    masked_email: str


class RefreshRequest(BaseModel):
    refresh_token: str = Field(..., min_length=10)


class TokenResponse(BaseModel):
    access_token: str
    refresh_token: str
    token_type: str = "bearer"
    otp_required: bool = False
