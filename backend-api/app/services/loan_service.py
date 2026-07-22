from datetime import date, timedelta
from decimal import Decimal, ROUND_UP, ROUND_HALF_UP
import math

from dateutil.relativedelta import relativedelta
from sqlalchemy.orm import Session
from sqlalchemy import func

from app.models.loan import Loan, LoanStatus
from app.models.loan_schedule import LoanSchedule


# ══════════════════════════════════════════════════════════════
# TABLA DE TASAS OFICIAL
# Tasa de interés (%) → Factor semanal por cada $1,000 prestados
# ══════════════════════════════════════════════════════════════

TABLA_TASAS: dict[str, Decimal] = {
    "3.50": Decimal("71.25"), "3.60": Decimal("71.50"), "3.70": Decimal("71.75"),
    "3.80": Decimal("72.00"), "3.90": Decimal("72.25"),
    "4.00": Decimal("72.50"), "4.10": Decimal("72.75"), "4.20": Decimal("73.00"),
    "4.30": Decimal("73.25"), "4.40": Decimal("73.50"),
    "4.50": Decimal("73.75"), "4.60": Decimal("74.00"), "4.70": Decimal("74.25"),
    "4.80": Decimal("74.50"), "4.90": Decimal("74.75"),
    "5.00": Decimal("75.00"), "5.10": Decimal("75.25"), "5.20": Decimal("75.50"),
    "5.30": Decimal("75.75"), "5.40": Decimal("76.00"),
    "5.50": Decimal("76.25"), "5.60": Decimal("76.50"), "5.70": Decimal("76.75"),
    "5.80": Decimal("77.00"), "5.90": Decimal("77.25"),
    "6.00": Decimal("77.50"), "6.10": Decimal("77.75"), "6.20": Decimal("78.00"),
    "6.30": Decimal("78.25"), "6.40": Decimal("78.50"),
    "6.50": Decimal("78.75"), "6.60": Decimal("79.00"), "6.70": Decimal("79.25"),
    "6.80": Decimal("79.50"), "6.90": Decimal("79.75"),
    "7.00": Decimal("80.00"), "7.10": Decimal("80.25"), "7.20": Decimal("80.50"),
    "7.30": Decimal("80.75"), "7.40": Decimal("81.00"),
    "7.50": Decimal("81.25"),
}

# Tasas válidas como Decimales para validación en schemas
TASAS_VALIDAS: set[Decimal] = {
    Decimal(k) for k in TABLA_TASAS
}


def _money(v: Decimal) -> Decimal:
    return v.quantize(Decimal("0.01"), rounding=ROUND_HALF_UP)


def _money_up(v: Decimal) -> Decimal:
    """Redondea hacia ARRIBA al peso entero (sin centavos) — para cuotas comerciales."""
    return v.quantize(Decimal("1"), rounding=ROUND_UP)


def get_factor_for_rate(interest_rate: Decimal) -> Decimal:
    """
    Busca el factor semanal por cada $1,000 en la tabla oficial.
    Lanza ValueError si la tasa no existe.
    """
    key = str(interest_rate.quantize(Decimal("0.01")))
    factor = TABLA_TASAS.get(key)
    if factor is None:
        raise ValueError(
            f"La tasa {key}% no existe en la tabla oficial autorizada. "
            f"Tasas válidas: {', '.join(sorted(TABLA_TASAS.keys()))}."
        )
    return factor


# ══════════════════════════════════════════════════════════════
# LÓGICA DE CÁLCULO: Tabla de Tasas Oficial
#
# Algoritmo:
#   1. factor_semanal = TABLA_TASAS[tasa_interes]
#   2. pago_base_16   = (capital / 1000) × factor_semanal
#   3. total_congelado = pago_base_16 × 16   ← SIEMPRE anclado a 16 semanas
#   4. interes_total   = total_congelado - capital
#   5. cuota_real      = total_congelado / semanas_solicitadas
#
# Si el cliente pide más de 16 semanas, la cuota se DILUYE
# pero el total congelado NO cambia.
# ══════════════════════════════════════════════════════════════

def calculate_totals_tabla(
    principal: Decimal,
    interest_rate: Decimal,
    payments_count: int,
) -> tuple[Decimal, Decimal, Decimal]:
    """
    Fórmula FinancieraBan con Tabla de Tasas Oficial.

    Retorna: (total_congelado, interes_generado, cuota_cobro)

    Ejemplo: Capital $30,000 · Tasa 5.40% · 16 semanas
        factor      = $76.00/mil
        pago_base   = 30 × 76.00 = $2,280.00
        total       = 2,280 × 16 = $36,480.00
        interés     = 36,480 - 30,000 = $6,480.00
        cuota       = 36,480 / 16 = $2,280.00

    Ejemplo: Capital $30,000 · Tasa 5.40% · 20 semanas
        (mismo total) cuota = 36,480 / 20 = $1,824.00
    """
    p = Decimal(str(principal))
    factor = get_factor_for_rate(interest_rate)

    miles_prestados = p / Decimal("1000")
    pago_base_16 = _money(miles_prestados * factor)
    total_congelado = _money(pago_base_16 * Decimal("16"))
    interes_generado = _money(total_congelado - p)

    cuota_base = total_congelado / Decimal(str(payments_count))
    cuota_cobro = _money(cuota_base)

    return total_congelado, interes_generado, cuota_cobro


def next_due_date(start: date, frequency: str, installment_number: int) -> date:
    if frequency == "WEEKLY":
        return start + timedelta(days=7 * (installment_number - 1))
    if frequency == "BIWEEKLY":
        return start + timedelta(days=14 * (installment_number - 1))
    if frequency == "YEARLY":
        return start + relativedelta(years=(installment_number - 1))
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
    interest_rate: Decimal,
    payments_count: int,
    frequency: str,
    start_date: date,
    payment_amount_override: Decimal | None = None,
) -> Loan:
    """
    Crea el préstamo con Tabla de Tasas Oficial y genera el calendario.

    Fórmula FinancieraBan:
        factor        = TABLA_TASAS[interest_rate]
        pago_base_16  = (capital / 1000) × factor
        total         = pago_base_16 × 16           (siempre anclado a 16 semanas)
        interés       = total - capital
        cuota         = total / n_pagos
        override      = si el admin quiere cobrar una cuota diferente
    """
    cycle = cycle_number or get_next_cycle_number(db, client_id)

    total_amount, interes_generado, cuota_calculada = calculate_totals_tabla(
        Decimal(str(principal_amount)),
        Decimal(str(interest_rate)),
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
        interest_rate=Decimal(str(interest_rate)),     # Tasa % de la tabla oficial
        interest_amount=interes_generado,               # Calculado automáticamente
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