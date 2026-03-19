from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from sqlalchemy import func
from datetime import date

from app.database.session import get_db
from app.core.dependencies import require_admin

from app.models.client import Client
from app.models.guarantor import Guarantor
from app.models.client_assignment import ClientAssignment
from app.models.user import User
from app.models.loan import Loan
from app.models.loan_schedule import LoanSchedule

from app.schemas.client import ClientCreate, ClientUpdate, ClientOut, ClientOutAdmin

router = APIRouter(prefix="/clients")


# =========================
# CREATE CLIENT + GUARANTOR
# =========================
@router.post("", response_model=ClientOut, status_code=status.HTTP_201_CREATED)
def create_client(
    data: ClientCreate,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    # Validar duplicado
    exists = db.query(Client).filter(Client.client_number == data.client_number).first()
    if exists:
        raise HTTPException(status_code=400, detail="client_number ya existe")

    # Crear cliente
    client = Client(
        client_number=data.client_number,
        full_name=data.full_name,
        phone=data.phone,
        address=data.address,
        marital_status=data.marital_status,
        spouse_full_name=data.spouse_full_name,
    )

    db.add(client)
    db.flush()  # obtiene ID

    # Crear aval
    guarantor = Guarantor(
        client_id=client.id,
        full_name=data.guarantor_full_name,
        address=data.guarantor_address,
        phone=data.guarantor_phone,
        marital_status=data.guarantor_marital_status,
    )

    db.add(guarantor)
    db.commit()
    db.refresh(client)

    return client


# =========================
# LIST CLIENTS (CON ESTATUS)
# =========================
@router.get("", response_model=list[ClientOutAdmin])
def list_clients(
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    clients = db.query(Client).order_by(Client.id.desc()).all()
    result = []

    for c in clients:

        # ===== ASIGNACIÓN =====
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

        # ===== PRÉSTAMO =====
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
            )

            next_row = (
                db.query(LoanSchedule)
                .filter(LoanSchedule.loan_id == loan.id)
                .filter(LoanSchedule.status != "PAID")
                .order_by(LoanSchedule.installment_number.asc())
                .first()
            )

            next_due_date = next_row.due_date if next_row else None

            if overdue_count and overdue_count > 0:
                loan_status = "ATRASADO"
            else:
                loan_status = "AL_CORRIENTE"

        item = ClientOutAdmin.model_validate(c)

        item.assigned_user_id = assigned_user_id
        item.assigned_username = assigned_username

        item.loan_status = loan_status
        item.overdue_count = int(overdue_count or 0)
        item.next_due_date = next_due_date

        result.append(item)

    return result


# =========================
# UPDATE CLIENT
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
        client.full_name = data.full_name

    if data.phone is not None:
        client.phone = data.phone

    if data.address is not None:
        client.address = data.address

    db.commit()
    db.refresh(client)

    return client


# =========================
# ASSIGN CLIENT TO USER
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

    # Desactivar asignaciones anteriores
    db.query(ClientAssignment).filter(
        ClientAssignment.client_id == client_id,
        ClientAssignment.is_active == True  # noqa
    ).update({"is_active": False})

    # Crear nueva asignación
    assignment = ClientAssignment(
        client_id=client_id,
        user_id=user_id,
        is_active=True,
    )

    db.add(assignment)
    db.commit()

    return {"message": "Cliente asignado correctamente"}