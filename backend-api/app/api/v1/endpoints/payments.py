import os
from fastapi import APIRouter, Depends, HTTPException, status, Request
from sqlalchemy.orm import Session

from app.core.dependencies import get_current_user
from app.database.session import get_db
from app.models.client_assignment import ClientAssignment
from app.models.loan import Loan
from app.models.ticket import Ticket
from app.models.payment import Payment
from app.models.user import User, UserRole
from app.schemas.payment import PaymentCreate, PaymentOut, TicketOut, PaymentWithTicketOut, PaymentListItem
from app.services.payment_service import create_payment_ticket_pdf

router = APIRouter(prefix="/payments")


@router.post("", response_model=PaymentWithTicketOut, status_code=status.HTTP_201_CREATED)
def create_payment(
    data: PaymentCreate,
    request: Request,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user),
):
    """
    Crear pago (abono) + ticket + PDF.
    ✅ Permite SOBRANTE: aplica a múltiples cuotas en orden.
    ADMIN: cobra a cualquiera.
    USER: solo a clientes asignados.
    """
    loan = db.query(Loan).filter(Loan.id == data.loan_id).first()
    if not loan:
        raise HTTPException(status_code=404, detail="Préstamo no encontrado")

    if current_user.role != UserRole.ADMIN:
        allowed = (
            db.query(ClientAssignment)
            .filter(ClientAssignment.client_id == loan.client_id)
            .filter(ClientAssignment.user_id == current_user.id)
            .filter(ClientAssignment.is_active == True)  # noqa
            .first()
        )
        if not allowed:
            raise HTTPException(status_code=403, detail="No tienes acceso para cobrar este préstamo")

    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
    base_dir = os.path.dirname(base_dir)  # -> backend-api/

    try:
        payment, ticket, applied, pdf_url = create_payment_ticket_pdf(
            db=db,
            loan_id=data.loan_id,
            schedule_id=data.schedule_id,
            user_id=current_user.id,
            amount_paid=data.amount_paid,
            payment_method=data.payment_method,
            notes=data.notes,
            generated_by=current_user.role.value if hasattr(current_user.role, "value") else str(current_user.role),
            base_dir=base_dir,
        )
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    ticket_out = TicketOut.model_validate(ticket)
    ticket_out.pdf_url = str(request.base_url).rstrip("/") + pdf_url

    return {
        "payment": PaymentOut.model_validate(payment),
        "ticket": ticket_out,
        "applied": applied,
    }


@router.get("/by-loan/{loan_id}", response_model=list[PaymentListItem])
def list_payments_by_loan(
    loan_id: int,
    request: Request,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user),
):
    """
    Lista pagos por préstamo.
    ADMIN: todos.
    USER: solo si el préstamo es de un cliente asignado.
    """
    loan = db.query(Loan).filter(Loan.id == loan_id).first()
    if not loan:
        raise HTTPException(status_code=404, detail="Préstamo no encontrado")

    if current_user.role != UserRole.ADMIN:
        allowed = (
            db.query(ClientAssignment)
            .filter(ClientAssignment.client_id == loan.client_id)
            .filter(ClientAssignment.user_id == current_user.id)
            .filter(ClientAssignment.is_active == True)  # noqa
            .first()
        )
        if not allowed:
            raise HTTPException(status_code=403, detail="No tienes acceso a este préstamo")

    rows = (
        db.query(Payment, Ticket)
        .join(Ticket, Ticket.payment_id == Payment.id)
        .filter(Payment.loan_id == loan_id)
        .order_by(Payment.id.desc())
        .all()
    )

    base = str(request.base_url).rstrip("/")
    out = []
    for p, t in rows:
        out.append(
            PaymentListItem(
                id=p.id,
                loan_id=p.loan_id,
                client_id=loan.client_id,
                cycle_number=loan.cycle_number,
                schedule_id=p.schedule_id,
                amount_paid=p.amount_paid,
                paid_at=p.paid_at,
                ticket_number=t.ticket_number,
                pdf_url=f"{base}/static/tickets/{t.ticket_number}.pdf",
            )
        )
    return out


@router.get("/by-client/{client_id}", response_model=list[PaymentListItem])
def list_payments_by_client(
    client_id: int,
    request: Request,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user),
):
    """
    Lista pagos por cliente (todas sus operaciones).
    ADMIN: todos.
    USER: solo si el cliente está asignado.
    """
    if current_user.role != UserRole.ADMIN:
        allowed = (
            db.query(ClientAssignment)
            .filter(ClientAssignment.client_id == client_id)
            .filter(ClientAssignment.user_id == current_user.id)
            .filter(ClientAssignment.is_active == True)  # noqa
            .first()
        )
        if not allowed:
            raise HTTPException(status_code=403, detail="No tienes acceso a este cliente")

    rows = (
        db.query(Payment, Ticket, Loan)
        .join(Ticket, Ticket.payment_id == Payment.id)
        .join(Loan, Loan.id == Payment.loan_id)
        .filter(Loan.client_id == client_id)
        .order_by(Payment.id.desc())
        .all()
    )

    base = str(request.base_url).rstrip("/")
    out = []
    for p, t, loan in rows:
        out.append(
            PaymentListItem(
                id=p.id,
                loan_id=p.loan_id,
                client_id=loan.client_id,
                cycle_number=loan.cycle_number,
                schedule_id=p.schedule_id,
                amount_paid=p.amount_paid,
                paid_at=p.paid_at,
                ticket_number=t.ticket_number,
                pdf_url=f"{base}/static/tickets/{t.ticket_number}.pdf",
            )
        )
    return out
