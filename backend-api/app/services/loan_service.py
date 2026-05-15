from datetime import date, timedelta
from decimal import Decimal, ROUND_UP, ROUND_HALF_UP
import math

from dateutil.relativedelta import relativedelta
from sqlalchemy.orm import Session
from sqlalchemy import func

from app.models.loan import Loan, LoanStatus
from app.models.loan_schedule import LoanSchedule


def _money(v: Decimal) -> Decimal:
    return v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)


def _money_up(v: Decimal) -> Decimal:
    """Redondea hacia ARRIBA al peso entero (sin centavos) — para cuotas comerciales."""
    return v.quantize(Decimal("1"), rounding=ROUND_UP)


# ══════════════════════════════════════════════════════════
# NUEVA LÓGICA: Interés fijo pactado (no porcentual)
# ══════════════════════════════════════════════════════════

def calculate_totals_fixed(
    principal: Decimal,
    interest_amount: Decimal,
    payments_count: int,
) -> tuple[Decimal, Decimal]:
    """
    Fórmula financiera FinancieraBan:
        total_programado = capital + interés_pactado
        cuota_base       = total_programado / n_pagos
        cuota_cobro      = redondear_arriba(cuota_base)  ← para números limpios

    Retorna: (total_programado, cuota_cobro)

    Ejemplo: $30,000 + $6,480 / 16 semanas
        total = $36,480
        cuota_base = $2,280.00
        cuota_cobro = $2,280  (sin decimales sucios)

    Si el admin quiere cobrar $2,300 (redondeo manual),
    envía payment_amount_override con ese valor.
    """
    total = _money(Decimal(str(principal)) + Decimal(str(interest_amount)))
    cuota_base = total / Decimal(str(payments_count))
    cuota_cobro = _money_up(cuota_base)
    return total, cuota_cobro


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
    payment_amount: Decimal,
    is_override: bool = False,
) -> list[LoanSchedule]:
    """
    Genera el calendario completo de cuotas.

    - Si is_override=True: TODAS las cuotas son exactamente payment_amount
      (incluyendo la última). El cobrador pactó esa cifra fija.
      Ej: 16 × $2,300 = $36,800 aunque total_programado = $36,480.

    - Si is_override=False: la ÚLTIMA cuota absorbe la diferencia de redondeo
      para que la suma sea exactamente total_amount.
    """
    rows: list[LoanSchedule] = []
    acumulado = Decimal("0.00")

    for n in range(1, payments_count + 1):
        due = next_due_date(start_date, frequency, n)

        if is_override or n < payments_count:
            # Cuota fija (override) O todas menos la última
            amount_due = payment_amount
        else:
            # Última cuota: cierra exactamente el total
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
    interest_amount: Decimal,
    payments_count: int,
    frequency: str,
    start_date: date,
    payment_amount_override: Decimal | None = None,
) -> Loan:
    """
    Crea el préstamo con interés fijo y genera el calendario.

    Fórmula FinancieraBan:
        total    = capital + interés_pactado
        cuota    = ceil(total / n_pagos)   [redondeado hacia arriba]
        override = si el admin quiere cobrar una cuota diferente (ej: $2,300 en vez de $2,280)
    """
    cycle = cycle_number or get_next_cycle_number(db, client_id)

    total_amount, cuota_calculada = calculate_totals_fixed(
        Decimal(str(principal_amount)),
        Decimal(str(interest_amount)),
        payments_count,
    )

    # Si el admin especifica un override (redondeo manual), se usa ese valor
    payment_amount = (
        _money(Decimal(str(payment_amount_override)))
        if payment_amount_override is not None
        else cuota_calculada
    )

    loan = Loan(
        client_id=client_id,
        cycle_number=cycle,
        principal_amount=_money(Decimal(str(principal_amount))),
        interest_rate=Decimal("0.0000"),        # Legacy: mantenemos columna pero ya no se usa
        interest_amount=_money(Decimal(str(interest_amount))),
        iva_rate=Decimal("0.0000"),
        iva_amount=Decimal("0.00"),
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
        payment_amount=payment_amount,
        is_override=(payment_amount_override is not None),
    )

    db.add_all(schedule_rows)
    db.commit()
    db.refresh(loan)
    return loan