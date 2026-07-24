import React from 'react';
import {
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  SafeAreaView,
  StatusBar,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../../context/AuthContext';

export default function ProfileTab() {
  const { user, signOut } = useAuth();

  const handleLogout = () => {
    Alert.alert(
      'Cerrar sesión',
      '¿Estás seguro de que deseas salir de la aplicación?',
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Sí, salir',
          style: 'destructive',
          onPress: () => signOut(),
        },
      ]
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" />

      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Mi Perfil</Text>
      </View>

      {/* User Card */}
      <View style={styles.content}>
        <View style={styles.userHeroCard}>
          <View style={styles.avatarBig}>
            <Text style={styles.avatarBigText}>
              {user?.username ? user.username.charAt(0).toUpperCase() : 'C'}
            </Text>
          </View>
          <Text style={styles.usernameText}>{user?.username || 'Cobrador'}</Text>
          <Text style={styles.emailText}>{user?.email || '—'}</Text>

          <View style={styles.roleChip}>
            <Ionicons name="person-circle-outline" size={14} color="#1a6fcf" />
            <Text style={styles.roleChipText}>
              Rol: {user?.role === 'USER' ? 'Cobrador de Campo' : user?.role || '—'}
            </Text>
          </View>
        </View>

        {/* Info Rows */}
        <View style={styles.infoCard}>
          <View style={styles.rowItem}>
            <Ionicons name="checkmark-circle" size={20} color="#10b981" />
            <View style={styles.rowTextWrap}>
              <Text style={styles.rowLabel}>Estado de la cuenta</Text>
              <Text style={styles.rowVal}>Activa y Autorizada</Text>
            </View>
          </View>
          <View style={styles.rowDivider} />
          <View style={styles.rowItem}>
            <Ionicons name="phone-portrait-outline" size={20} color="#1a6fcf" />
            <View style={styles.rowTextWrap}>
              <Text style={styles.rowLabel}>Dispositivo</Text>
              <Text style={styles.rowVal}>Expo / React Native App</Text>
            </View>
          </View>
        </View>

        {/* Logout Button */}
        <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout} activeOpacity={0.85}>
          <Ionicons name="log-out-outline" size={20} color="#dc2626" />
          <Text style={styles.logoutBtnText}>Cerrar sesión</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8fafc',
  },
  header: {
    backgroundColor: '#ffffff',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#e2e8f0',
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: '900',
    color: '#0f172a',
  },
  content: {
    padding: 20,
  },
  userHeroCard: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#e2e8f0',
    shadowColor: '#0f172a',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 3,
    marginBottom: 16,
  },
  avatarBig: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: '#1a6fcf',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 12,
    borderWidth: 3,
    borderColor: '#bfdbfe',
  },
  avatarBigText: {
    fontSize: 30,
    fontWeight: '900',
    color: '#ffffff',
  },
  usernameText: {
    fontSize: 20,
    fontWeight: '900',
    color: '#0f172a',
  },
  emailText: {
    fontSize: 13,
    color: '#64748b',
    marginTop: 2,
    marginBottom: 12,
  },
  roleChip: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 999,
    gap: 6,
    borderWidth: 1,
    borderColor: '#dbeafe',
  },
  roleChipText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#1e40af',
  },
  infoCard: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    marginBottom: 24,
  },
  rowItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 6,
  },
  rowTextWrap: {
    flex: 1,
  },
  rowLabel: {
    fontSize: 12,
    color: '#64748b',
    fontWeight: '600',
  },
  rowVal: {
    fontSize: 14,
    color: '#0f172a',
    fontWeight: '700',
    marginTop: 1,
  },
  rowDivider: {
    height: 1,
    backgroundColor: '#f1f5f9',
    marginVertical: 8,
  },
  logoutBtn: {
    backgroundColor: '#fef2f2',
    borderWidth: 1.5,
    borderColor: '#fecaca',
    borderRadius: 14,
    paddingVertical: 14,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 8,
  },
  logoutBtnText: {
    color: '#dc2626',
    fontSize: 15,
    fontWeight: '800',
  },
});

