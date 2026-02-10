from pydantic import BaseModel
from app.schemas.client import ClientOut
from app.schemas.loan import LoanOut
from app.schemas.loan_summary import LoanSummaryOut


class ClientLoanDashboardItem(BaseModel):
    loan: LoanOut
    summary: LoanSummaryOut


class ClientDashboardOut(BaseModel):
    client: ClientOut
    loans: list[ClientLoanDashboardItem] = []
