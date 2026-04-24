"""
Script para crear el usuario ADMIN inicial del sistema MYBANK.
Se ejecuta una sola vez desde terminal.
"""

import sys
import os
import io

# Forzar UTF-8 en la salida de la terminal (Windows)
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

# Permite importar app.*
BASE_DIR = os.path.dirname(os.path.dirname(__file__))
sys.path.append(BASE_DIR)

from sqlalchemy.orm import Session

from app.database.session import SessionLocal
from app.models.user import User, UserRole
from app.core.security import hash_password


def create_admin():
    db: Session = SessionLocal()

    try:
        # Verificar si ya existe un admin
        admin_exists = (
            db.query(User)
            .filter(User.role == UserRole.ADMIN)
            .first()
        )

        if admin_exists:
            print("[!] Ya existe un usuario ADMIN. No se creo otro.")
            return

        # === DATOS DEL ADMIN ===
        username = "admin"
        email    = "admin@mybank.local"
        password = "Admin123!"   # CAMBIAR DESPUES DEL PRIMER LOGIN

        admin = User(
            username=username,
            email=email,
            password_hash=hash_password(password),
            role=UserRole.ADMIN,
            is_active=True,
        )

        db.add(admin)
        db.commit()
        db.refresh(admin)

        print("[OK] ADMIN CREADO CORRECTAMENTE")
        print("================================")
        print(f"Usuario : {username}")
        print(f"Email   : {email}")
        print(f"Password: {password}")
        print("================================")
        print("[!] CAMBIA LA CONTRASENA DESPUES DEL PRIMER LOGIN")

    except Exception as e:
        db.rollback()
        print("[ERROR] CREANDO ADMIN")
        print(str(e))
    finally:
        db.close()


if __name__ == "__main__":
    create_admin()
