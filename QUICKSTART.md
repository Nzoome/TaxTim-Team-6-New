# 🚀 Quick Start Guide

## Prerequisites(Install)
- PHP 8.1 or higher
- Composer
- Node.js 18+ and npm
- Git (optional)

### 1. Backend Setup

```bash
# Navigate to backend directory
cd backend

# Install PHP dependencies
composer install

# Create logs directory
mkdir logs
```

### 2. Frontend Setup

```bash
# Navigate to frontend directory
cd frontend

# Install Node dependencies
npm install
```

### 3. Start the Application

**Terminal 1 - Backend:**
```bash
cd backend/public
php -S localhost:8000 router.php
```

**Terminal 2 - Frontend:**
```bash
cd frontend
npm start
```

The application will open at `http://localhost:3000`

## First Upload

1. Use the sample file: `sample-transactions.csv`
2. Drag and drop it into the upload area
3. Click "Calculate Taxes"
4. View your parsed transactions!

## Troubleshooting

### Backend Issues

**Error: "composer: command not found"**
- Install Composer: https://getcomposer.org/download/

**Error: "PHP version not supported"**
- Update to PHP 8.1+: https://www.php.net/downloads

**Error: "Port 8000 already in use"**
- Use a different port: `php -S localhost:8080`
- Update frontend `.env`: `REACT_APP_API_URL=http://localhost:8080`

### Frontend Issues

**Error: "npm: command not found"**
- Install Node.js: https://nodejs.org/

**Error: "Port 3000 already in use"**
- The app will prompt to use a different port automatically

**Error: "Network error"**
- Ensure backend is running on port 8000
- Check CORS settings in `backend/public/index.php`

## Testing with Sample Data

The project includes `sample-transactions.csv` with various transaction types:
- 3 BUY transactions
- 1 SELL transaction
- 3 TRADE transactions
- Multiple wallets (Luno, Binance)
- Transactions across different dates

## Next Steps

After Sprint 1:
- Sprint 2: FIFO calculations
- Sprint 3: Capital gains reporting
- Sprint 4: Tax year summaries

## Need Help?

- Check the main README.md
- Review backend/README.md
- Review frontend/README.md
- Inspect browser console for frontend errors
- Check backend/logs/ for PHP errors
