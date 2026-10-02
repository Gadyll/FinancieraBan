<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de auditoría transaccional para FinancieraBan.
     *
     * Registra silenciosamente cada acción (crear, actualizar, eliminar)
     * con el usuario, módulo, descripción y datos afectados.
     *
     * Ejemplo de uso:
     *   AuditLog::record('clients', 'create', "Registró cliente Juan Pérez #C-001", ['client_id' => 5]);
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // ¿Quién lo hizo?
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID del usuario en FastAPI');
            $table->string('username', 80)->nullable()->comment('Username en el momento de la acción');
            $table->string('user_role', 20)->nullable()->comment('Rol del usuario: ADMIN o USER');

            // ¿Qué hizo?
            $table->string('module', 60)->index()->comment('Módulo afectado: clients, loans, payments, users, etc.');
            $table->string('action', 30)->index()->comment('Acción: create, update, delete, assign, pay, etc.');
            $table->text('description')->nullable()->comment('Descripción legible de la acción');

            // ¿Sobre qué registro?
            $table->unsignedBigInteger('subject_id')->nullable()->index()->comment('ID del registro afectado en FastAPI');
            $table->string('subject_type', 60)->nullable()->comment('Tipo: client, loan, payment, user, etc.');

            // Datos adicionales (snapshot JSON)
            $table->json('properties')->nullable()->comment('Datos extra: payload enviado a la API, cambios, etc.');

            // Metadatos de sesión
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
