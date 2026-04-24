from datetime import date, timedelta
from decimal import Decimal, ROUND_HALF_UP

from dateutil.relativedelta import relativedelta
from sqlalchemy.orm import Session
from sqlalchemy import func

from app.models.loan import Loan, LoanStatus
from app.models.loan_schedule import LoanSchedule


def _money(v: Decimal) -> Decimal:
    return v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)


def _rate(v: Decimal) -> Decimal:
    return v.quantize(Decimal("0.0001"), rounding=ROUND_HALF_UP)


def calculate_interest(principal: Decimal, interest_rate: Decimal) -> Decimal:
    """Monto del interés = principal * (rate/100)."""
    return _money(principal * (_rate(interest_rate) / Decimal("100")))


def calculate_iva(interest_amount: Decimal, iva_rate: Decimal) -> Decimal:
    """IVA se calcula sobre el INTERÉS, no sobre el capital."""
    return _money(interest_amount * (_rate(iva_rate) / Decimal("100")))


def calculate_total(
    principal: Decimal,
    interest_rate: Decimal,
    iva_rate: Decimal = Decimal("16.0000"),
) -> tuple[Decimal, Decimal, Decimal]:
    """
    Retorna (total_amount, interest_amount, iva_amount).

    Fórmula financiera:
        interest = principal * (interest_rate/100)
        iva      = interest * (iva_rate/100)          ← IVA s/interés
        total    = principal + interest + iva
    """
    interest = calculate_interest(principal, interest_rate)
    iva = calculate_iva(interest, iva_rate)
    total = _money(principal + interest + iva)
    return total, interest, iva


def calculate_payment_amount(total: Decimal, payments_count: int) -> Decimal:
    """Pago por cuota = total / payments_count (2 decimales)."""
    return _money(total / Decimal(payments_count))


def next_due_date(start: date, frequency: str, installment_number: int) -> date:
    if frequency == "WEEKLY":
        return start + timedelta(days=7 * (installment_number - 1))
    if frequency == "BIWEEKLY":
        return start + timedelta(days=14 * (installment_number - 1))
    return start + relativedelta(months=(installment_number - 1))  # MONTHLY


def generate_schedule_rows(
    loan_id: int,
    start_date: date,
    frequency: str,
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
            # Última cuota absorbe diferencia de redondeo
            amount_due = _money(total_amount - acumulado)

        rows.append(
            LoanSchedule(
                loan_id=loan_id,
                installment_number=n,
                due_date=due,
                amount_due=amount_due,
                status="PENDING",
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
    iva_rate: Decimal,
    payments_count: int,
    frequency: str,
    start_date: date,
) -> Loan:
    """
    Crea el préstamo con IVA y genera el calendario en una sola transacción.

    Fórmula:
        interest = principal * (interest_rate/100)
        iva      = interest  * (iva_rate/100)
        total    = principal + interest + iva
        cuota    = total / payments_count
    """
    cycle = cycle_number or get_next_cycle_number(db, client_id)

    total_amount, interest_amount, iva_amount = calculate_total(
        principal_amount, interest_rate, iva_rate
    )
    payment_amount = calculate_payment_amount(total_amount, payments_count)

    loan = Loan(
        client_id=client_id,
        cycle_number=cycle,
        principal_amount=_money(principal_amount),
        interest_rate=_rate(interest_rate),
        iva_rate=_rate(iva_rate),
        iva_amount=iva_amount,
        total_amount=total_amount,
        payment_amount=payment_amount,
        frequency=frequency,
        payments_count=payments_count,
        start_date=start_date,
        status=LoanStatus.ACTIVE,
    )

    db.add(loan)
    db.flush()

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