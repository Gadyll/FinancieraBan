import AsyncStorage from "@react-native-async-storage/async-storage";
import "react-native-get-random-values";
import { v4 as uuidv4 } from "uuid";

const DEVICE_ID_KEY = "@mybank_device_id";

/**
 * Retorna el device_id unico de este dispositivo.
 * Si no existe, lo genera como UUID v4 y lo persiste en AsyncStorage
 * para siempre (no se borra al cerrar sesion, solo al desinstalar).
 */
export async function getOrCreateDeviceId(): Promise<string> {
  try {
    const stored = await AsyncStorage.getItem(DEVICE_ID_KEY);
    if (stored) return stored;

    const newId = uuidv4();
    await AsyncStorage.setItem(DEVICE_ID_KEY, newId);
    return newId;
  } catch {
    // Fallback: generar uno en memoria (no persistente pero funcional)
    return uuidv4();
  }
}
