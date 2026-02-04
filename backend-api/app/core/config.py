from functools import lru_cache
from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    APP_NAME: str = "MYBANK_API"
    ENV: str = "local"
    DEBUG: bool = False  # Por defecto False para seguridad

    # JWT
    JWT_SECRET: str
    JWT_ALGORITHM: str = "HS256"
    ACCESS_TOKEN_EXPIRE_MINUTES: int = 30
    REFRESH_TOKEN_EXPIRE_DAYS: int = 14

    # Database - fallback a SQLite si no hay configuración
    DB_URL: str = "sqlite:///./mybank.db"

    # CORS
    CORS_ORIGINS: str = ""

    class Config:
        env_file = ".env"
        case_sensitive = True


@lru_cache()
def get_settings() -> Settings:
    """
    Cached settings - solo se carga una vez
    Usa lru_cache para evitar recargar el .env múltiples veces
    """
    return Settings()


# Instancia global de settings
settings = get_settings()
