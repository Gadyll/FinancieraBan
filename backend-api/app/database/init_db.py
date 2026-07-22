from app.database.base import Base
from app.database.session import engine

from app.models.user import User  # noqa
from app.models.client import Client  # noqa
from app.models.guarantor import Guarantor  # noqa
from app.models.client_assignment import ClientAssignment  # noqa
from app.models.loan import Loan  # noqa
from app.models.loan_schedule import LoanSchedule  # noqa
from app.models.payment import Payment  # noqa
from app.models.ticket import Ticket  # noqa
from app.models.loan_surcharge import LoanSurcharge  # noqa


def init_db() -> None:
    Base.metadata.create_all(bind=engine)