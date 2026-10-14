// Configuración de la API para FinancieraBan
// En desarrollo usa la IP local; en producción (APK / EAS Build) usa la URL del VPS
const PROD_API_URL = 'https://api.tudominio.com/api/v1';
const DEV_API_URL = 'http://10.77.243.40:8000/api/v1';

export const API_BASE_URL = __DEV__ ? DEV_API_URL : PROD_API_URL;
