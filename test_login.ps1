$body = @{ username = "admin"; password = "Admin123!" } | ConvertTo-Json
try {
    $r = Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/v1/auth/login" -Method POST -ContentType "application/json" -Body $body
    Write-Host "STATUS:" $r.StatusCode
    Write-Host "BODY:" $r.Content
} catch {
    Write-Host "ERROR STATUS:" $_.Exception.Response.StatusCode.Value__
    $reader = [System.IO.StreamReader]::new($_.Exception.Response.GetResponseStream())
    Write-Host "ERROR BODY:" $reader.ReadToEnd()
}
