"""add user_number to users

Revision ID: c8b1a6507d5c
Revises: a364b363eb9e
Create Date: 2026-02-20 11:54:13.064488

"""
from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = 'c8b1a6507d5c'
down_revision: Union[str, Sequence[str], None] = 'a364b363eb9e'
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    """Upgrade schema."""
    pass


def downgrade() -> None:
    """Downgrade schema."""
    pass
