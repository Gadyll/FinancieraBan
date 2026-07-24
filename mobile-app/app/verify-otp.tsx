import React, { useState, useEffect, useRef, useCallback } from "react";
import {
  StyleSheet,
  Text,
  View,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StatusBar,
  NativeSyntheticEvent,
  TextInputKeyPressEventData,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { useAuth } from "../context/AuthContext";
import { resendOtpApi } from "../services/api";

const OTP_LENGTH = 6;
const EXPIRE_SECONDS = 5 * 60; // 5 minutos

export default function VerifyOtpScreen() {
  const { otpPending, completeOtp, cancelOtp } = useAuth();

  const [digits, setDigits] = useState<string[]>(Array(OTP_LENGTH).fill(""));
  const [loading, setLoading] = useState(false);
  const [resendLoading, setResendLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [secondsLeft, setSecondsLeft] = useState(EXPIRE_SECONDS);

  const inputRefs = useRef<Array<TextInput | null>>(Array(OTP_LENGTH).fill(null));

  // Cuenta regresiva
  useEffect(() => {
    if (secondsLeft <= 0) return;
    const interval = setInterval(() => {
      setSecondsLeft((s) => Math.max(0, s - 1));
    }, 1000);
    return () => clearInterval(interval);
  }, [secondsLeft]);

  const formatTime = (secs: number): string => {
    const m = Math.floor(secs / 60);
    const s = secs % 60;
    return `${m}:${s.toString().padStart(2, "0")}`;
  };

  const otpCode = digits.join("");
  const isComplete = otpCode.length === OTP_LENGTH;
  const isExpired = secondsLeft === 0;

  const handleDigitChange = (index: number, value: string) => {
    const digit = value.replace(/[^0-9]/g, "").slice(-1);
    const newDigits = [...digits];
    newDigits[index] = digit;
    setDigits(newDigits);
    setError(null);

    // Auto-advance al siguiente campo
    if (digit && index < OTP_LENGTH - 1) {
      inputRefs.current[index + 1]?.focus();
    }
  };

  const handleKeyPress = (
    index: number,
    e: NativeSyntheticEvent<TextInputKeyPressEventData>
  ) => {
    if (e.nativeEvent.key === "Backspace" && !digits[index] && index > 0) {
      // Retroceder al campo anterior si el actual esta vacio
      const newDigits = [...digits];
      newDigits[index - 1] = "";
      setDigits(newDigits);
      inputRefs.current[index - 1]?.focus();
    }
  };

  const handleVerify = async () => {
    if (!isComplete || loading) return;
    if (isExpired) {
      setError("El codigo ha expirado. Solicita uno nuevo.");
      return;
    }

    setLoading(true);
    setError(null);

    try {
      await completeOtp(otpCode);
      // AuthContext manejara la navegacion al cambiar token
    } catch (err: any) {
      setError(err.message || "Codigo incorrecto. Intenta de nuevo.");
      // Limpiar digitos al error
      setDigits(Array(OTP_LENGTH).fill(""));
      inputRefs.current[0]?.focus();
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    if (!otpPending || resendLoading) return;

    setResendLoading(true);
    setError(null);
    setSuccess(null);

    try {
      await resendOtpApi(otpPending.username, otpPending.password, otpPending.deviceId);
      setDigits(Array(OTP_LENGTH).fill(""));
      setSecondsLeft(EXPIRE_SECONDS);
      setSuccess("Se envio un nuevo codigo a tu correo.");
      inputRefs.current[0]?.focus();
    } catch (err: any) {
      setError(err.message || "No se pudo reenviar el codigo.");
    } finally {
      setResendLoading(false);
    }
  };

  if (!otpPending) return null;

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === "ios" ? "padding" : "height"}
    >
      <StatusBar barStyle="light-content" backgroundColor="#0e4fa8" />
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
      >
        {/* Header */}
        <View style={styles.heroBanner}>
          <View style={styles.logoBadge}>
            <Ionicons name="shield-checkmark" size={36} color="#ffffff" />
          </View>
          <Text style={styles.brandTitle}>MYBANK</Text>
          <Text style={styles.brandSubtitle}>Verificacion de Dispositivo</Text>
        </View>

        {/* Card */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>Codigo de verificacion</Text>
          <Text style={styles.cardSub}>
            Enviamos un codigo de 6 digitos a{"\n"}
            <Text style={styles.emailHighlight}>{otpPending.maskedEmail}</Text>
          </Text>

          {/* Error */}
          {error ? (
            <View style={styles.alertBox}>
              <Ionicons name="alert-circle" size={18} color="#dc2626" />
              <Text style={styles.alertText}>{error}</Text>
            </View>
          ) : null}

          {/* Success */}
          {success ? (
            <View style={[styles.alertBox, styles.successBox]}>
              <Ionicons name="checkmark-circle" size={18} color="#059669" />
              <Text style={[styles.alertText, styles.successText]}>{success}</Text>
            </View>
          ) : null}

          {/* OTP Inputs */}
          <View style={styles.otpRow}>
            {Array(OTP_LENGTH)
              .fill(null)
              .map((_, i) => (
                <TextInput
                  key={i}
                  ref={(ref) => { inputRefs.current[i] = ref; }}
                  style={[
                    styles.otpInput,
                    digits[i] ? styles.otpInputFilled : null,
                    error ? styles.otpInputError : null,
                  ]}
                  value={digits[i]}
                  onChangeText={(val) => handleDigitChange(i, val)}
                  onKeyPress={(e) => handleKeyPress(i, e)}
                  keyboardType="number-pad"
                  maxLength={1}
                  selectTextOnFocus
                  autoFocus={i === 0}
                />
              ))}
          </View>

          {/* Timer */}
          <View style={styles.timerRow}>
            <Ionicons
              name="time-outline"
              size={15}
              color={isExpired ? "#dc2626" : "#64748b"}
            />
            <Text
              style={[styles.timerText, isExpired && styles.timerExpired]}
            >
              {isExpired ? "Codigo expirado" : `Expira en ${formatTime(secondsLeft)}`}
            </Text>
          </View>

          {/* Verify Button */}
          <TouchableOpacity
            style={[
              styles.verifyBtn,
              (!isComplete || loading || isExpired) && styles.btnDisabled,
            ]}
            onPress={handleVerify}
            disabled={!isComplete || loading || isExpired}
            activeOpacity={0.85}
          >
            {loading ? (
              <ActivityIndicator color="#ffffff" size="small" />
            ) : (
              <>
                <Ionicons name="checkmark-circle" size={18} color="#ffffff" />
                <Text style={styles.verifyBtnText}>Verificar codigo</Text>
              </>
            )}
          </TouchableOpacity>

          {/* Resend */}
          <TouchableOpacity
            style={[styles.resendBtn, !isExpired && !resendLoading && styles.resendBtnDisabled]}
            onPress={handleResend}
            disabled={!isExpired || resendLoading}
            activeOpacity={0.75}
          >
            {resendLoading ? (
              <ActivityIndicator size="small" color="#1a6fcf" />
            ) : (
              <>
                <Ionicons
                  name="refresh"
                  size={15}
                  color={isExpired ? "#1a6fcf" : "#94a3b8"}
                />
                <Text
                  style={[
                    styles.resendText,
                    !isExpired && styles.resendTextDisabled,
                  ]}
                >
                  Reenviar codigo
                </Text>
              </>
            )}
          </TouchableOpacity>

          {/* Separator */}
          <View style={styles.separator} />

          {/* Back to login */}
          <TouchableOpacity
            style={styles.backBtn}
            onPress={cancelOtp}
            activeOpacity={0.7}
          >
            <Ionicons name="arrow-back" size={15} color="#64748b" />
            <Text style={styles.backText}>Volver al inicio de sesion</Text>
          </TouchableOpacity>
        </View>

        {/* Note */}
        <View style={styles.noteRow}>
          <Ionicons name="information-circle-outline" size={14} color="#94a3b8" />
          <Text style={styles.noteText}>
            Solo se pide este codigo la primera vez en cada dispositivo.
          </Text>
        </View>

        <Text style={styles.footerCopy}>c 2026 FinancieraBan</Text>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#f1f5f9",
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: "center",
    padding: 20,
  },
  heroBanner: {
    alignItems: "center",
    marginBottom: 24,
  },
  logoBadge: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: "#0d9488",
    justifyContent: "center",
    alignItems: "center",
    shadowColor: "#0d9488",
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.3,
    shadowRadius: 10,
    elevation: 8,
    marginBottom: 12,
  },
  brandTitle: {
    fontSize: 26,
    fontWeight: "900",
    color: "#0f172a",
    letterSpacing: 1.5,
  },
  brandSubtitle: {
    fontSize: 13,
    color: "#64748b",
    fontWeight: "600",
    marginTop: 2,
  },
  card: {
    backgroundColor: "#ffffff",
    borderRadius: 20,
    padding: 24,
    shadowColor: "#0f172a",
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.08,
    shadowRadius: 20,
    elevation: 4,
    borderWidth: 1,
    borderColor: "#e2e8f0",
  },
  cardTitle: {
    fontSize: 20,
    fontWeight: "800",
    color: "#0f172a",
    marginBottom: 6,
  },
  cardSub: {
    fontSize: 13,
    color: "#64748b",
    marginBottom: 20,
    lineHeight: 20,
  },
  emailHighlight: {
    color: "#0e4fa8",
    fontWeight: "700",
  },
  alertBox: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#fef2f2",
    borderWidth: 1,
    borderColor: "#fecaca",
    padding: 12,
    borderRadius: 10,
    marginBottom: 16,
    gap: 8,
  },
  successBox: {
    backgroundColor: "#f0fdf4",
    borderColor: "#86efac",
  },
  alertText: {
    color: "#dc2626",
    fontSize: 13,
    fontWeight: "600",
    flex: 1,
  },
  successText: {
    color: "#059669",
  },
  otpRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    gap: 8,
    marginBottom: 16,
  },
  otpInput: {
    flex: 1,
    height: 54,
    borderWidth: 2,
    borderColor: "#cbd5e1",
    borderRadius: 12,
    textAlign: "center",
    fontSize: 24,
    fontWeight: "800",
    color: "#0f172a",
    backgroundColor: "#f8fafc",
  },
  otpInputFilled: {
    borderColor: "#1a6fcf",
    backgroundColor: "#eff6ff",
    color: "#0e4fa8",
  },
  otpInputError: {
    borderColor: "#ef4444",
    backgroundColor: "#fef2f2",
  },
  timerRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 5,
    marginBottom: 20,
  },
  timerText: {
    fontSize: 13,
    color: "#64748b",
    fontWeight: "600",
  },
  timerExpired: {
    color: "#dc2626",
    fontWeight: "700",
  },
  verifyBtn: {
    backgroundColor: "#1a6fcf",
    borderRadius: 14,
    paddingVertical: 14,
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    gap: 8,
    marginBottom: 12,
    shadowColor: "#1a6fcf",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 4,
  },
  btnDisabled: {
    opacity: 0.45,
    elevation: 0,
    shadowOpacity: 0,
  },
  verifyBtnText: {
    color: "#ffffff",
    fontSize: 15,
    fontWeight: "800",
  },
  resendBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
    paddingVertical: 10,
  },
  resendBtnDisabled: {
    opacity: 0.45,
  },
  resendText: {
    fontSize: 14,
    color: "#1a6fcf",
    fontWeight: "600",
  },
  resendTextDisabled: {
    color: "#94a3b8",
  },
  separator: {
    height: 1,
    backgroundColor: "#e2e8f0",
    marginVertical: 16,
  },
  backBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
    paddingVertical: 6,
  },
  backText: {
    fontSize: 13,
    color: "#64748b",
    fontWeight: "500",
  },
  noteRow: {
    flexDirection: "row",
    alignItems: "flex-start",
    gap: 6,
    marginTop: 20,
    paddingHorizontal: 4,
  },
  noteText: {
    fontSize: 12,
    color: "#94a3b8",
    flex: 1,
    lineHeight: 18,
  },
  footerCopy: {
    textAlign: "center",
    color: "#94a3b8",
    fontSize: 12,
    marginTop: 16,
  },
});
