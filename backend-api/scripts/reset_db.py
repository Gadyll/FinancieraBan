"""
reset_db.py  —  MYBANK
=============================================================
Limpia COMPLETAMENTE la base de datos y recrea solo el admin.

Acciones:
  1. Desactiva foreign key checks
  2. Trunca todas las tablas de datos (usuarios, clientes, etc.)
  3. Resetea AUTO_INCREMENT a 1 en cada tabla
  4. Reactiva foreign key checks
  5. Crea el usuario ADMIN con las credenciales actuales

USO:
  cd backend-api
  venv\Scripts\python scripts\reset_db.py
=============================================================
"""

import sys
import os
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

BASE_DIR = os.path.dirname(os.path.dirname(__file__))
sys.path.append(BASE_DIR)

from sqlalchemy import text
from sqlalchemy.orm import Session

from app.database.session import SessionLocal, engine
from app.models.user import User, UserRole
from app.core.security import hash_password

# ── Credenciales del administrador ──────────────────────────────
ADMIN_USERNAME = "administrador_financiero"
ADMIN_EMAIL    = "admin@mybank.local"
ADMIN_PASSWORD = "123456789"
# ────────────────────────────────────────────────────────────────

# Orden de truncado: hijos primero, padres al final
# (respeta dependencias de foreign keys)
TABLES_IN_ORDER = [
    "payments",
    "loan_surcharges",
    "loan_schedule",
    "tickets",
    "loans",
    "client_assignments",
    "guarantors",
    "clients",
    "otp_codes",
    "trusted_devices",
    "users",
]


def reset_database():
    db: Session = SessionLocal()

    print("=" * 60)
    print("  MYBANK — RESET COMPLETO DE BASE DE DATOS")
    print("=" * 60)
    print()

    try:
        with engine.connect() as conn:

            # 1. Desactivar foreign key checks
            conn.execute(text("SET FOREIGN_KEY_CHECKS = 0;"))
            print("[1/3] Foreign key checks DESACTIVADOS")

            # 2. Truncar cada tabla y resetear AUTO_INCREMENT
            for table in TABLES_IN_ORDER:
                try:
                    conn.execute(text(f"TRUNCATE TABLE `{table}`;"))
                    print(f"      ✓ Tabla '{table}' limpiada (AUTO_INCREMENT = 1)")
                except Exception as e:
                    print(f"      ✗ Tabla '{table}' — {e}")

            # 3. Reactivar foreign key checks
            conn.execute(text("SET FOREIGN_KEY_CHECKS = 1;"))
            conn.commit()
            print("[2/3] Foreign key checks REACTIVADOS")
            print()

        # 4. Crear el admin
        print("[3/3] Creando usuario ADMIN ...")

        admin = User(
            user_number = 1,
            username    = ADMIN_USERNAME,
            email       = ADMIN_EMAIL,
            password_hash = hash_password(ADMIN_PASSWORD),
            role        = UserRole.ADMIN,
            is_active   = True,
            token_version = 1,
        )

        db.add(admin)
        db.commit()
        db.refresh(admin)

        print()
        print("=" * 60)
        print("  ✅  BASE DE DATOS RESETEADA CORRECTAMENTE")
        print("=" * 60)
        print(f"  Usuario  : {ADMIN_USERNAME}")
        print(f"  Email    : {ADMIN_EMAIL}")
        print(f"  Password : {ADMIN_PASSWORD}")
        print(f"  Role     : ADMIN")
        print(f"  ID       : {admin.id}  |  Folio: {admin.user_number}")
        print("=" * 60)
        print()
        print("  Todos los registros anteriores fueron eliminados.")
        print("  Los IDs comienzan desde 1 en todas las tablas.")
        print()

    except Exception as e:
        db.rollback()
        print()
        print("[ERROR] Falló el reset de la base de datos:")
        print(str(e))
        import traceback
        traceback.print_exc()
    finally:
        db.close()


if __name__ == "__main__":
    print()
    print("⚠️  ADVERTENCIA: Este script eliminará TODOS los datos.")
    print("   Solo se conservará el perfil ADMIN.")
    print()
    confirm = input("   Escribe 'CONFIRMAR' para continuar: ").strip()
    if confirm == "CONFIRMAR":
        reset_database()
    else:
        print()
        print("   Operación cancelada. No se modificó ningún dato.")
        print()
