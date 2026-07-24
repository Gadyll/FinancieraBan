import React, { useState, useEffect, useCallback } from 'react';
import {
  StyleSheet,
  Text,
  View,
  ScrollView,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
  Modal,
  SafeAreaView,
  StatusBar,
  Alert,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../../context/AuthContext';
import {
  getClientDashboardApi,
  createPaymentApi,
  ClientDashboardData,
  PaymentResponse,
} from '../../services/api';

export default function ClientDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { token } = useAuth();
  const router = useRouter();

  const clientId = parseInt(id || '0', 10);

  const [data, setData] = useState<ClientDashboardData | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [submitting, setSubmitting] = useState<boolean>(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // Form states for payment
  const [amountPaid, setAmountPaid] = useState<string>('');
  const [paymentMethod, setPaymentMethod] = useState<'CASH' | 'TRANSFER'>('CASH');
  const [notes, setNotes] = useState<string>('');

  // Ticket modal state
  const [successTicket, setSuccessTicket] = useState<PaymentResponse | null>(null);

  const loadClientData = useCallback(async () => {
    if (!token || !clientId) return;
    try {
      setErrorMsg(null);
      const res = await getClientDashboardApi(token, clientId);
      setData(res);

      // Pre-llenar monto con la cuota del préstamo activo si existe
      if (res.loans && res.loans.length > 0) {
        const activeLoanItem = res.loans.find(
          (l) => l.loan.status === 'ACTIVE' || l.loan.status === 'LATE'
        );
        if (activeLoanItem) {
          setAmountPaid(activeLoanItem.loan.payment_amount.toString());
        }
      }
    } catch (e: any) {
      setErrorMsg(e.message || 'No se pudo cargar el cliente.');
    } finally {
      setLoading(false);
    }
  }, [token, clientId]);

  useEffect(() => {
    loadClientData();
  }, [loadClientData]);

  const handleRegisterPayment = async () => {
    if (!token || !data) return;

    const activeItem = data.loans.find(
      (l) => l.loan.status === 'ACTIVE' || l.loan.status === 'LATE'
    );

    if (!activeItem) {
      Alert.alert('Atención', 'Este cliente no tiene un préstamo activo para registrar cobros.');
      return;
    }

    const numericAmount = parseFloat(amountPaid);
    if (isNaN(numericAmount) || numericAmount <= 0) {
      Alert.alert('Monto Inválido', 'Ingresa un monto de cobro mayor a $0.');
      return;
    }

    setSubmitting(true);

    try {
      const res = await createPaymentApi(token, {
        loan_id: activeItem.loan.id,
        amount_paid: numericAmount,
        payment_method: paymentMethod,
        schedule_id: activeItem.summary.next_installment_number ? undefined : undefined,
        notes: notes.trim() || undefined,
      });

      setSuccessTicket(res);
      // Recargar datos actualizados
      loadClientData();
    } catch (e: any) {
      Alert.alert('Error en el Cobro', e.message || 'No se pudo registrar el pago.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.centerView}>
        <ActivityIndicator size="large" color="#1a6fcf" />
        <Text style={styles.loadingText}>Cargando información del cliente...</Text>
      </SafeAreaView>
    );
  }

  if (errorMsg || !data) {
    return (
      <SafeAreaView style={styles.centerView}>
        <Ionicons name="alert-circle-outline" size={48} color="#dc2626" />
        <Text style={styles.errorText}>{errorMsg || 'Cliente no encontrado'}</Text>
        <TouchableOpacity style={styles.backBtn} onPress={() => router.back()}>
          <Text style={styles.backBtnText}>Volver a la lista</Text>
        </TouchableOpacity>
      </SafeAreaView>
    );
  }

  const { client, loans } = data;
  const activeItem = loans.find(
    (l) => l.loan.status === 'ACTIVE' || l.loan.status === 'LATE'
  );

  const activeLoan = activeItem?.loan;
  const activeSummary = activeItem?.summary;

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" />

      {/* Navigation Header */}
      <View style={styles.navHeader}>
        <TouchableOpacity onPress={() => router.back()} style={styles.navBackBtn}>
          <Ionicons name="arrow-back" size={24} color="#0f172a" />
        </TouchableOpacity>
        <Text style={styles.navTitle} numberOfLines={1}>
          {client.full_name}
        </Text>
        <View style={{ width: 24 }} />
      </View>

      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView contentContainerStyle={styles.scroll}>
        {/* Client Profile Summary */}
        <View style={styles.profileCard}>
          <View style={styles.profileHeader}>
            <View style={styles.avatarBig}>
              <Text style={styles.avatarBigText}>
                {client.full_name.charAt(0).toUpperCase()}
              </Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.clientName}>{client.full_name}</Text>
              <Text style={styles.clientNum}>Cliente No. {client.client_number}</Text>
              <Text style={styles.clientStatusBadge}>
                {activeLoan ? '● Préstamo Activo' : 'Sin Préstamo Activo'}
              </Text>
            </View>
          </View>

          <View style={styles.divider} />

          <View style={styles.infoRow}>
            <Ionicons name="call" size={16} color="#1a6fcf" />
            <Text style={styles.infoText}>{client.phone}</Text>
          </View>
          <View style={styles.infoRow}>
            <Ionicons name="location" size={16} color="#64748b" />
            <Text style={styles.infoText}>{client.address}</Text>
          </View>
        </View>

        {/* Active Loan Details */}
        {activeLoan && activeSummary ? (
          <View style={styles.loanCard}>
            <View style={styles.loanCardHeader}>
              <Ionicons name="card-outline" size={20} color="#1a6fcf" />
              <Text style={styles.loanCardTitle}>Préstamo Ciclo #{activeLoan.cycle_number}</Text>
            </View>

            <View style={styles.gridStats}>
              <View style={styles.statBox}>
                <Text style={styles.statLabel}>CUOTA EXACTA</Text>
                <Text style={styles.statValBlue}>
                  ${activeLoan.payment_amount.toLocaleString('es-MX')}
                </Text>
                <Text style={styles.statSub}>
                  {activeLoan.frequency === 'WEEKLY' ? 'Semanal' : 'Quincenal'}
                </Text>
              </View>

              <View style={styles.statBox}>
                <Text style={styles.statLabel}>SALDO RESTANTE</Text>
                <Text style={styles.statValDark}>
                  ${activeSummary.remaining_balance.toLocaleString('es-MX')}
                </Text>
                <Text style={styles.statSub}>
                  de ${activeLoan.total_amount.toLocaleString('es-MX')} total
                </Text>
              </View>
            </View>
          </View>
        ) : (
          <View style={styles.noLoanCard}>
            <Ionicons name="alert-circle-outline" size={32} color="#94a3b8" />
            <Text style={styles.noLoanText}>Este cliente no tiene préstamo activo actualmente.</Text>
          </View>
        )}

        {/* Formulario de Cobro */}
        {activeLoan ? (
          <View style={styles.paymentFormCard}>
            <Text style={styles.formTitle}>Registrar Cobro en Campo</Text>
            <Text style={styles.formSub}>
              Ingresa el monto recibido del cliente y confirma.
            </Text>

            {/* Input Monto */}
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>Monto a cobrar ($)</Text>
              <View style={styles.inputWrap}>
                <Text style={styles.currencyPrefix}>$</Text>
                <TextInput
                  style={styles.amountInput}
                  keyboardType="numeric"
                  value={amountPaid}
                  onChangeText={setAmountPaid}
                  placeholder="0.00"
                  placeholderTextColor="#94a3b8"
                />
              </View>
            </View>

            {/* Método de pago */}
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>Método de pago</Text>
              <View style={styles.methodSelector}>
                <TouchableOpacity
                  style={[
                    styles.methodBtn,
                    paymentMethod === 'CASH' && styles.methodBtnSelected,
                  ]}
                  onPress={() => setPaymentMethod('CASH')}
                  activeOpacity={0.8}
                >
                  <Ionicons
                    name="cash-outline"
                    size={18}
                    color={paymentMethod === 'CASH' ? '#ffffff' : '#475569'}
                  />
                  <Text
                    style={[
                      styles.methodText,
                      paymentMethod === 'CASH' && styles.methodTextSelected,
                    ]}
                  >
                    Efectivo
                  </Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[
                    styles.methodBtn,
                    paymentMethod === 'TRANSFER' && styles.methodBtnSelected,
                  ]}
                  onPress={() => setPaymentMethod('TRANSFER')}
                  activeOpacity={0.8}
                >
                  <Ionicons
                    name="card-outline"
                    size={18}
                    color={paymentMethod === 'TRANSFER' ? '#ffffff' : '#475569'}
                  />
                  <Text
                    style={[
                      styles.methodText,
                      paymentMethod === 'TRANSFER' && styles.methodTextSelected,
                    ]}
                  >
                    Transferencia
                  </Text>
                </TouchableOpacity>
              </View>
            </View>

            {/* Notas opcionales */}
            <View style={styles.inputGroup}>
              <Text style={styles.inputLabel}>Notas / Observaciones (opcional)</Text>
              <TextInput
                style={styles.notesInput}
                placeholder="Ej. Pagó completo en domicilio..."
                placeholderTextColor="#94a3b8"
                value={notes}
                onChangeText={setNotes}
                multiline
                numberOfLines={2}
              />
            </View>

            {/* Submit Payment Button */}
            <TouchableOpacity
              style={[styles.submitBtn, submitting && styles.submitBtnDisabled]}
              onPress={handleRegisterPayment}
              disabled={submitting}
              activeOpacity={0.85}
            >
              {submitting ? (
                <ActivityIndicator color="#ffffff" size="small" />
              ) : (
                <>
                  <Ionicons name="checkmark-circle-outline" size={20} color="#ffffff" />
                  <Text style={styles.submitBtnText}>Confirmar y Registrar Pago</Text>
                </>
              )}
            </TouchableOpacity>
          </View>
        ) : null}
        </ScrollView>
      </KeyboardAvoidingView>

      {/* Ticket Success Modal */}
      <Modal
        visible={successTicket !== null}
        transparent
        animationType="slide"
        onRequestClose={() => setSuccessTicket(null)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.ticketCard}>
            <View style={styles.ticketIconBadge}>
              <Ionicons name="checkmark-sharp" size={32} color="#ffffff" />
            </View>

            <Text style={styles.ticketTitle}>¡Pago Registrado!</Text>
            <Text style={styles.ticketSub}>Comprobante digital de cobro en campo</Text>

            <View style={styles.ticketBox}>
              <Text style={styles.ticketFolioLabel}>FOLIO DE TICKET</Text>
              <Text style={styles.ticketFolioVal}>
                {successTicket?.ticket?.ticket_number || 'REGISTRADO'}
              </Text>

              <View style={styles.ticketDivider} />

              <View style={styles.ticketRow}>
                <Text style={styles.ticketKey}>Cliente:</Text>
                <Text style={styles.ticketVal}>{client.full_name}</Text>
              </View>

              <View style={styles.ticketRow}>
                <Text style={styles.ticketKey}>Monto Cobrado:</Text>
                <Text style={styles.ticketValGreen}>
                  ${successTicket?.amount_paid.toLocaleString('es-MX')}
                </Text>
              </View>

              <View style={styles.ticketRow}>
                <Text style={styles.ticketKey}>Método:</Text>
                <Text style={styles.ticketVal}>
                  {successTicket?.payment_method === 'CASH' ? 'Efectivo' : 'Transferencia'}
                </Text>
              </View>
            </View>

            <TouchableOpacity
              style={styles.closeModalBtn}
              onPress={() => setSuccessTicket(null)}
            >
              <Text style={styles.closeModalText}>Aceptar y Continuar</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8fafc',
  },
  centerView: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  loadingText: {
    marginTop: 12,
    color: '#64748b',
    fontWeight: '600',
  },
  errorText: {
    color: '#dc2626',
    fontSize: 16,
    fontWeight: '700',
    marginTop: 12,
    textAlign: 'center',
  },
  backBtn: {
    marginTop: 16,
    backgroundColor: '#1a6fcf',
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 10,
  },
  backBtnText: {
    color: '#ffffff',
    fontWeight: '700',
  },
  navHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#ffffff',
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#e2e8f0',
  },
  navBackBtn: {
    padding: 4,
  },
  navTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: '#0f172a',
  },
  scroll: {
    padding: 16,
  },
  profileCard: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    marginBottom: 14,
  },
  profileHeader: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  avatarBig: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#1a6fcf',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  avatarBigText: {
    color: '#ffffff',
    fontSize: 22,
    fontWeight: '900',
  },
  clientName: {
    fontSize: 17,
    fontWeight: '800',
    color: '#0f172a',
  },
  clientNum: {
    fontSize: 12,
    color: '#64748b',
    marginTop: 1,
  },
  clientStatusBadge: {
    fontSize: 11,
    fontWeight: '700',
    color: '#10b981',
    marginTop: 3,
  },
  divider: {
    height: 1,
    backgroundColor: '#f1f5f9',
    marginVertical: 12,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 6,
  },
  infoText: {
    fontSize: 13,
    color: '#334155',
    fontWeight: '500',
  },
  loanCard: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    marginBottom: 14,
  },
  loanCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 12,
  },
  loanCardTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: '#0f172a',
  },
  gridStats: {
    flexDirection: 'row',
    gap: 10,
  },
  statBox: {
    flex: 1,
    backgroundColor: '#f8fafc',
    borderRadius: 12,
    padding: 12,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  statLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: '#64748b',
    letterSpacing: 0.5,
  },
  statValBlue: {
    fontSize: 18,
    fontWeight: '900',
    color: '#1a6fcf',
    marginVertical: 2,
  },
  statValDark: {
    fontSize: 18,
    fontWeight: '900',
    color: '#0f172a',
    marginVertical: 2,
  },
  statSub: {
    fontSize: 11,
    color: '#64748b',
  },
  noLoanCard: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    marginBottom: 14,
  },
  noLoanText: {
    fontSize: 13,
    color: '#64748b',
    marginTop: 8,
    textAlign: 'center',
  },
  paymentFormCard: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    padding: 18,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  formTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: '#0f172a',
  },
  formSub: {
    fontSize: 12,
    color: '#64748b',
    marginTop: 2,
    marginBottom: 16,
  },
  inputGroup: {
    marginBottom: 14,
  },
  inputLabel: {
    fontSize: 13,
    fontWeight: '700',
    color: '#334155',
    marginBottom: 6,
  },
  inputWrap: {
    position: 'relative',
    justifyContent: 'center',
  },
  currencyPrefix: {
    position: 'absolute',
    left: 14,
    fontSize: 18,
    fontWeight: '800',
    color: '#1a6fcf',
    zIndex: 1,
  },
  amountInput: {
    backgroundColor: '#f8fafc',
    borderWidth: 1.5,
    borderColor: '#cbd5e1',
    borderRadius: 12,
    paddingVertical: 12,
    paddingLeft: 32,
    paddingRight: 16,
    fontSize: 18,
    fontWeight: '800',
    color: '#0f172a',
  },
  methodSelector: {
    flexDirection: 'row',
    gap: 10,
  },
  methodBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#f1f5f9',
    borderWidth: 1.5,
    borderColor: '#cbd5e1',
    borderRadius: 12,
    paddingVertical: 12,
    gap: 6,
  },
  methodBtnSelected: {
    backgroundColor: '#1a6fcf',
    borderColor: '#1a6fcf',
  },
  methodText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#475569',
  },
  methodTextSelected: {
    color: '#ffffff',
  },
  notesInput: {
    backgroundColor: '#f8fafc',
    borderWidth: 1.5,
    borderColor: '#cbd5e1',
    borderRadius: 12,
    padding: 12,
    fontSize: 14,
    color: '#0f172a',
  },
  submitBtn: {
    backgroundColor: '#10b981',
    borderRadius: 14,
    paddingVertical: 14,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 8,
    marginTop: 8,
    shadowColor: '#10b981',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 4,
  },
  submitBtnDisabled: {
    opacity: 0.7,
  },
  submitBtnText: {
    color: '#ffffff',
    fontSize: 16,
    fontWeight: '800',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  ticketCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 24,
    width: '100%',
    maxWidth: 360,
    alignItems: 'center',
  },
  ticketIconBadge: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: '#10b981',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 12,
  },
  ticketTitle: {
    fontSize: 22,
    fontWeight: '900',
    color: '#0f172a',
  },
  ticketSub: {
    fontSize: 12,
    color: '#64748b',
    marginTop: 2,
    marginBottom: 16,
  },
  ticketBox: {
    backgroundColor: '#f8fafc',
    borderWidth: 1.5,
    borderColor: '#e2e8f0',
    borderRadius: 16,
    padding: 16,
    width: '100%',
    marginBottom: 20,
  },
  ticketFolioLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: '#64748b',
    textAlign: 'center',
    letterSpacing: 1,
  },
  ticketFolioVal: {
    fontSize: 18,
    fontWeight: '900',
    color: '#1a6fcf',
    textAlign: 'center',
    marginTop: 2,
  },
  ticketDivider: {
    height: 1,
    backgroundColor: '#cbd5e1',
    marginVertical: 12,
    borderStyle: 'dashed',
  },
  ticketRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 6,
  },
  ticketKey: {
    fontSize: 13,
    color: '#64748b',
    fontWeight: '600',
  },
  ticketVal: {
    fontSize: 13,
    color: '#0f172a',
    fontWeight: '700',
  },
  ticketValGreen: {
    fontSize: 15,
    color: '#10b981',
    fontWeight: '900',
  },
  closeModalBtn: {
    backgroundColor: '#1a6fcf',
    borderRadius: 12,
    paddingVertical: 12,
    width: '100%',
    alignItems: 'center',
  },
  closeModalText: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '800',
  },
});

