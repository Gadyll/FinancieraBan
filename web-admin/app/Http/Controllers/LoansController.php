<?php

namespace App\Http\Controllers;

use App\Services\MyBankApi;
use Illuminate\Http\Request;

class LoansController extends Controller
{
    // ─────────────────────────────────────────
    // GET /loans  → lista de préstamos
    // ─────────────────────────────────────────
    public function index(Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $error = null;
        $loans = [];

        $res = $api->listLoans($accessToken, 0, 200);
        if (!$res['ok']) {
            $error = "No se pudieron cargar préstamos: ({$res['status']}) " . json_encode($res['data']);
        } else {
            $loans = is_array($res['data']) ? $res['data'] : [];
        }

        // Traer clientes para mostrar nombre en la tabla
        $clientsRes = $api->clients($accessToken, 0, 500);
        $clientsMap = [];
        if ($clientsRes['ok'] && is_array($clientsRes['data'])) {
            foreach ($clientsRes['data'] as $c) {
                $clientsMap[$c['id']] = $c;
            }
        }

        return view('auth.loans.index', [
            'loans'      => $loans,
            'clientsMap' => $clientsMap,
            'error'      => $error,
        ]);
    }

    // ─────────────────────────────────────────
    // GET /loans/create  → formulario
    // ─────────────────────────────────────────
    public function create(Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        // Traer clientes activos (con préstamo activo o sin préstamo)
        $clientsRes = $api->clients($accessToken, 0, 500);
        $clients = [];
        if ($clientsRes['ok'] && is_array($clientsRes['data'])) {
            $clients = $clientsRes['data'];
        }

        // Pre-seleccionar cliente si viene de la URL
        $selectedClientId = $request->query('client_id');

        return view('auth.loans.create', [
            'clients'          => $clients,
            'selectedClientId' => $selectedClientId,
        ]);
    }

    // ─────────────────────────────────────────
    // POST /loans  → crear préstamo
    // ─────────────────────────────────────────
    public function store(Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $data = $request->validate([
            'client_id'               => ['required', 'integer', 'min:1'],
            'principal_amount'        => ['required', 'numeric', 'min:1'],
            'interest_amount'         => ['required', 'numeric', 'min:0'],
            'payment_amount_override' => ['nullable', 'numeric', 'min:0'],
            'payments_count'          => ['required', 'integer', 'min:4', 'max:104'],
            'frequency'               => ['required', 'in:WEEKLY,BIWEEKLY,MONTHLY'],
            'start_date'              => ['required', 'date'],
        ], [
            'client_id.required'        => 'Selecciona un cliente.',
            'principal_amount.required' => 'El capital prestado es obligatorio.',
            'principal_amount.min'      => 'El capital debe ser mayor a $0.',
            'interest_amount.required'  => 'El monto de interés pactado es obligatorio.',
            'payments_count.required'   => 'El número de pagos es obligatorio.',
            'payments_count.min'        => 'El mínimo es 4 pagos (4 meses mensual / 16 semanas).',
            'frequency.required'        => 'La frecuencia de pago es obligatoria.',
            'start_date.required'       => 'La fecha de entrega del dinero es obligatoria.',
        ]);

        $payload = [
            'client_id'               => (int)$data['client_id'],
            'principal_amount'        => (float)$data['principal_amount'],
            'interest_amount'         => (float)$data['interest_amount'],
            'payments_count'          => (int)$data['payments_count'],
            'frequency'               => $data['frequency'],
            'start_date'              => $data['start_date'],
        ];

        // Cuota manual solo si fue ingresada y es > 0
        if (!empty($data['payment_amount_override']) && (float)$data['payment_amount_override'] > 0) {
            $payload['payment_amount_override'] = (float)$data['payment_amount_override'];
        }

        $res = $api->createLoan($accessToken, $payload);

        if (!$res['ok']) {
            $msg = $res['data']['detail'] ?? json_encode($res['data']);
            // Extraer detalle de validación de Pydantic si existe
            if (isset($res['data']['detail']) && is_array($res['data']['detail'])) {
                $msgs = collect($res['data']['detail'])->pluck('msg')->implode('. ');
                $msg = $msgs ?: $msg;
            }
            return back()->withInput()->withErrors([
                'loan_create' => "No se pudo crear el préstamo: {$msg}",
            ]);
        }

        $loanId = $res['data']['id'] ?? null;
        return redirect()
            ->route('loans.show', ['loanId' => $loanId])
            ->with('success', 'Préstamo creado correctamente con calendario de pagos.');
    }

    // ─────────────────────────────────────────
    // GET /loans/{loanId}  → detalle + calendario
    // ─────────────────────────────────────────
    public function show(int $loanId, Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $error = null;

        // Préstamo completo con schedule incluido
        $loanRes = $api->getLoan($accessToken, $loanId);
        if (!$loanRes['ok']) {
            return redirect()->route('loans.index')
                ->withErrors(['loan' => "Préstamo no encontrado ({$loanRes['status']})."]);
        }
        $loan = $loanRes['data'];

        // Resumen financiero
        $summaryRes = $api->loanSummary($accessToken, $loanId);
        $summary = $summaryRes['ok'] ? $summaryRes['data'] : null;

        // Historial de pagos
        $paymentsRes = $api->listPaymentsByLoan($accessToken, $loanId);
        $payments = ($paymentsRes['ok'] && is_array($paymentsRes['data'])) ? $paymentsRes['data'] : [];

        // Info del cliente
        $clientId = $loan['client_id'] ?? null;
        $client = null;
        if ($clientId) {
            $clientRes = $api->clientDashboard($accessToken, $clientId);
            if ($clientRes['ok']) {
                $client = $clientRes['data']['client'] ?? null;
            }
        }

        // Recargos por mora
        $surchargesRes = $api->listSurcharges($accessToken, $loanId);
        $surcharges = ($surchargesRes['ok'] && is_array($surchargesRes['data'])) ? $surchargesRes['data'] : [];

        return view('auth.loans.show', [
            'loan'       => $loan,
            'summary'    => $summary,
            'payments'   => $payments,
            'client'     => $client,
            'surcharges' => $surcharges,
            'error'      => $error,
        ]);
    }

    // ─────────────────────────────────────────
    // POST /loans/{loanId}/surcharges/{surchargeId}/pay  → liquidar recargo
    // ─────────────────────────────────────────
    public function paySurcharge(int $loanId, int $surchargeId, Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $res = $api->paySurcharge($accessToken, $loanId, $surchargeId);

        if (!$res['ok']) {
            $msg = $res['data']['detail'] ?? json_encode($res['data']);
            return back()->withErrors(['surcharge_pay' => "No se pudo liquidar el recargo: {$msg}"]);
        }

        return redirect()
            ->route('loans.show', ['loanId' => $loanId])
            ->with('success', '✓ Recargo liquidado. El préstamo principal no fue modificado.');
    }

    // ─────────────────────────────────────────
    // POST /loans/{loanId}/surcharge  → autorizar recargo (solo admin)
    // ─────────────────────────────────────────
    public function storeSurcharge(int $loanId, Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'amount.required' => 'El monto del recargo es obligatorio.',
            'amount.min'      => 'El monto debe ser mayor a $0.',
        ]);

        $res = $api->createSurcharge(
            $accessToken,
            $loanId,
            (float) $data['amount'],
            $data['reason'] ?? null
        );

        if (!$res['ok']) {
            $msg = $res['data']['detail'] ?? json_encode($res['data']);
            return back()->withErrors(['surcharge' => "No se pudo autorizar el recargo: {$msg}"]);
        }

        return redirect()
            ->route('loans.show', ['loanId' => $loanId])
            ->with('success', '✓ Recargo por mora autorizado correctamente. El cobrador podrá cobrarlo.');
    }

    // ─────────────────────────────────────────
    // POST /loans/{loanId}/pay  → registrar pago
    // ─────────────────────────────────────────
    public function pay(int $loanId, Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $data = $request->validate([
            'amount_paid'    => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:CASH,TRANSFER,CARD,OTHER'],
            'schedule_id'    => ['nullable', 'integer', 'min:1'],
            'notes'          => ['nullable', 'string', 'max:255'],
        ], [
            'amount_paid.required'    => 'El monto del pago es obligatorio.',
            'amount_paid.min'         => 'El monto debe ser mayor a $0.',
            'payment_method.required' => 'El método de pago es obligatorio.',
        ]);

        $payload = [
            'loan_id'        => $loanId,
            'amount_paid'    => (float)$data['amount_paid'],
            'payment_method' => $data['payment_method'],
            'schedule_id'    => !empty($data['schedule_id']) ? (int)$data['schedule_id'] : null,
            'notes'          => $data['notes'] ?? null,
        ];

        $res = $api->createPayment($accessToken, $payload);

        if (!$res['ok']) {
            $msg = $res['data']['detail'] ?? json_encode($res['data']);
            return back()->withErrors(['payment' => "No se pudo registrar el pago: {$msg}"]);
        }

        $paymentData = $res['data'];
        $ticketNumber = $paymentData['ticket']['ticket_number'] ?? '—';

        // ── Obtener datos extra para el ticket de impresión ──
        $loanRes  = $api->getLoan($accessToken, $loanId);
        $loan     = $loanRes['ok'] ? $loanRes['data'] : [];

        $summaryRes = $api->loanSummary($accessToken, $loanId);
        $summary    = $summaryRes['ok'] ? $summaryRes['data'] : [];

        $clientId  = $loan['client_id'] ?? null;
        $clientName = '—';
        $clientNum  = '—';
        if ($clientId) {
            $clientRes = $api->clientDashboard($accessToken, $clientId);
            if ($clientRes['ok']) {
                $cl = $clientRes['data']['client'] ?? [];
                $clientName = $cl['full_name']     ?? '—';
                $clientNum  = $cl['client_number'] ?? '—';
            }
        }

        // Número de pago: contar pagos anteriores + 1
        $paymentsRes   = $api->listPaymentsByLoan($accessToken, $loanId);
        $paymentsCount = ($paymentsRes['ok'] && is_array($paymentsRes['data']))
            ? count($paymentsRes['data'])
            : 1;

        // Guardar en sesión para la vista de impresión
        session()->flash('ticket_data', [
            'ticket_number'  => $ticketNumber,
            'loan_id'        => $loanId,
            'client_name'    => $clientName,
            'client_number'  => $clientNum,
            'amount_paid'    => (float)$data['amount_paid'],
            'payment_method' => $data['payment_method'],
            'payment_number' => $paymentsCount,
            'payments_total' => $loan['payments_count'] ?? '—',
            'total_amount'   => $loan['total_amount']   ?? 0,
            'remaining'      => $summary['remaining_balance'] ?? 0,
            'paid_at'        => now()->format('d/m/Y H:i'),
            'notes'          => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('loans.show', ['loanId' => $loanId])
            ->with('payment_ok', "Pago registrado. Ticket: {$ticketNumber}");
    }

    // ─────────────────────────────────────────
    // GET /loans/{loanId}/ticket  → ticket imprimible
    // ─────────────────────────────────────────
    public function ticket(int $loanId, Request $request, MyBankApi $api)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login');
        }

        // Datos base del préstamo
        $loanRes = $api->getLoan($accessToken, $loanId);
        if (!$loanRes['ok']) {
            return redirect()->route('loans.show', $loanId);
        }
        $loan = $loanRes['data'];

        $summaryRes = $api->loanSummary($accessToken, $loanId);
        $summary    = $summaryRes['ok'] ? $summaryRes['data'] : [];

        // Info del cliente
        $clientId = $loan['client_id'] ?? null;
        $clientName = '—'; $clientNum = '—';
        if ($clientId) {
            $clientRes = $api->clientDashboard($accessToken, $clientId);
            if ($clientRes['ok']) {
                $cl = $clientRes['data']['client'] ?? [];
                $clientName = $cl['full_name']     ?? '—';
                $clientNum  = $cl['client_number'] ?? '—';
            }
        }

        // Todos los pagos del préstamo
        $paymentsRes = $api->listPaymentsByLoan($accessToken, $loanId);
        $allPayments = ($paymentsRes['ok'] && is_array($paymentsRes['data']))
            ? $paymentsRes['data']
            : [];

        $totalPayments = count($allPayments);

        // ── Si viene payment_id específico, buscar ese pago ──
        $requestedPaymentId = $request->query('payment_id');
        $targetPayment      = null;
        $paymentIndex       = $totalPayments; // posición (1-based)

        if ($requestedPaymentId) {
            foreach ($allPayments as $idx => $p) {
                if ((string)($p['id'] ?? '') === (string)$requestedPaymentId) {
                    $targetPayment = $p;
                    $paymentIndex  = $idx + 1;
                    break;
                }
            }
        }

        // Fallback: si viene de sesión flash (recién pagó) o toma el último
        if (!$targetPayment) {
            $flashData = session('ticket_data');
            if ($flashData) {
                return view('auth.loans.ticket', ['ticket' => $flashData]);
            }
            $targetPayment = count($allPayments) > 0 ? end($allPayments) : [];
            $paymentIndex  = $totalPayments;
        }

        $methodMap = ['CASH'=>'Efectivo','TRANSFER'=>'Transferencia','CARD'=>'Tarjeta','OTHER'=>'Otro'];

        $ticketData = [
            'ticket_number'  => $targetPayment['ticket_number'] ?? '—',
            'loan_id'        => $loanId,
            'client_name'    => $clientName,
            'client_number'  => $clientNum,
            'amount_paid'    => (float)($targetPayment['amount_paid'] ?? 0),
            'payment_method' => $targetPayment['payment_method'] ?? '—',
            'payment_number' => $paymentIndex,
            'payments_total' => $loan['payments_count'] ?? '—',
            'total_amount'   => $loan['total_amount']   ?? 0,
            'remaining'      => $summary['remaining_balance'] ?? 0,
            // Timestamp ISO original (UTC) para que el browser lo convierta a hora local
            'paid_at_iso'    => $targetPayment['paid_at'] ?? null,
            // Formato legible (Carbon lo convierte con la TZ de app.php = America/Mexico_City)
            'paid_at'        => isset($targetPayment['paid_at'])
                ? \Carbon\Carbon::parse($targetPayment['paid_at'])
                    ->setTimezone(config('app.timezone'))
                    ->format('d/m/Y H:i')
                : now()->format('d/m/Y H:i'),
            'notes'          => $targetPayment['notes'] ?? null,
        ];

        return view('auth.loans.ticket', ['ticket' => $ticketData]);
    }
}
