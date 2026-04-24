-- ============================================================
-- MYBANK - Migración: IVA en Loans + Campos extra en Clients
-- Fecha: 2026-04-24
-- Ejecutar en MySQL (phpMyAdmin de Laragon o HeidiSQL)
-- ============================================================

-- 1) Agregar campos IVA a la tabla loans
ALTER TABLE loans
    ADD COLUMN iva_rate   DECIMAL(9,4)  NOT NULL DEFAULT 16.0000  AFTER interest_rate,
    ADD COLUMN iva_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00     AFTER iva_rate;

-- 2) Agregar campos adicionales de perfil a la tabla clients
ALTER TABLE clients
    ADD COLUMN birth_date      DATE           NULL AFTER spouse_full_name,
    ADD COLUMN occupation      VARCHAR(100)   NULL AFTER birth_date,
    ADD COLUMN monthly_income  DECIMAL(12,2)  NULL AFTER occupation;

-- Verificar los cambios:
-- DESCRIBE loans;
-- DESCRIBE clients;
