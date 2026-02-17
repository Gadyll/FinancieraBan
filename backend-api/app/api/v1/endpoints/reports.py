from datetime import date
from fastapi import APIRouter, Depends, Query, HTTPException
from sqlalchemy.orm import Session

from app.database.session import get_db
from app.core.dependencies import require_admin
from app.services.report_service import (
    get_daily_report,
    get_daily_report_by_user,
)

router = APIRouter(prefix="/reports", tags=["reports"])


def _resolve_date(date_param: date | None, d_param: date | None) -> date:
    """
    Permite usar ?date=YYYY-MM-DD (correcto)
    y soporta ?d=YYYY-MM-DD (legacy) para evitar errores 422.
    """
    report_date = date_param or d_param
    if report_date is None:
        raise HTTPException(status_code=422, detail="Falta query param: date")
    return report_date


@router.get("/daily")
def daily_report(
    date: date | None = Query(default=None, description="Fecha (YYYY-MM-DD)"),
    d: date | None = Query(default=None, description="Alias legacy de date"),
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    report_date = _resolve_date(date, d)
    return get_daily_report(db, report_date)


@router.get("/daily/by-user")
def daily_by_user_report(
    date: date | None = Query(default=None, description="Fecha (YYYY-MM-DD)"),
    d: date | None = Query(default=None, description="Alias legacy de date"),
    db: Session = Depends(get_db),
    _admin=Depends(require_admin),
):
    report_date = _resolve_date(date, d)
    return get_daily_report_by_user(db, report_date)
