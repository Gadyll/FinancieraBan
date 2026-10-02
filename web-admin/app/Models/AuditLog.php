<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de auditoría transaccional de FinancieraBan.
 *
 * Registra silenciosamente quién hizo qué, cuándo y sobre qué registro.
 *
 * Uso rápido desde cualquier controller:
 *   AuditLog::record('clients', 'create', 'Registró cliente Juan Pérez', ['client_id' => 5]);
 */
class AuditLog extends Model
{
    const UPDATED_AT = null; // Solo usamos created_at

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'username',
        'user_role',
        'module',
        'action',
        'description',
        'subject_id',
        'subject_type',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Registra una entrada de auditoría de forma silenciosa.
     * No lanza excepciones para no interrumpir el flujo de negocio.
     *
     * @param string      $module      Módulo: 'clients', 'loans', 'payments', 'users'
     * @param string      $action      Acción: 'create', 'update', 'delete', 'assign', 'pay', 'surcharge', 'toggle', 'reset_password'
     * @param string      $description Descripción legible
     * @param array       $properties  Datos adicionales (payload, IDs, etc.)
     * @param int|null    $subjectId   ID del registro afectado
     * @param string|null $subjectType Tipo del registro ('client', 'loan', etc.)
     */
    public static function record(
        string $module,
        string $action,
        string $description,
        array $properties = [],
        ?int $subjectId = null,
        ?string $subjectType = null
    ): void {
        try {
            $sessionUser = session('mybank_user');
            $authUser    = auth()->check() ? auth()->user() : null;

            $userId   = $sessionUser['id'] ?? ($authUser->id ?? null);
            $username = $sessionUser['username'] ?? ($authUser->name ?? $authUser->email ?? 'sistema');
            $userRole = $sessionUser['role'] ?? ($authUser->role ?? (($authUser && ($authUser->role_id ?? null) == 1) ? 'ADMIN' : 'USER'));

            static::create([
                'user_id'      => $userId,
                'username'     => $username,
                'user_role'    => $userRole,
                'module'       => $module,
                'action'       => $action,
                'description'  => $description,
                'subject_id'   => $subjectId,
                'subject_type' => $subjectType,
                'properties'   => $properties ?: null,
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Auditoría nunca debe romper el flujo de negocio
            \Log::warning('AuditLog::record falló: ' . $e->getMessage());
        }
    }

    /**
     * Scope para filtrar por módulo.
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope para filtrar por usuario.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
