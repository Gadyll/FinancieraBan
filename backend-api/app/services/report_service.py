from __future__ import annotations

from datetime import date, datetime, time
from sqlalchemy.orm import Session
from sqlalchemy import func

from app.models.payment import Payment
from app.models.ticket import Ticket
from app.models.user import User, UserRole
from app.models.loan import Loan
from app.models.client import Client
from app.models.client_assignment import ClientAssignment


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
    """
    Retorna TODOS los cobradores activos (USER role).
    Los pagos contados son los de los CLIENTES ASIGNADOS a ese cobrador,
    sin importar quién los registró (admin o cobrador).
    Los cobradores sin pagos ese día también aparecen con count=0.
    """
    start, end = _day_range(d)

    # 1) Todos los cobradores activos
    collectors = (
        db.query(User)
        .filter(User.role == UserRole.USER, User.is_active == True)
        .order_by(User.username.asc())
        .all()
    )

    # 2) Pagos del día agrupados por loan → cliente → cobrador asignado
    #    Subquery: payment_id → cobrador responsable (via client_assignment)
    pay_rows = (
        db.query(
            ClientAssignment.user_id.label("collector_id"),
            func.coalesce(func.sum(Payment.amount_paid), 0).label("total_paid"),
            func.count(Payment.id).label("payments_count"),
        )
        .join(Loan,   Loan.client_id  == ClientAssignment.client_id)
        .join(Payment, Payment.loan_id == Loan.id)
        .filter(
            ClientAssignment.is_active == True,
            Payment.paid_at >= start,
            Payment.paid_at <= end,
        )
        .group_by(ClientAssignment.user_id)
        .all()
    )
    pay_map = {int(r.collector_id): (float(r.total_paid or 0), int(r.payments_count or 0))
               for r in pay_rows}

    # 3) Tickets del día por cobrador (mismo criterio)
    ticket_rows = (
        db.query(
            ClientAssignment.user_id.label("collector_id"),
            func.count(Ticket.id).label("tickets_count"),
        )
        .join(Loan,    Loan.client_id    == ClientAssignment.client_id)
        .join(Payment, Payment.loan_id   == Loan.id)
        .join(Ticket,  Ticket.payment_id == Payment.id)
        .filter(
            ClientAssignment.is_active == True,
            Payment.paid_at >= start,
            Payment.paid_at <= end,
        )
        .group_by(ClientAssignment.user_id)
        .all()
    )
    tickets_map = {int(r.collector_id): int(r.tickets_count or 0) for r in ticket_rows}

    items = []
    for u in collectors:
        uid = int(u.id)
        total_paid_u, payments_count_u = pay_map.get(uid, (0.0, 0))
        items.append({
            "user_id":        uid,
            "username":       str(u.username),
            "total_paid":     total_paid_u,
            "payments_count": payments_count_u,
            "tickets_count":  tickets_map.get(uid, 0),
        })

    # Ordenar: primero los que cobraron más
    items.sort(key=lambda x: x["total_paid"], reverse=True)

    return {"date": d, "items": items}


def get_daily_payments_by_user(db: Session, d: date, user_id: int) -> list[dict]:
    """
    Detalle de cada pago del día para los CLIENTES ASIGNADOS al cobrador,
    sin importar quién registró el pago (admin o cobrador).
    Columna extra 'registered_by' indica quién lo capturó.
    """
    start, end = _day_range(d)

    # Alias para el User que registró el pago (distinto al cobrador)
    from sqlalchemy.orm import aliased
    Registrar = aliased(User)

    rows = (
        db.query(
            Payment.id.label("payment_id"),
            Payment.loan_id.label("loan_id"),
            Payment.amount_paid.label("amount_paid"),
            Payment.payment_method.label("payment_method"),
            Payment.paid_at.label("paid_at"),
            Payment.notes.label("notes"),
            Client.full_name.label("client_name"),
            Client.client_number.label("client_number"),
            Ticket.ticket_number.label("ticket_number"),
            Registrar.username.label("registered_by"),
        )
        # Camino: ClientAssignment → Client → Loan → Payment
        .join(Client,  Client.id        == ClientAssignment.client_id)
        .join(Loan,    Loan.client_id   == Client.id)
        .join(Payment, Payment.loan_id  == Loan.id)
        .outerjoin(Ticket,    Ticket.payment_id  == Payment.id)
        .outerjoin(Registrar, Registrar.id       == Payment.user_id)
        .filter(
            ClientAssignment.user_id   == user_id,
            ClientAssignment.is_active == True,
            Payment.paid_at >= start,
            Payment.paid_at <= end,
        )
        .order_by(Payment.paid_at.asc())
        .distinct()
        .all()
    )

    return [
        {
            "payment_id":    int(r.payment_id),
            "loan_id":       int(r.loan_id) if r.loan_id else None,
            "client_name":   str(r.client_name)   if r.client_name   else "—",
            "client_number": str(r.client_number) if r.client_number else "—",
            "ticket_number": str(r.ticket_number) if r.ticket_number else "—",
            "amount_paid":   float(r.amount_paid or 0),
            "payment_method": str(
                r.payment_method.value
                if hasattr(r.payment_method, "value")
                else r.payment_method
            ),
            # ISO completo con timezone para que el browser muestre la hora local del dispositivo
            "paid_at_iso":   r.paid_at.isoformat() if r.paid_at else None,
            "paid_at":       r.paid_at.strftime("%H:%M") if r.paid_at else "—",
            "registered_by": str(r.registered_by) if r.registered_by else "—",
            "notes":         str(r.notes) if r.notes else None,
        }
        for r in rows
    ]
