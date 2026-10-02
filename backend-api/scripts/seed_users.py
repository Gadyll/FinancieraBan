"""
Script de seeding para crear usuarios iniciales de FinancieraBan.

Usuarios creados:
  - administrador_financiero / 123456789 (rol: ADMIN)
  - usuario_estandar         / 123456789 (rol: USER)

Ejecución:
    cd backend-api
    venv\Scripts\python.exe scripts\seed_users.py
"""
import sys
import os

# Añadir el directorio raíz al path para importar los módulos de la app
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from app.database.session import SessionLocal
from app.models.user import User, UserRole
from app.core.security import hash_password


INITIAL_USERS = [
    {
        "username": "administrador_financiero",
        "password": "123456789",
        "email": None,
        "role": UserRole.ADMIN,
        "user_number": 1,
    },
    {
        "username": "usuario_estandar",
        "password": "123456789",
        "email": None,
        "role": UserRole.USER,
        "user_number": 2,
    },
]


def seed():
    db = SessionLocal()
    try:
        created = 0
        skipped = 0
        for u in INITIAL_USERS:
            existing = db.query(User).filter(User.username == u["username"]).first()
            if existing:
                print("  [SKIP] '" + u['username'] + "' ya existe (id=" + str(existing.id) + ", role=" + str(existing.role) + ")")
                skipped += 1
                continue

            # Verificar que el user_number no esté ocupado
            next_num = u["user_number"]
            while db.query(User).filter(User.user_number == next_num).first():
                next_num += 100

            user = User(
                username=u["username"],
                password_hash=hash_password(u["password"]),
                email=u["email"],
                role=u["role"],
                user_number=next_num,
                is_active=True,
                token_version=1,
            )
            db.add(user)
            db.commit()
            db.refresh(user)
            print("  [OK]   '" + u['username'] + "' creado (id=" + str(user.id) + ", role=" + str(user.role) + ", folio=" + str(user.user_number) + ")")
            created += 1

        print("\nSeeding completado: " + str(created) + " creados, " + str(skipped) + " omitidos.")
    except Exception as e:
        db.rollback()
        print("\nError durante seeding: " + str(e))
        raise
    finally:
        db.close()


if __name__ == "__main__":
    print("Iniciando seeding de usuarios FinancieraBan...")
    seed()
