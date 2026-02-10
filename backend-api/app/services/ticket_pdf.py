import os
from datetime import datetime

from reportlab.lib.units import mm
from reportlab.pdfgen import canvas


def render_ticket_pdf(
    file_path: str,
    ticket_number: str,
    payload: dict,
) -> None:
    """
    PDF 80mm (ancho). Alto "suficiente" fijo por ahora.
    Diseño básico (no final) -> luego lo refinamos.
    """
    width = 80 * mm
    height = 220 * mm  # alto fijo para que no falle; luego lo hacemos dinámico

    os.makedirs(os.path.dirname(file_path), exist_ok=True)

    c = canvas.Canvas(file_path, pagesize=(width, height))

    y = height - 10 * mm
    line = 5 * mm

    def text(s: str, size: int = 9):
        nonlocal y
        c.setFont("Helvetica", size)
        c.drawString(5 * mm, y, s)
        y -= line

    # Header
    text("MYBANK - COMPROBANTE", 10)
    text(f"FOLIO: {ticket_number}", 9)
    text(f"FECHA: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}", 8)
    text("-" * 40, 8)

    # Cliente / Préstamo (info crítica del negocio)
    client = payload.get("client", {})
    loan = payload.get("loan", {})
    text(f"CLIENTE: {client.get('full_name','')}", 9)
    text(f"NO. CLIENTE: {client.get('client_number','')}", 9)
    text(f"CICLO: {loan.get('cycle_number','')}", 9)
    text("-" * 40, 8)

    text(f"MONTO: {loan.get('principal_amount','')}", 9)
    text(f"INTERES: {loan.get('interest_rate','')}%", 9)
    text(f"TOTAL: {loan.get('total_amount','')}", 9)
    text(f"PAGO (CUOTA): {loan.get('payment_amount','')}", 9)
    text(f"FRECUENCIA: {loan.get('frequency','')}", 9)
    text(f"NUM. PAGOS: {loan.get('payments_count','')}", 9)
    text("-" * 40, 8)

    pay = payload.get("payment", {})
    text(f"ABONO: {pay.get('amount_paid','')}", 10)
    text(f"METODO: {pay.get('payment_method','CASH')}", 9)
    text("-" * 40, 8)

    # Detalle de aplicación (sobrante)
    applied = payload.get("applied", [])
    text("APLICADO A:", 9)
    for a in applied[:18]:  # cap simple
        text(
            f"#{a['installment']} {a['due_date']} PAG:{a['paid']} REST:{a['remaining_due']}",
            7,
        )

    text("-" * 40, 8)
    text("GRACIAS POR SU PAGO", 9)

    c.showPage()
    c.save()
