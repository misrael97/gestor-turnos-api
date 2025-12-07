# Script para cambiar QUEUE_CONNECTION a sync en Laravel
# Ejecutar desde: c:\Users\silva\Desktop\pwa buena\gestor-turnos-api

Write-Host "🔧 Configurando envío síncrono de correos..." -ForegroundColor Cyan

$envPath = ".env"

if (-not (Test-Path $envPath)) {
    Write-Host "❌ Error: No se encontró el archivo .env" -ForegroundColor Red
    Write-Host "   Asegúrate de ejecutar este script desde la raíz del proyecto Laravel" -ForegroundColor Yellow
    exit 1
}

# Leer contenido del archivo
$content = Get-Content $envPath -Raw

# Verificar configuración actual
if ($content -match "QUEUE_CONNECTION=(\w+)") {
    $currentValue = $Matches[1]
    Write-Host "📋 Configuración actual: QUEUE_CONNECTION=$currentValue" -ForegroundColor Yellow
    
    if ($currentValue -eq "sync") {
        Write-Host "✅ Ya está configurado como 'sync'. No se necesitan cambios." -ForegroundColor Green
        exit 0
    }
}

# Hacer backup del archivo .env
$backupPath = ".env.backup." + (Get-Date -Format "yyyyMMdd_HHmmss")
Copy-Item $envPath $backupPath
Write-Host "💾 Backup creado: $backupPath" -ForegroundColor Green

# Reemplazar QUEUE_CONNECTION
$content = $content -replace "QUEUE_CONNECTION=\w+", "QUEUE_CONNECTION=sync"

# Guardar cambios
Set-Content $envPath $content -NoNewline

Write-Host "✅ Cambio realizado: QUEUE_CONNECTION=sync" -ForegroundColor Green

# Limpiar caché de Laravel
Write-Host "`n🧹 Limpiando caché de Laravel..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear

Write-Host "`n✨ ¡Configuración completada!" -ForegroundColor Green
Write-Host "`n📧 Ahora los correos se enviarán inmediatamente sin necesidad de 'php artisan queue:work'" -ForegroundColor Cyan
Write-Host "`n🧪 Prueba el login en la PWA para verificar que funcione" -ForegroundColor White
