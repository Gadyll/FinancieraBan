# Test completo del flujo de autenticación
$baseUrl = "http://127.0.0.1:8000/api/v1"

Write-Host "=== TEST 1: Login con credenciales correctas ===" -ForegroundColor Cyan
$loginBody = @{ username = "admin"; password = "Admin123!" } | ConvertTo-Json
try {
    $r = Invoke-WebRequest -Uri "$baseUrl/auth/login" -Method POST -ContentType "application/json" -Body $loginBody
    $data = $r.Content | ConvertFrom-Json
    Write-Host "STATUS: $($r.StatusCode)" -ForegroundColor Green
    Write-Host "access_token prefix: $($data.access_token.Substring(0, 30))..."
    Write-Host "refresh_token prefix: $($data.refresh_token.Substring(0, 30))..."
    $accessToken  = $data.access_token
    $refreshToken = $data.refresh_token
} catch {
    $reader = [System.IO.StreamReader]::new($_.Exception.Response.GetResponseStream())
    Write-Host "FAIL: $($reader.ReadToEnd())" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "=== TEST 2: Login con credenciales incorrectas ===" -ForegroundColor Cyan
$badBody = @{ username = "admin"; password = "WRONG" } | ConvertTo-Json
try {
    $r2 = Invoke-WebRequest -Uri "$baseUrl/auth/login" -Method POST -ContentType "application/json" -Body $badBody
    Write-Host "FAIL: Debería haber dado 401" -ForegroundColor Red
} catch {
    $code = $_.Exception.Response.StatusCode.Value__
    Write-Host "STATUS: $code (esperado 401)" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== TEST 3: /me con access token ===" -ForegroundColor Cyan
try {
    $r3 = Invoke-WebRequest -Uri "$baseUrl/auth/me" -Method GET -Headers @{ Authorization = "Bearer $accessToken" }
    $me = $r3.Content | ConvertFrom-Json
    Write-Host "STATUS: $($r3.StatusCode)" -ForegroundColor Green
    Write-Host "User: $($me.username) | Role: $($me.role) | Active: $($me.is_active)"
} catch {
    $reader = [System.IO.StreamReader]::new($_.Exception.Response.GetResponseStream())
    Write-Host "FAIL: $($reader.ReadToEnd())" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== TEST 4: Refresh token (cuerpo JSON) ===" -ForegroundColor Cyan
$refreshBody = @{ refresh_token = $refreshToken } | ConvertTo-Json
try {
    $r4 = Invoke-WebRequest -Uri "$baseUrl/auth/refresh" -Method POST -ContentType "application/json" -Body $refreshBody
    $newTokens = $r4.Content | ConvertFrom-Json
    Write-Host "STATUS: $($r4.StatusCode)" -ForegroundColor Green
    Write-Host "Nuevo access_token prefix: $($newTokens.access_token.Substring(0, 30))..."
} catch {
    $reader = [System.IO.StreamReader]::new($_.Exception.Response.GetResponseStream())
    Write-Host "FAIL: $($reader.ReadToEnd())" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== TEST 5: Refresh con token inválido ===" -ForegroundColor Cyan
$badRefreshBody = @{ refresh_token = "token.invalido.aqui" } | ConvertTo-Json
try {
    $r5 = Invoke-WebRequest -Uri "$baseUrl/auth/refresh" -Method POST -ContentType "application/json" -Body $badRefreshBody
    Write-Host "FAIL: Debería haber dado 401" -ForegroundColor Red
} catch {
    $code = $_.Exception.Response.StatusCode.Value__
    Write-Host "STATUS: $code (esperado 401)" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== TODOS LOS TESTS COMPLETADOS ===" -ForegroundColor Yellow
