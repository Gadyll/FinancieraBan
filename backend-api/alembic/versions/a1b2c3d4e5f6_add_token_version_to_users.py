"""add token_version to users for single concurrent session

Revision ID: a1b2c3d4e5f6
Revises: f02248bcc0d3
Create Date: 2026-10-01

"""
from alembic import op
import sqlalchemy as sa

# revision identifiers, used by Alembic.
revision = 'a1b2c3d4e5f6'
down_revision = 'f02248bcc0d3'
branch_labels = None
depends_on = None


def upgrade() -> None:
    # Agregar columna token_version con valor default 1
    op.add_column(
        'users',
        sa.Column('token_version', sa.Integer(), nullable=False, server_default='1')
    )


def downgrade() -> None:
    op.drop_column('users', 'token_version')
