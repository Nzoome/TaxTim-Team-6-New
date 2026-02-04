# Crypto Tax Calculator - Backend

PHP backend for the TaxTim Crypto Tax Calculator.

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

```bash
composer install
```

## Setup

1. Install dependencies:
```bash
composer install
```

2. Configure your web server to point to the `public` directory

3. Ensure the `logs` directory is writable:
```bash
mkdir -p logs
chmod 755 logs
```

## Usage

The backend exposes a single endpoint:

### POST /

Upload a CSV or XLSX file containing crypto transactions.

**Request:**
- Method: POST
- Content-Type: multipart/form-data
- Body: file (CSV or XLSX)

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "transactions": [...],
    "summary": {
      "total_transactions": 10,
      "transaction_types": {
        "BUY": 5,
        "SELL": 3,
        "TRADE": 2
      },
      "currencies": ["BTC", "ETH", "ZAR"],
      "date_range": {
        "earliest": "2024-01-01",
        "latest": "2024-12-31"
      }
    }
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "error": "Validation failed",
  "errors": {
    "row_2": ["Invalid date format: 2024-13-01"],
    "row_3": ["From amount must be a positive number: -1.5"]
  }
}
```

## File Format

The CSV/XLSX file must contain the following columns:

| Column | Required | Description |
|--------|----------|-------------|
| date | Yes | Transaction date (YYYY-MM-DD or any parseable format) |
| type | Yes | Transaction type (BUY, SELL, or TRADE) |
| from_currency | Yes | Source currency (e.g., ZAR, BTC) |
| from_amount | Yes | Amount of source currency |
| to_currency | Yes | Destination currency (e.g., BTC, ETH) |
| to_amount | Yes | Amount of destination currency |
| price | Yes | Price per unit in ZAR |
| fee | No | Transaction fee (default: 0) |
| wallet | No | Wallet identifier |

## Project Structure

```
backend/
├── public/
│   └── index.php          # Entry point
├── src/
│   ├── Models/
│   │   └── Transaction.php
│   ├── Parsers/
│   │   ├── CSVParser.php
│   │   └── XLSXParser.php
│   ├── Validators/
│   │   └── TransactionValidator.php
│   ├── Services/
│   │   ├── FileProcessor.php
│   │   ├── TransactionNormalizer.php
│   │   ├── TransactionSorter.php
│   │   └── Logger.php
│   └── Exceptions/
│       ├── ParseException.php
│       └── ValidationException.php
├── logs/                  # Log files
└── composer.json
```

## Sprint 1 Implementation

Sprint 1 focuses on file upload, parsing, validation, normalization, and sorting:

✅ Parse CSV files
✅ Parse XLSX files
✅ Validate file structure
✅ Validate transaction data
✅ Normalize into Transaction objects
✅ Sort chronologically

FIFO calculations and tax logic will be implemented in later sprints.
