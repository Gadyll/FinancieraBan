import React from 'react';
import {
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  SafeAreaView,
  StatusBar,
  ScrollView,
} from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../../context/AuthContext';

export default function HomeScreen() {
  const { user } = useAuth();
  const router = useRouter();

  const todayStr = new Date().toLocaleDateString('es-MX', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0e4fa8" />

      <ScrollView contentContainerStyle={styles.scroll}>
        {/* Header Banner */}
        <View style={styles.heroBanner}>
          <View style={styles.heroHeader}>
            <View>
              <Text style={styles.greetingText}>¡Hola, {user?.username || 'Cobrador'}!</Text>
              <Text style={styles.dateText}>{todayStr}</Text>
            </View>
            <View style={styles.avatarMini}>
              <Text style={styles.avatarMiniText}>
                {user?.username ? user.username.charAt(0).toUpperCase() : 'C'}
              </Text>
            </View>
          </View>

          <View style={styles.statusBadge}>
            <View style={styles.statusDot} />
            <Text style={styles.statusText}>Sesión Activa · Cobrador de Campo</Text>
          </View>
        </View>

        {/* Main Card Section */}
        <View style={styles.content}>
          <Text style={styles.sectionTitle}>Operaciones de Campo</Text>

          <TouchableOpacity
            style={styles.mainActionCard}
            onPress={() => router.push('/(tabs)/clients' as any)}
            activeOpacity={0.85}
          >
            <View style={styles.iconCircle}>
              <Ionicons name="people-sharp" size={28} color="#ffffff" />
            </View>
            <View style={styles.actionCardBody}>
              <Text style={styles.actionTitle}>Mis Clientes Asignados</Text>
              <Text style={styles.actionSub}>
                Revisa el estado de la cartera, saldos y registra cobros con ticket.
              </Text>
            </View>
            <Ionicons name="arrow-forward-circle" size={24} color="#1a6fcf" />
          </TouchableOpacity>

          {/* Info Card */}
          <View style={styles.infoCard}>
            <Ionicons name="information-circle-outline" size={22} color="#1a6fcf" />
            <Text style={styles.infoText}>
              Todos los cobros realizados desde esta app se reflejan inmediatamente en el panel administrativo web.
            </Text>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8fafc',
  },
  scroll: {
    flexGrow: 1,
  },
  heroBanner: {
    backgroundColor: '#1a6fcf',
    paddingHorizontal: 20,
    paddingTop: 24,
    paddingBottom: 28,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
  },
  heroHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  greetingText: {
    fontSize: 24,
    fontWeight: '900',
    color: '#ffffff',
  },
  dateText: {
    fontSize: 13,
    color: '#bfdbfe',
    marginTop: 2,
    textTransform: 'capitalize',
  },
  avatarMini: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#ffffff',
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarMiniText: {
    fontSize: 18,
    fontWeight: '900',
    color: '#1a6fcf',
  },
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.15)',
    alignSelf: 'flex-start',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 999,
    marginTop: 16,
    gap: 6,
  },
  statusDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#10b981',
  },
  statusText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '600',
  },
  content: {
    padding: 20,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0f172a',
    marginBottom: 12,
  },
  mainActionCard: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    padding: 20,
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 3,
    marginBottom: 16,
  },
  iconCircle: {
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: '#1a6fcf',
    justifyContent: 'center',
    alignItems: 'center',
  },
  actionCardBody: {
    flex: 1,
    marginLeft: 14,
    marginRight: 8,
  },
  actionTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#0f172a',
  },
  actionSub: {
    fontSize: 12,
    color: '#64748b',
    marginTop: 3,
    lineHeight: 16,
  },
  infoCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    borderWidth: 1,
    borderColor: '#dbeafe',
    padding: 14,
    borderRadius: 14,
    gap: 10,
  },
  infoText: {
    fontSize: 12,
    color: '#1e40af',
    fontWeight: '500',
    flex: 1,
    lineHeight: 17,
  },
});
