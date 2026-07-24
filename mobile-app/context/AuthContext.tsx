import React, { createContext, useContext, useState, useEffect, useCallback } from "react";
import {
  UserMe,
  loginApi,
  verifyOtpApi,
  getMeApi,
  saveAuthSession,
  getAuthToken,
  getSavedUser,
  clearAuthSession,
  LoginOtpRequired,
} from "../services/api";
import { getOrCreateDeviceId } from "../constants/device";

// Estado que indica que se necesita OTP (dispositivo nuevo)
export interface OtpPendingState {
  username: string;
  password: string;
  deviceId: string;
  maskedEmail: string;
  message: string;
}

interface AuthContextType {
  token: string | null;
  user: UserMe | null;
  isLoading: boolean;
  // Estado OTP pendiente (nulo = no se necesita OTP)
  otpPending: OtpPendingState | null;
  signIn: (username: string, password: string) => Promise<void>;
  completeOtp: (otpCode: string) => Promise<void>;
  cancelOtp: () => void;
  signOut: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType>({
  token: null,
  user: null,
  isLoading: true,
  otpPending: null,
  signIn: async () => {},
  completeOtp: async () => {},
  cancelOtp: () => {},
  signOut: async () => {},
});

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({
  children,
}) => {
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<UserMe | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [otpPending, setOtpPending] = useState<OtpPendingState | null>(null);

  // Restaurar sesion al arrancar la app
  useEffect(() => {
    async function loadSession() {
      try {
        const savedToken = await getAuthToken();
        const savedUser = await getSavedUser();

        if (savedToken && savedUser) {
          setToken(savedToken);
          setUser(savedUser);

          // Verificar en segundo plano si el token sigue siendo valido
          getMeApi(savedToken)
            .then((freshUser) => {
              setUser(freshUser);
              saveAuthSession(savedToken, freshUser);
            })
            .catch(() => {
              // Token expirado o invalido
              clearAuthSession();
              setToken(null);
              setUser(null);
            });
        }
      } catch (e) {
        console.error("Error al restaurar sesion:", e);
      } finally {
        setIsLoading(false);
      }
    }
    loadSession();
  }, []);

  /**
   * Paso 1: Login con usuario + contrasena.
   * - Si el dispositivo ya es de confianza → establece sesion directamente.
   * - Si es nuevo → guarda el estado OTP pendiente para mostrar pantalla verify-otp.
   */
  const signIn = useCallback(async (username: string, password: string) => {
    const deviceId = await getOrCreateDeviceId();
    const result = await loginApi(username.trim(), password, deviceId);

    if (result.otp_required) {
      // Dispositivo nuevo: guardar datos para paso 2
      const otpResult = result as LoginOtpRequired;
      setOtpPending({
        username: username.trim(),
        password,
        deviceId,
        maskedEmail: otpResult.masked_email,
        message: otpResult.message,
      });
      return;
    }

    // Dispositivo de confianza: sesion directa
    const accessToken = result.access_token;
    const userData = await getMeApi(accessToken);
    setToken(accessToken);
    setUser(userData);
    setOtpPending(null);
    await saveAuthSession(accessToken, userData);
  }, []);

  /**
   * Paso 2: Verificar el OTP ingresado por el usuario.
   * Si es correcto, el dispositivo queda registrado como de confianza en el backend.
   */
  const completeOtp = useCallback(
    async (otpCode: string) => {
      if (!otpPending) throw new Error("No hay verificacion OTP pendiente.");

      const { username, deviceId } = otpPending;
      const result = await verifyOtpApi(username, otpCode, deviceId);

      const userData = await getMeApi(result.access_token);
      setToken(result.access_token);
      setUser(userData);
      setOtpPending(null);
      await saveAuthSession(result.access_token, userData);
    },
    [otpPending]
  );

  /** Cancelar verificacion OTP y volver al login */
  const cancelOtp = useCallback(() => {
    setOtpPending(null);
  }, []);

  const signOut = useCallback(async () => {
    await clearAuthSession();
    setToken(null);
    setUser(null);
    setOtpPending(null);
  }, []);

  return (
    <AuthContext.Provider
      value={{ token, user, isLoading, otpPending, signIn, completeOtp, cancelOtp, signOut }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
