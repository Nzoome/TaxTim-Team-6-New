# Crypto Tax Calculator - Installation & Run Script
# For Windows PowerShell

Write-Host "🪙 Crypto Tax Calculator - Installation Script" -ForegroundColor Cyan
Write-Host "=" -ForegroundColor Cyan -NoNewline; Write-Host ("=" * 50) -ForegroundColor Cyan

# Check if we're in the right directory
if (-not (Test-Path "backend") -or -not (Test-Path "frontend")) {
    Write-Host "❌ Error: Please run this script from the project root directory" -ForegroundColor Red
    exit 1
}

# Function to check if a command exists
function Test-Command {
    param($Command)
    try {
        Get-Command $Command -ErrorAction Stop | Out-Null
        return $true
    }
    catch {
        return $false
    }
}

Write-Host "`n📋 Checking prerequisites..." -ForegroundColor Yellow

# Check PHP
if (Test-Command "php") {
    $phpVersion = php -v | Select-Object -First 1
    Write-Host "✅ PHP found: $phpVersion" -ForegroundColor Green
} else {
    Write-Host "❌ PHP not found. Please install PHP 8.1 or higher" -ForegroundColor Red
    Write-Host "   Download from: https://www.php.net/downloads" -ForegroundColor Yellow
    exit 1
}

# Check Composer
if (Test-Command "composer") {
    $composerVersion = composer --version | Select-Object -First 1
    Write-Host "✅ Composer found: $composerVersion" -ForegroundColor Green
} else {
    Write-Host "❌ Composer not found. Please install Composer" -ForegroundColor Red
    Write-Host "   Download from: https://getcomposer.org/download/" -ForegroundColor Yellow
    exit 1
}

# Check Node.js
if (Test-Command "node") {
    $nodeVersion = node -v
    Write-Host "✅ Node.js found: $nodeVersion" -ForegroundColor Green
} else {
    Write-Host "❌ Node.js not found. Please install Node.js 18 or higher" -ForegroundColor Red
    Write-Host "   Download from: https://nodejs.org/" -ForegroundColor Yellow
    exit 1
}

# Check npm
if (Test-Command "npm") {
    $npmVersion = npm -v
    Write-Host "✅ npm found: v$npmVersion" -ForegroundColor Green
} else {
    Write-Host "❌ npm not found. It should come with Node.js" -ForegroundColor Red
    exit 1
}

Write-Host "`n📦 Installing dependencies..." -ForegroundColor Yellow

# Install backend dependencies
Write-Host "`n🔧 Installing PHP dependencies..." -ForegroundColor Cyan
Push-Location backend
try {
    composer install --no-interaction
    Write-Host "✅ PHP dependencies installed" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to install PHP dependencies" -ForegroundColor Red
    Pop-Location
    exit 1
}
Pop-Location

# Create logs directory
if (-not (Test-Path "backend/logs")) {
    New-Item -ItemType Directory -Path "backend/logs" | Out-Null
    Write-Host "✅ Created logs directory" -ForegroundColor Green
}

# Install frontend dependencies
Write-Host "`n🔧 Installing Node dependencies..." -ForegroundColor Cyan
Push-Location frontend
try {
    npm install
    Write-Host "✅ Node dependencies installed" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to install Node dependencies" -ForegroundColor Red
    Pop-Location
    exit 1
}
Pop-Location

Write-Host "`n✅ Installation complete!" -ForegroundColor Green
Write-Host "`n📚 Next steps:" -ForegroundColor Yellow
Write-Host "   1. Run './run.ps1' to start both backend and frontend" -ForegroundColor White
Write-Host "   2. Or manually start:" -ForegroundColor White
Write-Host "      - Backend: cd backend/public; php -S localhost:8000" -ForegroundColor Gray
Write-Host "      - Frontend: cd frontend; npm start" -ForegroundColor Gray
Write-Host "`n🌐 The app will open at http://localhost:3000" -ForegroundColor Cyan
Write-Host "📄 Use sample-transactions.csv to test the upload" -ForegroundColor Cyan
