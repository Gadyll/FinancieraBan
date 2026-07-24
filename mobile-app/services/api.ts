import { API_BASE_URL } from "../constants/config";
import AsyncStorage from "@react-native-async-storage/async-storage";

export interface UserMe {
  id: number;
  username: string;
  email: string;
  role: string;
  is_active: boolean;
}

export interface ClientItem {
  id: number;
  client_number: string;
  full_name: string;
  phone: string;
  address: string;
  marital_status: string;
  spouse_full_name?: string;
  birth_date?: string;
  occupation?: string;
  monthly_income?: number;
}

export interface LoanItem {
  id: number;
  client_id: number;
  cycle_number: number;
  principal_amount: number;
  interest_rate: number;
  interest_amount: number;
  total_amount: number;
  payment_amount: number;
  frequency: string;
  payments_count: number;
  start_date: string;
  status: string;
}

export interface LoanSummary {
  loan_id: number;
  client_id: number;
  cycle_number: number;
  status: string;
  frequency: string;
  payments_count: number;
  total_amount: number;
  total_paid: number;
  remaining_balance: number;
  next_installment_number?: number;
  next_due_date?: string;
  next_amount_due?: number;
  next_status?: string;
  overdue_count: number;
}

export interface ClientDashboardLoan {
  loan: LoanItem;
  summary: LoanSummary;
}

export interface ClientDashboardData {
  client: ClientItem;
  loans: ClientDashboardLoan[];
}

export interface PaymentPayload {
  loan_id: number;
  amount_paid: number;
  payment_method: string;
  schedule_id?: number | null;
  notes?: string | null;
}

export interface PaymentResponse {
  id: number;
  loan_id: number;
  amount_paid: number;
  payment_method: string;
  paid_at: string;
  ticket?: {
    ticket_number: string;
  };
}

// Login responses
export interface LoginOtpRequired {
  otp_required: true;
  message: string;
  masked_email: string;
}

export interface LoginTokenResponse {
  otp_required: false;
  access_token: string;
  refresh_token: string;
  token_type: string;
}

// ── Token Storage Helpers ──
const TOKEN_KEY = "@mybank_access_token";
const USER_KEY = "@mybank_user_data";

export async function saveAuthSession(token: string, user: UserMe) {
  await AsyncStorage.setItem(TOKEN_KEY, token);
  await AsyncStorage.setItem(USER_KEY, JSON.stringify(user));
}

export async function getAuthToken(): Promise<string | null> {
  return await AsyncStorage.getItem(TOKEN_KEY);
}

export async function getSavedUser(): Promise<UserMe | null> {
  const data = await AsyncStorage.getItem(USER_KEY);
  return data ? JSON.parse(data) : null;
}

export async function clearAuthSession() {
  await AsyncStorage.removeItem(TOKEN_KEY);
  await AsyncStorage.removeItem(USER_KEY);
}

// ── API Calls ──

/**
 * Paso 1 del login.
 * Retorna OTP requerido si es dispositivo nuevo,
 * o tokens directamente si el dispositivo ya es de confianza.
 */
export async function loginApi(
  username: string,
  password: string,
  deviceId: string
): Promise<LoginOtpRequired | LoginTokenResponse> {
  const response = await fetch(`${API_BASE_URL}/auth/login`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ username, password, device_id: deviceId }),
  });

  const data = await response.json();
  if (!response.ok) {
    throw new Error(
      data.detail || "Credenciales invalidas. Revisa usuario y contrasena."
    );
  }

  return data as LoginOtpRequired | LoginTokenResponse;
}

/**
 * Paso 2: verificar OTP en dispositivo nuevo.
 * Retorna tokens JWT si el codigo es correcto.
 */
export async function verifyOtpApi(
  username: string,
  otpCode: string,
  deviceId: string
): Promise<{ access_token: string; refresh_token: string; token_type: string }> {
  const response = await fetch(`${API_BASE_URL}/auth/verify-otp`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      username,
      otp_code: otpCode,
      device_id: deviceId,
    }),
  });

  const data = await response.json();
  if (!response.ok) {
    throw new Error(data.detail || "Codigo incorrecto o expirado.");
  }

  return data;
}

/**
 * Reenviar OTP cuando expira.
 */
export async function resendOtpApi(
  username: string,
  password: string,
  deviceId: string
): Promise<{ message: string; masked_email: string }> {
  const response = await fetch(`${API_BASE_URL}/auth/resend-otp`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ username, password, device_id: deviceId }),
  });

  const data = await response.json();
  if (!response.ok) {
    throw new Error(data.detail || "No se pudo reenviar el codigo.");
  }

  return data;
}

export async function getMeApi(token: string): Promise<UserMe> {
  const response = await fetch(`${API_BASE_URL}/auth/me`, {
    headers: { Authorization: `Bearer ${token}` },
  });

  const data = await response.json();
  if (!response.ok) {
    throw new Error(data.detail || "Sesion no valida");
  }

  return data as UserMe;
}

export async function getMyClientsApi(token: string): Promise<ClientItem[]> {
  const response = await fetch(`${API_BASE_URL}/clients/my`, {
    headers: { Authorization: `Bearer ${token}` },
  });

  const data = await response.json();
  if (!response.ok) {
    throw new Error(
      data.detail || "No se pudieron cargar los clientes asignados"
    );
  }

  return data as ClientItem[];
}

export async function getClientDashboardApi(
  token: string,
  clientId: number
): Promise<ClientDashboardData> {
  const response = await fetch(
    `${API_BASE_URL}/clients/${clientId}/dashboard`,
    {
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  const data = await response.json();
  if (!response.ok) {
    throw new Error(data.detail || "No se pudo cargar el perfil del cliente");
  }

  return data as ClientDashboardData;
}

export async function createPaymentApi(
  token: string,
  payload: PaymentPayload
): Promise<PaymentResponse> {
  const response = await fetch(`${API_BASE_URL}/payments`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(payload),
  });

  const data = await response.json();
  if (!response.ok) {
    const msg = Array.isArray(data.detail)
      ? data.detail.map((d: any) => d.msg).join(". ")
      : data.detail || "Error al registrar el pago";
    throw new Error(msg);
  }

  return data as PaymentResponse;
}
