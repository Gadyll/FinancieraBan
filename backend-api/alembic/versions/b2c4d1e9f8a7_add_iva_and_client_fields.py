"""add_iva_and_client_fields

Revision ID: b2c4d1e9f8a7
Revises: f3745d90e6dd
Create Date: 2026-04-24

Agrega:
  - loans.iva_rate      (DECIMAL 9,4 DEFAULT 16.0000)
  - loans.iva_amount    (DECIMAL 12,2 DEFAULT 0.00)
  - clients.birth_date  (DATE nullable)
  - clients.occupation  (VARCHAR 100 nullable)
  - clients.monthly_income (DECIMAL 12,2 nullable)
"""

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision = 'b2c4d1e9f8a7'
down_revision = 'f3745d90e6dd'
branch_labels = None
depends_on = None


def upgrade() -> None:
    # ── loans: IVA ──────────────────────────────────────────────────
    # Agregar iva_rate con valor por defecto 16% (antes del total_amount para lógica)
    op.add_column(
        'loans',
        sa.Column('iva_rate', sa.Numeric(9, 4), nullable=False, server_default='16.0000'),
    )
    op.add_column(
        'loans',
        sa.Column('iva_amount', sa.Numeric(12, 2), nullable=False, server_default='0.00'),
    )

    # ── clients: campos adicionales ─────────────────────────────────
    op.add_column(
        'clients',
        sa.Column('birth_date', sa.Date(), nullable=True),
    )
    op.add_column(
        'clients',
        sa.Column('occupation', sa.String(100), nullable=True),
    )
    op.add_column(
        'clients',
        sa.Column('monthly_income', sa.Numeric(12, 2), nullable=True),
    )


def downgrade() -> None:
    # loans
    op.drop_column('loans', 'iva_rate')
    op.drop_column('loans', 'iva_amount')

    # clients
    op.drop_column('clients', 'birth_date')
    op.drop_column('clients', 'occupation')
    op.drop_column('clients', 'monthly_income')
