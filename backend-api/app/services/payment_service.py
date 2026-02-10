import os
from datetime import datetime, timezone
from decimal import Decimal, ROUND_HALF_UP

from sqlalchemy.orm import Session
from sqlalchemy import func
from sqlalchemy.exc import IntegrityError

from app.models.loan import Loan
from app.models.loan_schedule import LoanSchedule
from app.models.payment import Payment
from app.models.ticket import Ticket
from app.models.client import Client

from app.services.ticket_pdf import render_ticket_pdf


def _money(v: Decimal) -> Decimal:
    return v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)


def generate_ticket_number(db: Session) -> str:
    today = datetime.now(timezone.utc).strftime("%Y%m%d")
    prefix = f"MYB-{today}-"

    last = (
        db.query(Ticket.ticket_number)
        .filter(Ticket.ticket_number.like(f"{prefix}%"))
        .order_by(Ticket.id.desc())
        .first()
    )

    if not last:
        return f"{prefix}000001"

    last_num = int(last[0].split("-")[-1])
    return f"{prefix}{last_num + 1:06d}"


def _get_schedule_chain(
    db: Session,
    loan_id: int,
    start_schedule_id: int | None,
) -> list[LoanSchedule]:
    q = (
        db.query(LoanSchedule)
        .filter(LoanSchedule.loan_id == loan_id)
        .order_by(LoanSchedule.installment_number.asc())
    )
    rows = q.all()

    if not rows:
        return []

    if start_schedule_id is None:
        return rows

    # empezar desde la cuota indicada
    idx = next((i for i, r in enumerate(rows) if r.id == start_schedule_id), None)
    if idx is None:
        raise ValueError("schedule_id no pertenece a este préstamo")
    return rows[idx:]


def apply_amount_across_schedule(
    db: Session,
    loan: Loan,
    schedule_rows: list[LoanSchedule],
    amount_paid: Decimal,
) -> list[dict]:
    """
    ✅ Permite sobrante: aplica el monto a múltiples cuotas (en orden).
    - Actualiza amount_due y status de cada cuota.
    - Si todas quedan PAID -> loan.status = PAID
    Retorna detalle aplicado para el ticket.
    """
    remaining = _money(amount_paid)
    applied_detail: list[dict] = []

    for s in schedule_rows:
        if remaining <= Decimal("0.00"):
            break

        if s.status == "PAID":
            continue

        due = _money(s.amount_due)

        pay_here = due if remaining >= due else remaining
        new_due = _money(due - pay_here)

        s.amount_due = new_due
        if new_due == Decimal("0.00"):
            s.status = "PAID"
            s.paid_at = datetime.now(timezone.utc)
        else:
            s.status = "PARTIAL"

        applied_detail.append(
            {
                "schedule_id": s.id,
                "installment": s.installment_number,
                "due_date": str(s.due_date),
                "paid": str(pay_here),
                "remaining_due": str(new_due),
                "status": s.status,
            }
        )

        remaining = _money(remaining - pay_here)

    # validar si loan queda pagado
    not_paid = (
        db.query(func.count(LoanSchedule.id))
        .filter(LoanSchedule.loan_id == loan.id)
        .filter(LoanSchedule.status != "PAID")
        .scalar()
    )
    if (not_paid or 0) == 0:
        loan.status = "PAID"

    if not applied_detail:
        raise ValueError("No hay cuotas pendientes para aplicar el pago")

    return applied_detail


def create_payment_ticket_pdf(
    db: Session,
    loan_id: int,
    schedule_id: int | None,
    user_id: int,
    amount_paid: Decimal,
    payment_method: str | None,
    notes: str | None,
    generated_by: str,
    base_dir: str,
) -> tuple[Payment, Ticket, list[dict], str]:
    """
    Transacción:
    1) valida loan
    2) aplica monto a cuotas (sobrante)
    3) crea Payment (registro de transacción)
    4) crea Ticket (folio)
    5) genera PDF y devuelve url
    """
    loan = db.query(Loan).filter(Loan.id == loan_id).first()
    if not loan:
        raise ValueError("Préstamo no encontrado")

    # schedules en orden, empezando por schedule_id si viene
    chain = _get_schedule_chain(db, loan_id, schedule_id)

    applied = apply_amount_across_schedule(db, loan, chain, amount_paid)

    payment = Payment(
        loan_id=loan_id,
        schedule_id=schedule_id,  # referencia de inicio
        user_id=user_id,
        amount_paid=_money(amount_paid),
        paid_at=datetime.now(timezone.utc),
        payment_method=payment_method or "CASH",
        notes=notes,
    )
    db.add(payment)
    db.flush()

    ticket_number = generate_ticket_number(db)
    ticket = Ticket(
        ticket_number=ticket_number,
        payment_id=payment.id,
        generated_by=generated_by,
    )
    db.add(ticket)

    try:
        db.commit()
    except IntegrityError:
        db.rollback()
        raise ValueError("Error creando ticket (folio duplicado). Reintenta.")

    # ✅ Generar PDF SIN tocar BD
    client = db.query(Client).filter(Client.id == loan.client_id).first()

    pdf_path = os.path.join(base_dir, "storage", "tickets", f"{ticket_number}.pdf")
    payload = {
        "client": {
            "full_name": getattr(client, "full_name", ""),
            "client_number": getattr(client, "client_number", ""),
        },
        "loan": {
            "cycle_number": loan.cycle_number,
            "principal_amount": str(loan.principal_amount),
            "interest_rate": str(loan.interest_rate),
            "total_amount": str(loan.total_amount),
            "payment_amount": str(loan.payment_amount),
            "frequency": str(loan.frequency),
            "payments_count": loan.payments_count,
        },
        "payment": {
            "amount_paid": str(_money(amount_paid)),
            "payment_method": payment_method or "CASH",
        },
        "applied": applied,
    }
    render_ticket_pdf(pdf_path, ticket_number, payload)

    pdf_url = f"/static/tickets/{ticket_number}.pdf"

    db.refresh(payment)
    db.refresh(ticket)
    return payment, ticket, applied, pdf_url
