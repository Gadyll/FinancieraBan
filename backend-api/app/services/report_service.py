from __future__ import annotations

from datetime import date, datetime, time
from sqlalchemy.orm import Session
from sqlalchemy import func

from app.models.payment import Payment
from app.models.ticket import Ticket
from app.models.user import User

def _day_range(d: date) -> tuple[datetime, datetime]:
    start = datetime.combine(d, time.min)
    end = datetime.combine(d, time.max)
    return start, end


def get_daily_report(db: Session, d: date) -> dict:
    start, end = _day_range(d)

    total_paid = (
        db.query(func.coalesce(func.sum(Payment.amount_paid), 0))
        .filter(Payment.paid_at >= start, Payment.paid_at <= end)
        .scalar()
    ) or 0

    payments_count = (
        db.query(func.count(Payment.id))
        .filter(Payment.paid_at >= start, Payment.paid_at <= end)
        .scalar()
    ) or 0

    tickets_count = (
        db.query(func.count(Ticket.id))
        .filter(Ticket.created_at >= start, Ticket.created_at <= end)
        .scalar()
    ) or 0

    return {
        "date": d,
        "total_paid": float(total_paid),
        "payments_count": int(payments_count),
        "tickets_count": int(tickets_count),
    }


def get_daily_report_by_user(db: Session, d: date) -> dict:
    start, end = _day_range(d)

    rows = (
        db.query(
            User.id.label("user_id"),
            User.username.label("username"),
            func.coalesce(func.sum(Payment.amount_paid), 0).label("total_paid"),
            func.count(Payment.id).label("payments_count"),
        )
        .join(Payment, Payment.user_id == User.id)
        .filter(Payment.paid_at >= start, Payment.paid_at <= end)
        .group_by(User.id, User.username)
        .order_by(func.sum(Payment.amount_paid).desc())
        .all()
    )

    # Tickets por user (por Payment -> Ticket)
    ticket_rows = (
        db.query(
            Payment.user_id.label("user_id"),
            func.count(Ticket.id).label("tickets_count"),
        )
        .join(Ticket, Ticket.payment_id == Payment.id)
        .filter(Payment.paid_at >= start, Payment.paid_at <= end)
        .group_by(Payment.user_id)
        .all()
    )
    tickets_map = {int(r.user_id): int(r.tickets_count) for r in ticket_rows}

    items = []
    for r in rows:
        uid = int(r.user_id)
        items.append(
            {
                "user_id": uid,
                "username": str(r.username),
                "total_paid": float(r.total_paid or 0),
                "payments_count": int(r.payments_count or 0),
                "tickets_count": int(tickets_map.get(uid, 0)),
            }
        )

    return {"date": d, "items": items}

