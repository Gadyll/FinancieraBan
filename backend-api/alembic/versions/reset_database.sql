-- ============================================================
-- MYBANK — RESET COMPLETO DE BASE DE DATOS
-- FinancieraBan · 2026
--
-- ⚠️ ESTE SCRIPT BORRA TODOS LOS DATOS.
--    Solo úsalo en desarrollo o para empezar de cero.
--
-- Ejecutar en: phpMyAdmin → base "mybank" → SQL
-- O desde terminal: mysql -u root mybank < reset_database.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Eliminar todas las tablas ──────────────────────────────
DROP TABLE IF EXISTS loan_surcharges;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS loan_schedule;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS client_assignments;
DROP TABLE IF EXISTS guarantors;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS alembic_version;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Eliminar ENUMs (MySQL los guarda como tipos) ───────────
-- En MySQL los ENUMs son inline, no hay que eliminarlos por separado.

-- ── RECREAR TABLAS ─────────────────────────────────────────

-- 1) users
CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)  NOT NULL,
    email         VARCHAR(120) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('ADMIN','USER') NOT NULL DEFAULT 'USER',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    user_number   INT UNSIGNED NOT NULL UNIQUE,
    created_at    DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at    DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY ix_users_username (username),
    UNIQUE KEY ix_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) clients
CREATE TABLE clients (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_number   VARCHAR(30)  NOT NULL,
    full_name       VARCHAR(150) NOT NULL,
    phone           VARCHAR(25)  NULL,
    address         VARCHAR(255) NULL,
    marital_status  VARCHAR(30)  NULL,
    spouse_full_name VARCHAR(150) NULL,
    -- Campos de perfil
    birth_date      DATE         NULL,
    occupation      VARCHAR(100) NULL,
    monthly_income  DECIMAL(12,2) NULL,
    created_at      DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at      DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY ix_clients_client_number (client_number),
    KEY ix_clients_full_name (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) guarantors
CREATE TABLE guarantors (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_id        INT UNSIGNED NOT NULL,
    full_name        VARCHAR(150) NOT NULL,
    phone            VARCHAR(25)  NULL,
    address          VARCHAR(255) NULL,
    marital_status   VARCHAR(30)  NULL,
    created_at       DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at       DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY ix_guarantors_client_id (client_id),
    CONSTRAINT fk_guarantors_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) client_assignments
CREATE TABLE client_assignments (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    client_id   INT UNSIGNED NOT NULL,
    assigned_at DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_client_assignment_user_client (user_id, client_id),
    KEY ix_client_assignments_client_id (client_id),
    KEY ix_client_assignments_user_id (user_id),
    CONSTRAINT fk_ca_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE RESTRICT,
    CONSTRAINT fk_ca_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) loans
CREATE TABLE loans (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_id        INT UNSIGNED NOT NULL,
    cycle_number     INT          NOT NULL DEFAULT 1,
    principal_amount DECIMAL(12,2) NOT NULL,
    interest_rate    DECIMAL(5,2)  NOT NULL,
    iva_rate         DECIMAL(9,4)  NOT NULL DEFAULT 16.0000,
    iva_amount       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount     DECIMAL(12,2) NOT NULL,
    payment_amount   DECIMAL(12,2) NOT NULL,
    frequency        ENUM('WEEKLY','BIWEEKLY','MONTHLY') NOT NULL DEFAULT 'WEEKLY',
    payments_count   INT           NOT NULL,
    start_date       DATE          NOT NULL,
    status           ENUM('ACTIVE','PAID','LATE','CANCELED') NOT NULL DEFAULT 'ACTIVE',
    created_at       DATETIME(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at       DATETIME(6)   NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY ix_loans_client_id (client_id),
    KEY ix_loans_cycle_number (cycle_number),
    CONSTRAINT fk_loans_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) loan_schedule
CREATE TABLE loan_schedule (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    loan_id             INT UNSIGNED NOT NULL,
    installment_number  INT          NOT NULL,
    due_date            DATE         NOT NULL,
    amount_due          DECIMAL(12,2) NOT NULL,
    status              ENUM('PENDING','PAID','PARTIAL','LATE') NOT NULL DEFAULT 'PENDING',
    paid_at             DATETIME(6)  NULL,
    created_at          DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at          DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_schedule_loan_installment (loan_id, installment_number),
    KEY ix_loan_schedule_loan_id (loan_id),
    KEY ix_loan_schedule_due_date (due_date),
    CONSTRAINT fk_schedule_loan FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7) payments
CREATE TABLE payments (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    loan_id        INT UNSIGNED NOT NULL,
    schedule_id    INT UNSIGNED NULL,
    user_id        INT UNSIGNED NOT NULL,
    amount_paid    DECIMAL(12,2) NOT NULL,
    payment_method ENUM('CASH','TRANSFER','CARD','OTHER') NOT NULL DEFAULT 'CASH',
    notes          VARCHAR(255) NULL,
    paid_at        DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    created_at     DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY ix_payments_loan_id (loan_id),
    KEY ix_payments_paid_at (paid_at),
    KEY ix_payments_schedule_id (schedule_id),
    KEY ix_payments_user_id (user_id),
    CONSTRAINT fk_payments_loan     FOREIGN KEY (loan_id)     REFERENCES loans(id)          ON DELETE RESTRICT,
    CONSTRAINT fk_payments_schedule FOREIGN KEY (schedule_id) REFERENCES loan_schedule(id)  ON DELETE SET NULL,
    CONSTRAINT fk_payments_user     FOREIGN KEY (user_id)     REFERENCES users(id)           ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8) tickets
CREATE TABLE tickets (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_number  VARCHAR(50)  NOT NULL,
    payment_id     INT UNSIGNED NOT NULL,
    generated_by   ENUM('ADMIN','USER') NOT NULL DEFAULT 'USER',
    pdf_path       VARCHAR(255) NULL,
    created_at     DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_ticket_number (ticket_number),
    UNIQUE KEY uq_ticket_payment_id (payment_id),
    KEY ix_tickets_payment_id (payment_id),
    KEY ix_tickets_ticket_number (ticket_number),
    CONSTRAINT fk_tickets_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9) loan_surcharges (NUEVO — recargos por mora autorizados por admin)
CREATE TABLE loan_surcharges (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    loan_id        INT UNSIGNED NOT NULL,
    authorized_by  INT UNSIGNED NOT NULL,
    amount         DECIMAL(12,2) NOT NULL,
    reason         VARCHAR(255) NULL,
    status         VARCHAR(20)  NOT NULL DEFAULT 'PENDING',
    payment_id     INT UNSIGNED NULL,
    created_at     DATETIME(6)  NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY ix_loan_surcharges_loan_id (loan_id),
    CONSTRAINT fk_surcharge_loan    FOREIGN KEY (loan_id)       REFERENCES loans(id)    ON DELETE CASCADE,
    CONSTRAINT fk_surcharge_user    FOREIGN KEY (authorized_by) REFERENCES users(id)    ON DELETE RESTRICT,
    CONSTRAINT fk_surcharge_payment FOREIGN KEY (payment_id)    REFERENCES payments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10) alembic_version (para que Alembic sepa el estado de migraciones)
CREATE TABLE alembic_version (
    version_num VARCHAR(32) NOT NULL,
    PRIMARY KEY (version_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Marca la última migración como aplicada
INSERT INTO alembic_version (version_num) VALUES ('b2c4d1e9f8a7');

-- ── SEED: Usuario administrador inicial ────────────────────
-- Ejecuta scripts/create_admin.py despues del reset para crear el hash real.
-- O usa este INSERT de emergencia solo para acceso inicial:
-- Contrasena: Admin123!
INSERT INTO users (user_number, username, email, password_hash, role, is_active)
VALUES (
    1,
    'admin',
    'admin@mybank.local',
    '$2b$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMeSSm.BtlNS7sAnKuPdT.5rB2',
    'ADMIN',
    1
);

-- ============================================================
-- ✅ BASE DE DATOS LISTA
-- Próximo cliente: Cliente00001
-- Admin: usuario="admin" / contraseña="Admin123!"
-- ============================================================
