"""add user_number to users

Revision ID: f3745d90e6dd
Revises: c8b1a6507d5c
Create Date: 2026-02-20 11:55:10.968972

"""
from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = 'f3745d90e6dd'
down_revision: Union[str, Sequence[str], None] = 'c8b1a6507d5c'
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    """Upgrade schema."""
    pass


def downgrade() -> None:
    """Downgrade schema."""
    pass
