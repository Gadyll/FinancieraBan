import os
from fastapi import APIRouter, HTTPException
from fastapi.responses import FileResponse

router = APIRouter(prefix="/tickets")


@router.get("/{ticket_number}/download")
def download_ticket(ticket_number: str):
    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
    base_dir = os.path.dirname(base_dir)  # -> backend-api/
    file_path = os.path.join(base_dir, "storage", "tickets", f"{ticket_number}.pdf")

    if not os.path.exists(file_path):
        raise HTTPException(status_code=404, detail="Ticket PDF no encontrado")

    return FileResponse(
        file_path,
        media_type="application/pdf",
        filename=f"{ticket_number}.pdf",
    )
