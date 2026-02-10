from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.core.dependencies import get_current_user, require_admin
from app.database.session import get_db
from app.models.client import Client
from app.models.client_assignment import ClientAssignment
from app.models.loan import Loan
from app.models.loan_schedule import LoanSchedule
from app.models.user import User, UserRole
from app.schemas.loan import LoanCreate, LoanOut, ScheduleOut, LoanWithScheduleOut
from app.services.loan_service import create_loan_with_schedule

router = APIRouter(prefix="/loans")


# =========================
# ADMIN: CREATE LOAN + SCHEDULE
# =========================
@router.post("", response_model=LoanOut, status_code=status.HTTP_201_CREATED)
def create_loan(
    data: LoanCreate,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    # Validar que exista cliente
    client = db.query(Client).filter(Client.id == data.client_id).first()
    if not client:
        raise HTTPException(status_code=404, detail="Cliente no encontrado")

    loan = create_loan_with_schedule(
        db=db,
        client_id=data.client_id,
        cycle_number=data.cycle_number,
        principal_amount=data.principal_amount,
        interest_rate=data.interest_rate,
        payments_count=data.payments_count,
        frequency=data.frequency,  # enum compatible
        start_date=data.start_date,
    )
    return loan


# =========================
# ADMIN: LIST LOANS
# =========================
@router.get("", response_model=list[LoanOut])
def list_loans(
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    return (
        db.query(Loan)
        .order_by(Loan.id.desc())
        .offset(skip)
        .limit(limit)
        .all()
    )


# =========================
# ADMIN: GET LOAN + SCHEDULE
# =========================
@router.get("/{loan_id}", response_model=LoanWithScheduleOut)
def get_loan_with_schedule(
    loan_id: int,
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    loan = db.query(Loan).filter(Loan.id == loan_id).first()
    if not loan:
        raise HTTPException(status_code=404, detail="Préstamo no encontrado")

    schedule = (
        db.query(LoanSchedule)
        .filter(LoanSchedule.loan_id == loan_id)
        .order_by(LoanSchedule.installment_number.asc())
        .all()
    )

    out = LoanWithScheduleOut.model_validate(loan)
    out.schedule = [ScheduleOut.model_validate(s) for s in schedule]
    return out


# =========================
# ADMIN/USER: GET SCHEDULE (USER solo si el cliente es suyo)
# =========================
@router.get("/{loan_id}/schedule", response_model=list[ScheduleOut])
def get_schedule(
    loan_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user),
):
    loan = db.query(Loan).filter(Loan.id == loan_id).first()
    if not loan:
        raise HTTPException(status_code=404, detail="Préstamo no encontrado")

    # ADMIN siempre puede ver
    if current_user.role == UserRole.ADMIN:
        pass
    else:
        # USER solo si el cliente está asignado a él
        allowed = (
            db.query(ClientAssignment)
            .filter(ClientAssignment.client_id == loan.client_id)
            .filter(ClientAssignment.user_id == current_user.id)
            .filter(ClientAssignment.is_active == True)  # noqa
            .first()
        )
        if not allowed:
            raise HTTPException(status_code=403, detail="No tienes acceso a este préstamo")

    schedule = (
        db.query(LoanSchedule)
        .filter(LoanSchedule.loan_id == loan_id)
        .order_by(LoanSchedule.installment_number.asc())
        .all()
    )
    return schedule


# =========================
# USER: MY LOANS (por clientes asignados)
# =========================
@router.get("/my/list", response_model=list[LoanOut])
def my_loans(
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user),
):
    if current_user.role != UserRole.USER:
        raise HTTPException(status_code=403, detail="Solo USER puede acceder a /loans/my/list")

    # loans cuyos clients están asignados a este cobrador
    return (
        db.query(Loan)
        .join(ClientAssignment, ClientAssignment.client_id == Loan.client_id)
        .filter(ClientAssignment.user_id == current_user.id)
        .filter(ClientAssignment.is_active == True)  # noqa
        .order_by(Loan.id.desc())
        .all()
    )
