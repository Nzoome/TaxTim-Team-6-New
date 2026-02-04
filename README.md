# 🪙 Crypto Tax Calculator for TaxTim

**Version**: 4.0.0 - Production Ready ✅
**Status**: All 4 Sprints Complete 🎉

A comprehensive cryptocurrency tax calculator built for TaxTim to help South African taxpayers calculate their crypto capital gains using SARS-compliant FIFO (First-In, First-Out) methodology with full audit reporting capabilities.

## 📋 Project Overview

This production-ready tool helps taxpayers:
- ✅ Upload transaction history from CSV or XLSX files
- ✅ Automatically parse, validate, and normalize transactions
- ✅ Calculate capital gains using FIFO methodology
- ✅ Allocate transactions to correct tax years
- ✅ Visualize data with interactive charts
- ✅ Filter transactions by asset, type, and tax year
- ✅ Export SARS-ready PDF audit reports
- ✅ Export detailed Excel/CSV files
- ✅ Trace every disposal back to specific acquisitions

## 🎯 All Sprints Complete

### ✅ Sprint 1: File Upload & Processing (COMPLETE)
- File upload with drag-and-drop
- CSV and XLSX parsing
- Data validation and normalization
- Chronological sorting

### ✅ Sprint 2: FIFO Engine (COMPLETE)
- FIFO lot management
- Capital gains calculations
- Cost base tracking
- Partial lot consumption

### ✅ Sprint 3: Tax Year Integration (COMPLETE)
- South African tax year support (March 1 - Feb 28/29)
- Tax year allocation
- Multi-year processing
- Tax year reporting

### ✅ Sprint 4: UI & Audit Reports (COMPLETE)
- Interactive dashboard
- Summary cards with taxable amounts
- Visual analytics (charts)
- Advanced filtering
- FIFO traceability UI
- PDF audit reports
- Excel exports
- Comprehensive testing

## 🏗️ Project Structure

```
crypto-tax-calculator/
├── backend/                    # PHP Backend
│   ├── src/
│   │   ├── Models/            # Data models
│   │   ├── Parsers/           # CSV & XLSX parsers
│   │   ├── Validators/        # Data validation
│   │   ├── Services/          # Business logic
│   │   └── Exceptions/        # Custom exceptions
│   ├── public/                # Entry point
│   └── composer.json          # PHP dependencies
│
├── frontend/                   # React Frontend
│   ├── src/
│   │   ├── components/        # UI components
│   │   ├── services/          # API services
│   │   ├── App.js            # Main application
│   │   └── index.js          # Entry point
│   └── package.json           # Node dependencies
│
└── README.md                  # This file
```

## 🚀 Sprint 1 Implementation (COMPLETE)

Sprint 1 focuses on file upload, parsing, validation, normalization, and sorting.

### ✅ Completed Features

1. **File Upload & Processing**
   - Drag-and-drop interface
   - Support for CSV and XLSX files
   - File size validation

2. **CSV Parser**
   - Reads CSV files
   - Normalizes column headers
   - Handles empty rows
   - Provides line-by-line error reporting

3. **XLSX Parser**
   - Reads Excel files
   - Handles Excel date formats
   - Produces same output format as CSV parser

4. **Validation**
   - Required column verification
   - Date format validation
   - Numeric amount validation
   - Transaction type validation (BUY, SELL, TRADE)

5. **Normalization**
   - Converts raw data into Transaction objects
   - Standardizes currency codes
   - Handles optional fields (fee, wallet)

6. **Chronological Sorting**
   - Sorts by date/time
   - Uses line number as tie-breaker
   - Ensures deterministic ordering

7. **UI Components**
   - File upload component with drag-and-drop
   - Error display with detailed messages
   - Transaction summary statistics
   - Expandable transaction table

## 📦 Installation

### Backend Setup

1. Navigate to backend directory:
```bash
cd backend
```

2. Install PHP dependencies:
```bash
composer install
```

3. Configure web server (Apache/Nginx) to point to `public` directory

4. Ensure logs directory is writable:
```bash
mkdir -p logs
chmod 755 logs
```

### Frontend Setup

1. Navigate to frontend directory:
```bash
cd frontend
```

2. Install Node dependencies:
```bash
npm install
```

3. Create `.env` file (optional):
```
REACT_APP_API_URL=http://localhost:8000
```

## 🎮 Running the Application

### Start Backend (PHP)

**Option 1: Using PHP Built-in Server**
```bash
cd backend/public
php -S localhost:8000
```

**Option 2: Using Apache/Nginx**
Configure your web server to serve the `backend/public` directory.

### Start Frontend (React)

```bash
cd frontend
npm start
```

The application will open at `http://localhost:3000`

## 📄 File Format

### Required Columns

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| date | Date | Transaction date | 2024-11-01 |
| type | String | BUY, SELL, or TRADE | BUY |
| from_currency | String | Source currency | ZAR |
| from_amount | Number | Source amount | 80000 |
| to_currency | String | Destination currency | BTC |
| to_amount | Number | Destination amount | 0.1 |
| price | Number | Price per unit (ZAR) | 800000 |
| fee | Number | Transaction fee (optional) | 100 |
| wallet | String | Wallet identifier (optional) | Binance |

### Sample Data

See `sample-transactions.csv` for an example file.

## 🧪 Testing

### Test with Sample Data

1. Use the provided sample CSV file
2. Upload through the web interface
3. Verify parsing and validation

### Manual Testing Checklist

- ✅ Upload valid CSV file
- ✅ Upload valid XLSX file
- ✅ Upload file with missing columns
- ✅ Upload file with invalid dates
- ✅ Upload file with negative amounts
- ✅ Upload file with invalid transaction types

## 🔧 Technology Stack

### Backend
- **Language**: PHP 8.1+
- **Libraries**: PhpSpreadsheet (for XLSX parsing)
- **Architecture**: Object-Oriented Programming

### Frontend
- **Framework**: React 18
- **HTTP Client**: Axios
- **Styling**: CSS3 with modern features

## 📊 API Documentation

### POST /

Upload and process crypto transaction file.

**Request:**
- Method: `POST`
- Content-Type: `multipart/form-data`
- Body: `file` (CSV or XLSX)

**Success Response:**
```json
{
  "success": true,
  "data": {
    "transactions": [...],
    "summary": {
      "total_transactions": 10,
      "transaction_types": {"BUY": 5, "SELL": 3, "TRADE": 2},
      "currencies": ["BTC", "ETH", "ZAR"],
      "date_range": {
        "earliest": "2024-01-01",
        "latest": "2024-12-31"
      }
    }
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "row_2": ["Invalid date format: 2024-13-01"]
  }
}
```

## 🎯 Future Sprints

### Sprint 2: FIFO Calculations
- Implement multi-balance FIFO tracking
- Track cost basis for each purchase
- Handle partial sells/trades
- Calculate realized gains/losses

### Sprint 3: Tax Reporting
- Generate Base Cost reports per tax year
- Calculate Capital Gains per tax year
- Support multiple tax years
- Export reports for SARS

### Sprint 4: Advanced Features
- Multi-wallet support
- Stablecoin handling
- Exchange fee integration
- Historical price lookups

## 👥 Team Collaboration

### Backend Developers
- Backend Dev A: File processing, validation
- Backend Dev B: Parsers, normalization

### Frontend Developers
- Frontend Dev A: Core UI, layout
- Frontend Dev B: Components, integration

## 🐛 Known Issues / Limitations

Sprint 1 implementation does NOT include:
- FIFO lot tracking
- Capital gains calculations
- Tax year logic
- Multi-wallet functionality
- Price lookups

These features will be added in future sprints.

## 📝 License

© 2026 TaxTim - All Rights Reserved

## 🤝 Contributing

This project is part of a competition submission for TaxTim. For questions or support, please contact the development team.

## 🌟 Acknowledgments

- TaxTim for the project opportunity
- SARS for tax guidelines
- The open-source community for tools and libraries

---

**Built with ❤️ for TaxTim**
