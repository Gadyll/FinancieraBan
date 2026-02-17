from pydantic import BaseModel
from datetime import date


class DailyReportOut(BaseModel):
    date: date
    total_paid: float
    payments_count: int
    tickets_count: int


class DailyByUserItem(BaseModel):
    user_id: int
    username: str
    total_paid: float
    payments_count: int
    tickets_count: int


class DailyByUserReportOut(BaseModel):
    date: date
    items: list[DailyByUserItem] = []
