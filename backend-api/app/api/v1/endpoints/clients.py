from __future__ import annotations

from datetime import date
import re

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from sqlalchemy import func

from app.database.session import get_db
from app.core.dependencies import get_current_user, require_admin

from app.models.client import Client
from app.models.guarantor import Guarantor
from app.models.client_assignment import ClientAssignment
from app.models.user import User, UserRole
from app.models.loan import Loan
from app.models.loan_schedule import LoanSchedule
from app.models.payment import Payment

from app.schemas.client import ClientCreate, ClientUpdate, ClientOut, ClientOutAdmin
from app.schemas.loan import LoanOut
from app.schemas.loan_summary import LoanSummaryOut
from app.schemas.client_dashboard import ClientDashboardOut, ClientLoanDashboardItem

router = APIRouter(prefix="/clients", tags=["clients"])


def generate_next_client_number(db: Session) -> str:
    last_clients = db.query(Client.client_number).order_by(Client.id.desc()).limit(500).all()

    max_num = 0
    # Acepta tanto "Cliente00001" (nuevo) como "C0001" (legado)
    pattern = re.compile(r"^(?:Cliente|C)(\d+)$", re.IGNORECASE)

    for (client_number,) in last_clients:
        if not client_number:
            continue
        match = pattern.match(client_number.strip())
        if match:
            max_num = max(max_num, int(match.group(1)))

    next_num = max_num + 1
    return f"Cliente{next_num:05d}"   # → Cliente00001, Cliente00002, ...


# =========================
# ADMIN: CREATE CLIENT
# =========================
@router.post("", response_model=ClientOut, status_code=status.HTTP_201_CREATED)
def create_client(
    data: ClientCreate,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    client_number = generate_next_client_number(db)

    client = Client(
        client_number=client_number,
        full_name=data.full_name.strip(),
        phone=data.phone,
        address=data.address.strip(),
        marital_status=data.marital_status,
        spouse_full_name=data.spouse_full_name.strip() if data.spouse_full_name else None,
        # ✅ Campos nuevos
        birth_date=data.birth_date,
        occupation=data.occupation.strip() if data.occupation else None,
        monthly_income=data.monthly_income,
    )

    db.add(client)
    db.flush()

    guarantor = Guarantor(
        client_id=client.id,
        full_name=data.guarantor_full_name.strip(),
        address=data.guarantor_address.strip(),
        phone=data.guarantor_phone,
        marital_status=data.guarantor_marital_status,
    )

    db.add(guarantor)
    db.commit()
    db.refresh(client)

    return client


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

    result: list[ClientOutAdmin] = []

    for c in clients:
        assign = (
            db.query(ClientAssignment)
            .filter(ClientAssignment.client_id == c.id)
            .filter(ClientAssignment.is_active == True)  # noqa
            .first()
        )

        assigned_user_id = None
        assigned_username = None

        if assign:
            user = db.query(User).filter(User.id == assign.user_id).first()
            if user:
                assigned_user_id = user.id
                assigned_username = user.username

        loan = (
            db.query(Loan)
            .filter(Loan.client_id == c.id)
            .filter(Loan.status == "ACTIVE")
            .order_by(Loan.id.desc())
            .first()
        )

        loan_status = "SIN_PRESTAMO"
        overdue_count = 0
        next_due_date = None

        if loan:
            today = date.today()

            overdue_count = (
                db.query(func.count(LoanSchedule.id))
                .filter(LoanSchedule.loan_id == loan.id)
                .filter(LoanSchedule.status != "PAID")
                .filter(LoanSchedule.due_date < today)
                .scalar()
            ) or 0

            next_row = (
                db.query(LoanSchedule)
                .filter(LoanSchedule.loan_id == loan.id)
                .filter(LoanSchedule.status != "PAID")
                .order_by(LoanSchedule.installment_number.asc())
                .first()
            )

            next_due_date = next_row.due_date if next_row else None
            loan_status = "ATRASADO" if overdue_count > 0 else "AL_CORRIENTE"

        item = ClientOutAdmin.model_validate(c)
        item.assigned_user_id = assigned_user_id
        item.assigned_username = assigned_username
        item.loan_status = loan_status
        item.overdue_count = int(overdue_count)
        item.next_due_date = next_due_date

        result.append(item)

    return result


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

    guarantor = db.query(Guarantor).filter(Guarantor.client_id == client_id).first()

    if data.full_name is not None:
        client.full_name = data.full_name.strip()

    if data.phone is not None:
        client.phone = data.phone

    if data.address is not None:
        client.address = data.address.strip()

    if data.marital_status is not None:
        client.marital_status = data.marital_status

    if data.spouse_full_name is not None:
        client.spouse_full_name = data.spouse_full_name.strip() if data.spouse_full_name else None

    # ✅ Campos nuevos
    if data.birth_date is not None:
        client.birth_date = data.birth_date
    if data.occupation is not None:
        client.occupation = data.occupation.strip() if data.occupation else None
    if data.monthly_income is not None:
        client.monthly_income = data.monthly_income

    if guarantor:
        if data.guarantor_full_name is not None:
            guarantor.full_name = data.guarantor_full_name.strip()
        if data.guarantor_address is not None:
            guarantor.address = data.guarantor_address.strip()
        if data.guarantor_phone is not None:
            guarantor.phone = data.guarantor_phone
        if data.guarantor_marital_status is not None:
            guarantor.marital_status = data.guarantor_marital_status

    db.commit()
    db.refresh(client)
    return client


# =========================
# ADMIN: ASSIGN CLIENT
# =========================
@router.post("/{client_id}/assign")
def assign_client(
    client_id: int,
    payload: dict,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    user_id = payload.get("user_id")
    if not user_id:
        raise HTTPException(status_code=400, detail="user_id requerido")

    client = db.query(Client).filter(Client.id == client_id).first()
    if not client:
        raise HTTPException(status_code=404, detail="Cliente no encontrado")

    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario no encontrado")

    if user.role != UserRole.USER:
        raise HTTPException(status_code=400, detail="Solo se puede asignar a cobradores USER")

    if not user.is_active:
        raise HTTPException(status_code=400, detail="Usuario inactivo")

    db.query(ClientAssignment).filter(
        ClientAssignment.client_id == client_id,
        ClientAssignment.is_active == True  # noqa
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


# =========================
# CLIENT LOANS
# =========================
@router.get("/{client_id}/loans", response_model=list[LoanOut])
def get_client_loans(
    client_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user),
):
    client = db.query(Client).filter(Client.id == client_id).first()
    if not client:
        raise HTTPException(status_code=404, detail="Cliente no encontrado")

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
    return loans


# =========================
# CLIENT DASHBOARD
# =========================
@router.get("/{client_id}/dashboard", response_model=ClientDashboardOut)
def client_dashboard(
    client_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user),
):
    client = db.query(Client).filter(Client.id == client_id).first()
    if not client:
        raise HTTPException(status_code=404, detail="Cliente no encontrado")

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

    if not loans:
        return {"client": client, "loans": []}

    loan_ids = [l.id for l in loans]

    paid_rows = (
        db.query(Payment.loan_id, func.coalesce(func.sum(Payment.amount_paid), 0))
        .filter(Payment.loan_id.in_(loan_ids))
        .group_by(Payment.loan_id)
        .all()
    )
    paid_map = {loan_id: total for loan_id, total in paid_rows}

    rem_rows = (
        db.query(LoanSchedule.loan_id, func.coalesce(func.sum(LoanSchedule.amount_due), 0))
        .filter(LoanSchedule.loan_id.in_(loan_ids))
        .filter(LoanSchedule.status != "PAID")
        .group_by(LoanSchedule.loan_id)
        .all()
    )
    rem_map = {loan_id: total for loan_id, total in rem_rows}

    dashboard_items: list[ClientLoanDashboardItem] = []

    for loan in loans:
        next_row = (
            db.query(LoanSchedule)
            .filter(LoanSchedule.loan_id == loan.id)
            .filter(LoanSchedule.status.in_(["PENDING", "PARTIAL"]))
            .order_by(LoanSchedule.installment_number.asc())
            .first()
        )

        overdue_count = (
            db.query(func.count(LoanSchedule.id))
            .filter(LoanSchedule.loan_id == loan.id)
            .filter(LoanSchedule.status != "PAID")
            .filter(LoanSchedule.due_date < date.today())
            .scalar()
        ) or 0

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
            overdue_count=int(overdue_count),
        )

        dashboard_items.append(
            ClientLoanDashboardItem(
                loan=loan,
                summary=summary,
            )
        )

    return ClientDashboardOut(client=client, loans=dashboard_items)