from datetime import date, timedelta
from decimal import Decimal, ROUND_HALF_UP

from dateutil.relativedelta import relativedelta
from sqlalchemy.orm import Session
from sqlalchemy import func

from app.models.loan import Loan, LoanFrequency, LoanStatus
from app.models.loan_schedule import LoanSchedule, ScheduleStatus


def _money(v: Decimal) -> Decimal:
    return v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)


def calculate_total(principal: Decimal, interest_rate: Decimal) -> Decimal:
    """
    total = principal + (principal * interest_rate/100)
    """
    total = principal + (principal * (interest_rate / Decimal("100")))
    return _money(total)


def calculate_payment_amount(total: Decimal, payments_count: int) -> Decimal:
    """
    pago por cuota = total / payments_count (2 decimales)
    """
    return _money(total / Decimal(payments_count))


def next_due_date(start: date, frequency: LoanFrequency, installment_number: int) -> date:
    """
    installment_number empieza en 1.
    """
    if frequency == LoanFrequency.WEEKLY:
        return start + timedelta(days=7 * (installment_number - 1))

    if frequency == LoanFrequency.BIWEEKLY:
        return start + timedelta(days=14 * (installment_number - 1))

    # MONTHLY: sumar meses reales (no 30 días)
    return start + relativedelta(months=(installment_number - 1))


def generate_schedule_rows(
    loan_id: int,
    start_date: date,
    frequency: LoanFrequency,
    payments_count: int,
    total_amount: Decimal,
) -> list[LoanSchedule]:
    """
    Genera el calendario completo.
    Ajusta la ÚLTIMA cuota si hay diferencia por redondeo.
    """
    payment_amount = calculate_payment_amount(total_amount, payments_count)

    rows: list[LoanSchedule] = []
    acumulado = Decimal("0.00")

    for n in range(1, payments_count + 1):
        due = next_due_date(start_date, frequency, n)

        amount_due = payment_amount
        if n == payments_count:
            # Ajuste por centavos: última cuota = total - (sum previas)
            amount_due = _money(total_amount - acumulado)

        rows.append(
            LoanSchedule(
                loan_id=loan_id,
                installment_number=n,
                due_date=due,
                amount_due=amount_due,
                status=ScheduleStatus.PENDING,
            )
        )
        acumulado = _money(acumulado + amount_due)

    return rows


def get_next_cycle_number(db: Session, client_id: int) -> int:
    max_cycle = db.query(func.max(Loan.cycle_number)).filter(Loan.client_id == client_id).scalar()
    return int(max_cycle or 0) + 1


def create_loan_with_schedule(
    db: Session,
    client_id: int,
    cycle_number: int | None,
    principal_amount: Decimal,
    interest_rate: Decimal,
    payments_count: int,
    frequency: LoanFrequency,
    start_date: date,
) -> Loan:
    """
    Crea el préstamo y genera el calendario en una sola transacción.
    """
    cycle = cycle_number or get_next_cycle_number(db, client_id)

    total_amount = calculate_total(principal_amount, interest_rate)
    payment_amount = calculate_payment_amount(total_amount, payments_count)

    loan = Loan(
        client_id=client_id,
        cycle_number=cycle,
        principal_amount=_money(principal_amount),
        interest_rate=_money(interest_rate),
        total_amount=total_amount,
        payment_amount=payment_amount,
        frequency=frequency,
        payments_count=payments_count,
        start_date=start_date,
        status=LoanStatus.ACTIVE,
    )

    db.add(loan)
    db.flush()  # obtiene loan.id sin cerrar transacción

    schedule_rows = generate_schedule_rows(
        loan_id=loan.id,
        start_date=start_date,
        frequency=frequency,
        payments_count=payments_count,
        total_amount=total_amount,
    )

    db.add_all(schedule_rows)
    db.commit()
    db.refresh(loan)
    return loan
