from datetime import date, datetime, time
from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session

from app.database.session import get_db
from app.core.dependencies import require_admin
from app.models.ticket import Ticket
from app.models.payment import Payment
from app.models.client import Client
from app.models.user import User

router = APIRouter(prefix="/tickets", tags=["tickets"])


@router.get("/recent")
def recent_tickets(
    limit: int = Query(default=10, ge=1, le=50),
    date: date | None = Query(default=None, description="Filtra por fecha (YYYY-MM-DD)"),
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    q = (
        db.query(Ticket)
        .order_by(Ticket.created_at.desc())
    )

    if date:
        start = datetime.combine(date, time.min)
        end = datetime.combine(date, time.max)
        q = q.filter(Ticket.created_at >= start, Ticket.created_at <= end)

    tickets = q.limit(limit).all()

    # Respuesta simple para dashboard (sin relaciones complejas)
    return [
        {
            "ticket_number": t.ticket_number,
            "created_at": t.created_at,
            "loan_id": t.loan_id,
            "payment_id": t.payment_id,
        }
        for t in tickets
    ]
