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
            'client_id'      => ['required', 'integer', 'min:1'],
            'principal_amount'=> ['required', 'numeric', 'min:1'],
            'interest_rate'  => ['required', 'numeric', 'min:0', 'max:1000'],
            'iva_rate'        => ['required', 'numeric', 'min:0', 'max:100'],
            'payments_count' => ['required', 'integer', 'min:1', 'max:520'],
            'frequency'      => ['required', 'in:WEEKLY,BIWEEKLY,MONTHLY'],
            'start_date'     => ['required', 'date', 'after_or_equal:today'],
        ], [
            'client_id.required'       => 'Selecciona un cliente.',
            'principal_amount.required'=> 'El monto del préstamo es obligatorio.',
            'principal_amount.min'     => 'El monto debe ser mayor a $0.',
            'interest_rate.required'   => 'La tasa de interés es obligatoria.',
            'payments_count.required'  => 'El número de pagos es obligatorio.',
            'frequency.required'       => 'La frecuencia de pago es obligatoria.',
            'start_date.required'      => 'La fecha de inicio es obligatoria.',
            'start_date.after_or_equal'=> 'La fecha de inicio debe ser hoy o posterior.',
        ]);

        $payload = [
            'client_id'       => (int)$data['client_id'],
            'principal_amount' => (float)$data['principal_amount'],
            'interest_rate'   => (float)$data['interest_rate'],
            'iva_rate'        => (float)$data['iva_rate'],
            'payments_count'  => (int)$data['payments_count'],
            'frequency'       => $data['frequency'],
            'start_date'      => $data['start_date'],
        ];

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
            'amount_paid.required' => 'El monto del pago es obligatorio.',
            'amount_paid.min'      => 'El monto debe ser mayor a $0.',
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

        $ticketNumber = $res['data']['ticket']['ticket_number'] ?? '—';
        $pdfUrl = $res['data']['ticket']['pdf_url'] ?? null;

        return redirect()
            ->route('loans.show', ['loanId' => $loanId])
            ->with('payment_ok', "Pago registrado. Ticket: {$ticketNumber}")
            ->with('ticket_pdf', $pdfUrl);
    }
}
