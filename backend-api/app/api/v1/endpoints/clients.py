from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from sqlalchemy.exc import IntegrityError
from sqlalchemy import func

from app.models.loan import Loan
from app.schemas.loan import LoanOut


from app.models.loan_schedule import LoanSchedule
from app.models.payment import Payment
from app.schemas.loan_summary import LoanSummaryOut
from app.schemas.client_dashboard import ClientDashboardOut, ClientLoanDashboardItem


from app.core.dependencies import get_current_user, require_admin
from app.database.session import get_db
from app.models.client import Client
from app.models.client_assignment import ClientAssignment
from app.models.user import User, UserRole
from app.schemas.client import (
    ClientCreate,
    ClientUpdate,
    ClientOut,
    ClientOutAdmin,
)

router = APIRouter(prefix="/clients")


# =========================
# ADMIN: CREATE CLIENT
# =========================
@router.post("", response_model=ClientOut, status_code=status.HTTP_201_CREATED)
def create_client(
    data: ClientCreate,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    client_number = data.client_number.strip()

    exists = (
        db.query(Client)
        .filter(func.lower(Client.client_number) == client_number.lower())
        .first()
    )
    if exists:
        raise HTTPException(status_code=409, detail="client_number ya existe")

    try:
        client = Client(
            client_number=client_number,
            full_name=data.full_name.strip(),
            phone=data.phone,
            address=data.address.strip() if data.address else None,
        )
        db.add(client)
        db.commit()
        db.refresh(client)
        return client
    except IntegrityError:
        db.rollback()
        raise HTTPException(status_code=409, detail="Error creando cliente")


# =========================
# ADMIN: LIST CLIENTS
# =========================
@router.get("", response_model=list[ClientOutAdmin])
def list_clients(
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    clients = (
        db.query(Client)
        .order_by(Client.id.desc())
        .offset(skip)
        .limit(limit)
        .all()
    )

    if not clients:
        return []

    client_ids = [c.id for c in clients]

    rows = (
        db.query(
            ClientAssignment.client_id,
            ClientAssignment.user_id,
            User.username,
        )
        .join(User, User.id == ClientAssignment.user_id)
        .filter(ClientAssignment.client_id.in_(client_ids))
        .filter(ClientAssignment.is_active == True)  # noqa
        .all()
    )

    assignment_map = {
        r[0]: {"user_id": r[1], "username": r[2]} for r in rows
    }

    out = []
    for c in clients:
        item = ClientOutAdmin.model_validate(c)
        a = assignment_map.get(c.id)
        if a:
            item.assigned_user_id = a["user_id"]
            item.assigned_username = a["username"]
        out.append(item)

    return out


# =========================
# ADMIN: UPDATE CLIENT
# =========================
@router.patch("/{client_id}", response_model=ClientOut)
def update_client(
    client_id: int,
    data: ClientUpdate,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    client = db.query(Client).filter(Client.id == client_id).first()
    if not client:
        raise HTTPException(status_code=404, detail="Cliente no encontrado")

    if data.full_name is not None:
        client.full_name = data.full_name.strip()
    if data.phone is not None:
        client.phone = data.phone
    if data.address is not None:
        client.address = data.address.strip() if data.address else None

    db.commit()
    db.refresh(client)
    return client


# =========================
# ADMIN: ASSIGN CLIENT
# =========================
@router.post("/{client_id}/assign/{user_id}")
def assign_client(
    client_id: int,
    user_id: int,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    client = db.query(Client).filter(Client.id == client_id).first()
    if not client:
        raise HTTPException(status_code=404, detail="Cliente no encontrado")

    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado")

    if user.role != UserRole.USER:
        raise HTTPException(
            status_code=400,
            detail="Solo se puede asignar a usuarios cobradores (USER)",
        )

    if not user.is_active:
        raise HTTPException(status_code=400, detail="Usuario inactivo")

    db.query(ClientAssignment).filter(
        ClientAssignment.client_id == client_id,
        ClientAssignment.is_active == True,  # noqa
    ).update({"is_active": False})

    existing = db.query(ClientAssignment).filter(
        ClientAssignment.client_id == client_id,
        ClientAssignment.user_id == user_id,
    ).first()

    if existing:
        existing.is_active = True
        db.commit()
        return {"ok": True, "message": "Asignación reactivada"}

    assignment = ClientAssignment(
        client_id=client_id,
        user_id=user_id,
        is_active=True,
    )
    db.add(assignment)
    db.commit()
    return {"ok": True, "message": "Cliente asignado correctamente"}


# =========================
# USER: MY CLIENTS
# =========================
@router.get("/my", response_model=list[ClientOut])
def my_clients(
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user),
):
    if current_user.role != UserRole.USER:
        raise HTTPException(status_code=403, detail="Solo USER puede acceder")

    return (
        db.query(Client)
        .join(ClientAssignment, ClientAssignment.client_id == Client.id)
        .filter(ClientAssignment.user_id == current_user.id)
        .filter(ClientAssignment.is_active == True)  # noqa
        .order_by(Client.id.desc())
        .all()
    )

@router.get("/{client_id}/loans", response_model=list[LoanOut])
def get_client_loans(
    client_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user),
):
    """
    Préstamos de un cliente.

    - ADMIN: puede ver cualquiera.
    - USER: solo si el cliente está asignado a él.
    """
    # validar cliente existe
    client = db.query(Client).filter(Client.id == client_id).first()
    if not client:
        raise HTTPException(status_code=404, detail="Cliente no encontrado")

    # permisos
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

    # devolver loans del cliente
    loans = (
        db.query(Loan)
        .filter(Loan.client_id == client_id)
        .order_by(Loan.id.desc())
        .all()
    )
    return loans

@router.get("/{client_id}/dashboard", response_model=ClientDashboardOut)
def client_dashboard(
    client_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user),
):
    """
    Dashboard de cliente:
    - client info
    - loans del cliente
    - summary por cada loan
    Permisos:
    - ADMIN: todo
    - USER: solo si cliente asignado
    """
    client = db.query(Client).filter(Client.id == client_id).first()
    if not client:
        raise HTTPException(status_code=404, detail="Cliente no encontrado")

    # permisos
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

    loans = (
        db.query(Loan)
        .filter(Loan.client_id == client_id)
        .order_by(Loan.id.desc())
        .all()
    )

    # Si no hay loans, regresamos client + []
    if not loans:
        return {"client": client, "loans": []}

    loan_ids = [l.id for l in loans]

    # total_paid por loan
    paid_rows = (
        db.query(Payment.loan_id, func.coalesce(func.sum(Payment.amount_paid), 0))
        .filter(Payment.loan_id.in_(loan_ids))
        .group_by(Payment.loan_id)
        .all()
    )
    paid_map = {loan_id: total for loan_id, total in paid_rows}

    # remaining_balance por loan (sum de amount_due no pagadas)
    rem_rows = (
        db.query(LoanSchedule.loan_id, func.coalesce(func.sum(LoanSchedule.amount_due), 0))
        .filter(LoanSchedule.loan_id.in_(loan_ids))
        .filter(LoanSchedule.status != "PAID")
        .group_by(LoanSchedule.loan_id)
        .all()
    )
    rem_map = {loan_id: total for loan_id, total in rem_rows}

    # próxima cuota por loan: primera PENDING/PARTIAL
    # (lo hacemos con query por loan_id para mantenerlo simple y robusto)
    dashboard_items: list[ClientLoanDashboardItem] = []
    for loan in loans:
        next_row = (
            db.query(LoanSchedule)
            .filter(LoanSchedule.loan_id == loan.id)
            .filter(LoanSchedule.status.in_(["PENDING", "PARTIAL"]))
            .order_by(LoanSchedule.installment_number.asc())
            .first()
        )

        summary = LoanSummaryOut(
            loan_id=loan.id,
            client_id=loan.client_id,
            cycle_number=loan.cycle_number,
            status=str(loan.status),
            frequency=str(loan.frequency),
            payments_count=loan.payments_count,

            total_amount=loan.total_amount,
            total_paid=paid_map.get(loan.id, 0),
            remaining_balance=rem_map.get(loan.id, 0),

            next_installment_number=(next_row.installment_number if next_row else None),
            next_due_date=(next_row.due_date if next_row else None),
            next_amount_due=(next_row.amount_due if next_row else None),
            next_status=(next_row.status if next_row else None),

            overdue_count=0,  # si lo quieres aquí también, lo agregamos con otra query
        )

        dashboard_items.append(
            ClientLoanDashboardItem(
                loan=loan,
                summary=summary,
            )
        )

    return ClientDashboardOut(client=client, loans=dashboard_items)
