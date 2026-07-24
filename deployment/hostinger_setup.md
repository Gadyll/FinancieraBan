# 🚀 Guía de Despliegue en Hostinger — FinancieraBan

Este documento contiene las instrucciones paso a paso para desplegar la solución completa en Hostinger (VPS con Ubuntu/Debian o Web Hosting Business).

---

## 🏗️ Arquitectura de Producción

- **Frontend Admin Web:** Laravel 12 (PHP 8.3 / Nginx o Apache).
- **Backend API:** FastAPI (Python 3.10 / Uvicorn + Systemd).
- **Base de Datos:** MySQL / MariaDB (Compartida entre Laravel y FastAPI).
- **App Móvil:** Expo / React Native (Compilable a APK Android y IPA iOS).

---

## 1. Configuración de Base de Datos MySQL (Hostinger)

1. Crear base de datos en Hostinger hPanel:
   - **Nombre de BD:** `financieraban_prod`
   - **Usuario:** `financieraban_user`
   - **Contraseña:** `[TuContraseñaSegura]`

2. Importar migraciones/esquema ejecutando en el servidor:
   ```bash
   cd /var/www/financieraban/backend-api
   python -m alembic upgrade head
   ```

---

## 2. Despliegue del Backend API (FastAPI)

1. **Clonar/Subir código a `/var/www/financieraban/backend-api`**

2. **Crear entorno virtual Python:**
   ```bash
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   ```

3. **Configurar archivo `.env` en `backend-api/.env`:**
   ```env
   PROJECT_NAME=FinancieraBan API
   DATABASE_URL=mysql+pymysql://financieraban_user:[TuContraseñaSegura]@127.0.0.1:3306/financieraban_prod
   SECRET_KEY=[GenerarClaveSecreta32Caracteres]
   ALGORITHM=HS256
   ACCESS_TOKEN_EXPIRE_MINUTES=43200
   ALLOWED_ORIGINS=https://admin.tudominio.com,https://api.tudominio.com
   ```

4. **Crear Servicio Systemd para FastAPI (`/etc/systemd/system/financieraban-api.service`):**
   ```ini
   [Unit]
   Description=FinancieraBan FastAPI Service
   After=network.target

   [Service]
   User=www-data
   WorkingDirectory=/var/www/financieraban/backend-api
   ExecStart=/var/www/financieraban/backend-api/venv/bin/uvicorn app.main:app --host 127.0.0.1 --port 8000 --workers 4
   Restart=always

   [Install]
   WantedBy=multi-user.target
   ```

5. **Iniciar Servicio:**
   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable financieraban-api
   sudo systemctl start financieraban-api
   ```

---

## 3. Despliegue de la Web Administrativa (Laravel 12)

1. **Subir código a `/var/www/financieraban/web-admin`**

2. **Configurar `.env` en `web-admin/.env`:**
   ```env
   APP_NAME=FinancieraBan
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://admin.tudominio.com
   MYBANK_API_URL=http://127.0.0.1:8000/api/v1
   ```

3. **Instalar dependencias y compilar assets:**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install && npm run build
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## 4. Configuración de Nginx Reverse Proxy y SSL

Ejemplo de `/etc/nginx/sites-available/financieraban.conf`:

```nginx
# API Backend FastAPI
server {
    server_name api.tudominio.com;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# Web Admin Laravel
server {
    server_name admin.tudominio.com;
    root /var/www/financieraban/web-admin/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Activar SSL con Certbot:
```bash
sudo certbot --nginx -d admin.tudominio.com -d api.tudominio.com
```

---

## 5. Compilación de la App Móvil (Expo Android / iOS)

1. **Configurar URL de Producción en `mobile-app/constants/config.ts`:**
   ```typescript
   export const API_BASE_URL = 'https://api.tudominio.com/api/v1';
   ```

2. **Compilar APK/AAB para Android:**
   ```bash
   eas build -p android --profile production
   ```

3. **Compilar IPA para iOS:**
   ```bash
   eas build -p ios --profile production
   ```

