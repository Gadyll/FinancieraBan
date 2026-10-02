<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Vista del historial de auditoría.
     * Solo accesible para ADMIN.
     */
    public function index(Request $request)
    {
        $accessToken = session('mybank_access_token');
        if (!$accessToken) {
            return redirect()->route('login')->withErrors(['login' => 'Sesión inválida.']);
        }

        $query = AuditLog::query()->orderByDesc('created_at');

        // Filtros opcionales
        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }
        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }
        if ($username = $request->query('username')) {
            $query->where('username', 'like', "%{$username}%");
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(50)->withQueryString();

        $modules = AuditLog::select('module')->distinct()->orderBy('module')->pluck('module');
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('auth.audit.index', [
            'logs'    => $logs,
            'modules' => $modules,
            'actions' => $actions,
        ]);
    }
}
