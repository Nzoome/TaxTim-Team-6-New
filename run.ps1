# Crypto Tax Calculator - Run Script
# For Windows PowerShell

Write-Host "🪙 Starting Crypto Tax Calculator..." -ForegroundColor Cyan
Write-Host "=" -ForegroundColor Cyan -NoNewline; Write-Host ("=" * 50) -ForegroundColor Cyan

# Check if we're in the right directory
if (-not (Test-Path "backend") -or -not (Test-Path "frontend")) {
    Write-Host "❌ Error: Please run this script from the project root directory" -ForegroundColor Red
    exit 1
}

# Check if dependencies are installed
if (-not (Test-Path "backend/vendor")) {
    Write-Host "❌ Backend dependencies not found. Please run './install.ps1' first" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path "frontend/node_modules")) {
    Write-Host "❌ Frontend dependencies not found. Please run './install.ps1' first" -ForegroundColor Red
    exit 1
}

Write-Host "`n🚀 Starting services..." -ForegroundColor Yellow

# Start backend in a new PowerShell window
Write-Host "   Starting backend on http://localhost:8000..." -ForegroundColor Cyan
$backendPath = Join-Path $PSScriptRoot "backend\public"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$backendPath'; Write-Host '🔧 Backend Server Running on http://localhost:8000' -ForegroundColor Green; Write-Host 'Press Ctrl+C to stop' -ForegroundColor Yellow; php -S localhost:8000"

# Wait a moment for backend to start
Start-Sleep -Seconds 2

# Start frontend in a new PowerShell window
Write-Host "   Starting frontend on http://localhost:3000..." -ForegroundColor Cyan
$frontendPath = Join-Path $PSScriptRoot "frontend"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$frontendPath'; Write-Host '⚛️  Frontend Server Starting...' -ForegroundColor Green; Write-Host 'The browser will open automatically' -ForegroundColor Yellow; npm start"

Write-Host "`n✅ Services are starting!" -ForegroundColor Green
Write-Host "`n📝 Instructions:" -ForegroundColor Yellow
Write-Host "   - Backend: http://localhost:8000 (PHP server)" -ForegroundColor White
Write-Host "   - Frontend: http://localhost:3000 (React app)" -ForegroundColor White
Write-Host "   - Sample file: sample-transactions.csv" -ForegroundColor White
Write-Host "`n⚠️  To stop: Close the PowerShell windows or press Ctrl+C in each" -ForegroundColor Yellow
Write-Host "`n🎉 Happy calculating!" -ForegroundColor Cyan
