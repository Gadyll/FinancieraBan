import sys, os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from datetime import date, datetime, timezone
from decimal import Decimal
from sqlalchemy import text, inspect
from app.database.session import SessionLocal, engine
from app.models.user import User
from app.models.client import Client
from app.models.guarantor import Guarantor
from app.models.client_assignment import ClientAssignment
from app.models.loan import Loan
from app.models.loan_schedule import LoanSchedule
from app.models.payment import Payment
from app.models.ticket import Ticket
from app.models.loan_surcharge import LoanSurcharge

DRY_RUN = "--fix" not in sys.argv
RED="\033[91m"; GREEN="\033[92m"; YELLOW="\033[93m"; BLUE="\033[94m"; RESET="\033[0m"; BOLD="\033[1m"
errors=[]; warnings=[]; fixes=[]
def ok(msg): print(f"  {GREEN}OK{RESET}  {msg}")
def warn(msg): warnings.append(msg); print(f"  {YELLOW}WARN{RESET}  {msg}")
def err(msg): errors.append(msg); print(f"  {RED}ERR{RESET}  {msg}")
def info(msg): print(f"  {BLUE}INF{RESET}  {msg}")
def fix_applied(msg): fixes.append(msg); print(f"  {GREEN}FIX{RESET}  {msg}")
def section(t): print(f"\n{BOLD}{BLUE}== {t} =={RESET}")

section("1. TABLAS EXISTENTES")
inspector = inspect(engine)
existing_tables = set(inspector.get_table_names())
required_tables = {"users","clients","guarantors","client_assignments","loans","loan_schedule","payments","tickets","loan_surcharges"}
missing = required_tables - existing_tables
if missing:
    err(f"Tablas faltantes: {missing}")
else:
    ok(f"Todas las tablas existen")

section("2. COLUMNAS CRITICAS")
def check_col(table, col):
    if table not in existing_tables: return
    cols = {c["name"] for c in inspector.get_columns(table)}
    if col not in cols: err(f"'{table}.{col}' NO existe")
    else: ok(f"'{table}.{col}' existe")

check_col("users","token_version"); check_col("users","user_number")
check_col("loans","interest_amount"); check_col("loans","iva_amount")
check_col("loan_schedule","paid_at"); check_col("payments","schedule_id")
check_col("loan_surcharges","payment_id")

section("3. INDICES IMPORTANTES")
with engine.connect() as conn:
    idx_rows = conn.execute(text("SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, INDEX_NAME")).fetchall()
idx_map = {}
for row in idx_rows:
    tbl, idx_name, col = row
    idx_map.setdefault(tbl, set()).add(col)

important_indexes = [("users","username"),("clients","client_number"),("loans","client_id"),("loan_schedule","loan_id"),("loan_schedule","due_date"),("payments","loan_id"),("payments","user_id"),("client_assignments","client_id"),("client_assignments","user_id")]
for table, col in important_indexes:
    if table in idx_map and col in idx_map[table]: ok(f"Indice en '{table}.{col}'")
    else: warn(f"Falta indice en '{table}.{col}'")

section("4. INTEGRIDAD DE DATOS")
db = SessionLocal()
try:
    today = date.today()
    dupes = db.execute(text("SELECT username, COUNT(*) c FROM users GROUP BY username HAVING c > 1")).fetchall()
    if dupes: err(f"Usernames duplicados: {[r[0] for r in dupes]}")
    else: ok("Sin usernames duplicados")

    dupes_num = db.execute(text("SELECT user_number, COUNT(*) c FROM users GROUP BY user_number HAVING c > 1")).fetchall()
    if dupes_num: err(f"user_number duplicados: {[r[0] for r in dupes_num]}")
    else: ok("Sin user_number duplicados")

    null_cn = db.query(Client).filter(Client.client_number == None).count()
    if null_cn: err(f"{null_cn} clientes sin client_number")
    else: ok("Todos los clientes tienen client_number")

    dup_cn = db.execute(text("SELECT client_number, COUNT(*) c FROM clients GROUP BY client_number HAVING c > 1")).fetchall()
    if dup_cn: err(f"client_number duplicados: {[r[0] for r in dup_cn]}")
    else: ok("Sin client_number duplicados")

    no_guar = db.execute(text("SELECT c.id, c.client_number FROM clients c LEFT JOIN guarantors g ON g.client_id = c.id WHERE g.id IS NULL")).fetchall()
    if no_guar: warn(f"{len(no_guar)} clientes sin aval: {[r[1] for r in no_guar]}")
    else: ok("Todos los clientes tienen aval")

    orphan_loans = db.execute(text("SELECT l.id FROM loans l LEFT JOIN clients c ON c.id = l.client_id WHERE c.id IS NULL")).fetchall()
    if orphan_loans: err(f"{len(orphan_loans)} prestamos huerfanos (sin cliente)")
    else: ok("Sin prestamos huerfanos")

    orphan_sched = db.execute(text("SELECT s.id FROM loan_schedule s LEFT JOIN loans l ON l.id = s.loan_id WHERE l.id IS NULL")).fetchall()
    if orphan_sched: err(f"{len(orphan_sched)} cuotas huerfanas (sin prestamo)")
    else: ok("Sin calendario huerfano")

    orphan_pay = db.execute(text("SELECT p.id FROM payments p LEFT JOIN loans l ON l.id = p.loan_id WHERE l.id IS NULL")).fetchall()
    if orphan_pay: err(f"{len(orphan_pay)} pagos huerfanos (sin prestamo)")
    else: ok("Sin pagos huerfanos")

    dup_sched = db.execute(text("SELECT loan_id, installment_number, COUNT(*) c FROM loan_schedule GROUP BY loan_id, installment_number HAVING c > 1")).fetchall()
    if dup_sched:
        err(f"{len(dup_sched)} duplicados en loan_schedule:")
        for row in dup_sched[:10]: err(f"  loan_id={row[0]} cuota={row[1]} duplicadas={row[2]}")
        if not DRY_RUN:
            for row in dup_sched:
                dupl_ids = db.execute(text("SELECT id FROM loan_schedule WHERE loan_id=:lid AND installment_number=:inst ORDER BY id ASC"), {"lid": row[0], "inst": row[1]}).fetchall()
                del_ids = [r[0] for r in dupl_ids[1:]]
                if del_ids:
                    db.execute(text(f"DELETE FROM loan_schedule WHERE id IN ({','.join(str(d) for d in del_ids)})"))
            db.commit()
            fix_applied(f"Eliminados duplicados en loan_schedule")
    else:
        ok("Sin duplicados en loan_schedule")

    dup_assign = db.execute(text("SELECT user_id, client_id, COUNT(*) c FROM client_assignments WHERE is_active=1 GROUP BY user_id, client_id HAVING c > 1")).fetchall()
    if dup_assign:
        warn(f"{len(dup_assign)} asignaciones activas duplicadas")
        if not DRY_RUN:
            for row in dup_assign:
                ids = db.execute(text("SELECT id FROM client_assignments WHERE user_id=:u AND client_id=:c AND is_active=1 ORDER BY id ASC"), {"u": row[0], "c": row[1]}).fetchall()
                deactivate = [r[0] for r in ids[:-1]]
                if deactivate:
                    db.execute(text(f"UPDATE client_assignments SET is_active=0 WHERE id IN ({','.join(str(d) for d in deactivate)})"))
            db.commit()
            fix_applied("Asignaciones duplicadas desactivadas")
    else:
        ok("Sin asignaciones duplicadas activas")

    section("5. CALENDARIOS")
    active_loans = db.query(Loan).filter(Loan.status == "ACTIVE").all()
    loans_no_sched = [loan.id for loan in active_loans if db.query(LoanSchedule).filter(LoanSchedule.loan_id == loan.id).count() == 0]
    if loans_no_sched: err(f"{len(loans_no_sched)} prestamos ACTIVE sin calendario: {loans_no_sched}")
    else: ok(f"Todos los {len(active_loans)} prestamos activos tienen calendario")

    section("6. CONSISTENCIA DE ESTADOS")
    bad_paid = db.query(LoanSchedule).filter(LoanSchedule.status == "PAID", LoanSchedule.amount_due > 0).count()
    if bad_paid:
        warn(f"{bad_paid} cuotas PAID con amount_due > 0")
        if not DRY_RUN:
            db.execute(text("UPDATE loan_schedule SET status='PARTIAL' WHERE status='PAID' AND amount_due > 0"))
            db.commit()
            fix_applied(f"{bad_paid} cuotas corregidas PAID->PARTIAL")
    else:
        ok("Cuotas PAID correctas (amount_due=0)")

    bad_pending = db.query(LoanSchedule).filter(LoanSchedule.status == "PENDING", LoanSchedule.amount_due == 0).count()
    if bad_pending:
        warn(f"{bad_pending} cuotas PENDING con amount_due=0")
        if not DRY_RUN:
            db.execute(text("UPDATE loan_schedule SET status='PAID', paid_at=NOW() WHERE status='PENDING' AND amount_due = 0"))
            db.commit()
            fix_applied(f"{bad_pending} cuotas corregidas PENDING->PAID")
    else:
        ok("Sin cuotas PENDING con amount_due=0")

    section("7. ESTADOS DE PRESTAMOS")
    loans_should_be_paid = db.execute(text("SELECT l.id FROM loans l WHERE l.status = 'ACTIVE' AND NOT EXISTS (SELECT 1 FROM loan_schedule s WHERE s.loan_id = l.id AND s.status != 'PAID') AND EXISTS (SELECT 1 FROM loan_schedule s2 WHERE s2.loan_id = l.id)")).fetchall()
    if loans_should_be_paid:
        warn(f"{len(loans_should_be_paid)} prestamos ACTIVE con todas cuotas PAID: {[r[0] for r in loans_should_be_paid]}")
        if not DRY_RUN:
            ids = [str(r[0]) for r in loans_should_be_paid]
            db.execute(text(f"UPDATE loans SET status='PAID' WHERE id IN ({','.join(ids)})"))
            db.commit()
            fix_applied(f"{len(loans_should_be_paid)} prestamos actualizados a PAID")
    else:
        ok("Todos los prestamos ACTIVE tienen cuotas pendientes")

    loans_bad_paid = db.execute(text("SELECT l.id FROM loans l WHERE l.status = 'PAID' AND EXISTS (SELECT 1 FROM loan_schedule s WHERE s.loan_id = l.id AND s.status != 'PAID' AND s.amount_due > 0)")).fetchall()
    if loans_bad_paid:
        warn(f"{len(loans_bad_paid)} prestamos PAID con cuotas pendientes: {[r[0] for r in loans_bad_paid]}")
        if not DRY_RUN:
            ids = [str(r[0]) for r in loans_bad_paid]
            db.execute(text(f"UPDATE loans SET status='ACTIVE' WHERE id IN ({','.join(ids)})"))
            db.commit()
            fix_applied(f"{len(loans_bad_paid)} prestamos revertidos a ACTIVE")
    else:
        ok("Prestamos PAID correctos (sin cuotas pendientes)")

    section("8. TICKETS Y PAGOS")
    orphan_tickets = db.execute(text("SELECT t.id FROM tickets t LEFT JOIN payments p ON p.id = t.payment_id WHERE p.id IS NULL")).fetchall()
    if orphan_tickets: warn(f"{len(orphan_tickets)} tickets sin pago asociado")
    else: ok("Todos los tickets tienen pago")

    pays_no_ticket = db.execute(text("SELECT p.id FROM payments p LEFT JOIN tickets t ON t.payment_id = p.id WHERE t.id IS NULL")).fetchall()
    if pays_no_ticket: warn(f"{len(pays_no_ticket)} pagos sin ticket: {[r[0] for r in pays_no_ticket]}")
    else: ok("Todos los pagos tienen ticket")

    section("9. ESTADISTICAS GENERALES")
    info(f"Usuarios:      {db.query(User).count()}")
    info(f"Clientes:      {db.query(Client).count()}")
    info(f"Avales:        {db.query(Guarantor).count()}")
    info(f"Asignaciones:  {db.query(ClientAssignment).count()}")
    info(f"Prestamos:     {db.query(Loan).count()} | ACTIVE:{db.query(Loan).filter(Loan.status=='ACTIVE').count()} PAID:{db.query(Loan).filter(Loan.status=='PAID').count()}")
    info(f"Cuotas:        {db.query(LoanSchedule).count()} | PENDING:{db.query(LoanSchedule).filter(LoanSchedule.status=='PENDING').count()} PAID:{db.query(LoanSchedule).filter(LoanSchedule.status=='PAID').count()} PARTIAL:{db.query(LoanSchedule).filter(LoanSchedule.status=='PARTIAL').count()}")
    info(f"Pagos:         {db.query(Payment).count()}")
    info(f"Tickets:       {db.query(Ticket).count()}")
    info(f"Recargos:      {db.query(LoanSurcharge).count()}")

    section("10. RESUMEN FINAL")
    if errors:
        print(f"\n{RED}{BOLD}ERRORES ({len(errors)}):{RESET}")
        for e in errors: print(f"  * {e}")
    if warnings:
        print(f"\n{YELLOW}{BOLD}ADVERTENCIAS ({len(warnings)}):{RESET}")
        for w in warnings: print(f"  * {w}")
    if fixes:
        print(f"\n{GREEN}{BOLD}CORRECCIONES APLICADAS ({len(fixes)}):{RESET}")
        for f in fixes: print(f"  * {f}")
    if not errors and not warnings:
        print(f"\n{GREEN}{BOLD}BASE DE DATOS LIMPIA Y CONSISTENTE{RESET}")
    elif not errors:
        print(f"\n{YELLOW}{BOLD}Sin errores criticos, pero hay advertencias.{RESET}")
    else:
        print(f"\n{RED}{BOLD}Hay errores. Ejecuta con --fix para corregirlos.{RESET}")
    if DRY_RUN and (errors or warnings):
        print(f"\n  Para corregir: python scripts/db_audit_and_fix.py --fix\n")
finally:
    db.close()
