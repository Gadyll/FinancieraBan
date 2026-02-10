from datetime import date
from decimal import Decimal
from pydantic import BaseModel


class LoanSummaryOut(BaseModel):
    loan_id: int
    client_id: int
    cycle_number: int
    status: str
    frequency: str
    payments_count: int

    total_amount: Decimal
    total_paid: Decimal
    remaining_balance: Decimal

    next_installment_number: int | None
    next_due_date: date | None
    next_amount_due: Decimal | None
    next_status: str | None

    overdue_count: int
