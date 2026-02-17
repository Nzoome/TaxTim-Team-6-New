/**
 * PDF Generator for SARS Audit Report
 * 
 * This module generates a comprehensive PDF audit report suitable for SARS submission.
 * Uses jsPDF library for PDF generation.
 */

// For now, we'll create a simple implementation that uses the browser's print functionality
// In production, you'd use jsPDF or similar library

export const generatePDF = async (transactions, summary, filters) => {
  // Create a temporary container for the report
  const reportHTML = generateReportHTML(transactions, summary, filters);
  
  // Create a new window for printing
  const printWindow = window.open('', '_blank');
  if (!printWindow) {
    throw new Error('Popup blocked. Please allow popups for this site.');
  }

  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Crypto Tax Audit Report - SARS</title>
      <style>
        ${getPrintStyles()}
      </style>
    </head>
    <body>
      ${reportHTML}
    </body>
    </html>
  `);
  
  printWindow.document.close();
  
  // Wait for content to load, then print
  setTimeout(() => {
    printWindow.print();
  }, 500);
};

const generateReportHTML = (transactions, summary, filters) => {
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-ZA', {
      style: 'currency',
      currency: 'ZAR',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount);
  };

  const formatCrypto = (amount, decimals = 8) => {
    if (amount === null || amount === undefined) return '-';
    return parseFloat(amount).toFixed(decimals).replace(/\.?0+$/, '');
  };

  const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-ZA', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const currentDate = new Date().toLocaleDateString('en-ZA', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });

  const filterText = [];
  if (filters.asset !== 'all') filterText.push(`Asset: ${filters.asset}`);
  if (filters.type !== 'all') filterText.push(`Type: ${filters.type}`);
  if (filters.taxYear !== 'all') filterText.push(`Tax Year: ${filters.taxYear}`);
  const filterDisplay = filterText.length > 0 ? filterText.join(', ') : 'None';

  return `
    <div class="report">
      <!-- Header -->
      <div class="report-header">
        <h1>Cryptocurrency Capital Gains Tax Report</h1>
        <h2>SARS Audit Report</h2>
        <p class="report-date">Generated: ${currentDate}</p>
        <div class="divider"></div>
      </div>

      <!-- Executive Summary -->
      <div class="section">
        <h3>Executive Summary</h3>
        <table class="summary-table">
          <tr>
            <td class="label">Total Capital Gains:</td>
            <td class="value positive">${formatCurrency(summary.totalCapitalGain)}</td>
          </tr>
          <tr>
            <td class="label">Total Capital Losses:</td>
            <td class="value negative">${formatCurrency(summary.totalCapitalLoss)}</td>
          </tr>
          <tr class="highlight">
            <td class="label"><strong>Net Capital Gain:</strong></td>
            <td class="value"><strong>${formatCurrency(summary.netCapitalGain)}</strong></td>
          </tr>
          <tr class="highlight-primary">
            <td class="label"><strong>Taxable Capital Gain (40% inclusion):</strong></td>
            <td class="value"><strong>${formatCurrency(summary.taxableCapitalGain)}</strong></td>
          </tr>
          <tr>
            <td class="label">Total Proceeds:</td>
            <td class="value">${formatCurrency(summary.totalProceeds)}</td>
          </tr>
          <tr>
            <td class="label">Total Cost Base (FIFO):</td>
            <td class="value">${formatCurrency(summary.totalCostBase)}</td>
          </tr>
          <tr>
            <td class="label">Transactions Processed:</td>
            <td class="value">${summary.transactionsProcessed}</td>
          </tr>
        </table>
        
        <div class="info-box">
          <p><strong>Applied Filters:</strong> ${filterDisplay}</p>
          <p><strong>Method:</strong> First-In-First-Out (FIFO)</p>
          <p><strong>Inclusion Rate:</strong> 40% for individuals (as per SARS requirements)</p>
        </div>
      </div>

      <!-- SARS Compliance -->
      <div class="section">
        <h3>SARS Compliance Information</h3>
        <p>This report has been prepared in accordance with South African Revenue Service (SARS) guidelines for cryptocurrency taxation:</p>
        <ul>
          <li><strong>Tax Year:</strong> March 1 to February 28/29 of the following year</li>
          <li><strong>CGT Inclusion Rate:</strong> 40% for individuals</li>
          <li><strong>Valuation Method:</strong> FIFO (First-In-First-Out)</li>
          <li><strong>Annual Exclusion:</strong> R40,000 per year (not applied in calculations - to be claimed on ITR12)</li>
        </ul>
      </div>

      <!-- Transaction Details -->
      <div class="section">
        <h3>Transaction Details</h3>
        <p class="sub-info">Chronological listing of all cryptocurrency transactions with FIFO-based capital gains calculations</p>
        
        <table class="transaction-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Currency</th>
              <th>Amount</th>
              <th>Proceeds</th>
              <th>Cost Base</th>
              <th>Gain/Loss</th>
              <th>Tax Year</th>
            </tr>
          </thead>
          <tbody>
            ${transactions.map((tx, index) => `
              <tr>
                <td>${formatDate(tx.date)}</td>
                <td><span class="badge badge-${tx.type?.toLowerCase()}">${tx.type}</span></td>
                <td>${tx.currency}</td>
                <td>${formatCrypto(tx.amount)}</td>
                <td>${tx.proceeds !== null && tx.proceeds !== undefined ? formatCurrency(tx.proceeds) : '-'}</td>
                <td>${tx.costBase !== null && tx.costBase !== undefined ? formatCurrency(tx.costBase) : '-'}</td>
                <td class="${tx.capitalGain > 0 ? 'positive' : tx.capitalGain < 0 ? 'negative' : ''}">
                  ${tx.capitalGain !== null && tx.capitalGain !== undefined ? formatCurrency(tx.capitalGain) : '-'}
                </td>
                <td>${tx.taxYear || '-'}</td>
              </tr>
              ${tx.lotsConsumed && tx.lotsConsumed.length > 0 ? `
                <tr class="fifo-detail">
                  <td colspan="8">
                    <div class="fifo-breakdown">
                      <strong>📦 FIFO Lots Consumed (${tx.lotsConsumed.length} lot${tx.lotsConsumed.length !== 1 ? 's' : ''}):</strong>
                      <table class="fifo-table">
                        <thead>
                          <tr>
                            <th>Purchase Date</th>
                            <th>Amount</th>
                            <th>Cost/Unit</th>
                            <th>Cost Base</th>
                            <th>Held (Days)</th>
                          </tr>
                        </thead>
                        <tbody>
                          ${tx.lotsConsumed.map(lot => `
                            <tr>
                              <td>${formatDate(lot.purchaseDate)}</td>
                              <td>${formatCrypto(lot.amountConsumed)}</td>
                              <td>${formatCurrency(lot.costPerUnit)}</td>
                              <td>${formatCurrency(lot.costBase)}</td>
                              <td>${lot.ageInDays !== null && lot.ageInDays !== undefined ? 
                                `${lot.ageInDays}${lot.ageInDays >= 365 ? ' <span class="lt-badge">LT</span>' : ''}` : 
                                '-'}</td>
                            </tr>
                          `).join('')}
                        </tbody>
                        <tfoot>
                          <tr>
                            <td colspan="3"><strong>Total Cost Base:</strong></td>
                            <td><strong>${formatCurrency(tx.lotsConsumed.reduce((sum, lot) => sum + lot.costBase, 0))}</strong></td>
                            <td></td>
                          </tr>
                        </tfoot>
                      </table>
                      <p class="fifo-note"><strong>FIFO Method:</strong> First-In-First-Out means the oldest coins purchased are sold first.</p>
                    </div>
                  </td>
                </tr>
              ` : ''}
            `).join('')}
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="report-footer">
        <p><strong>Disclaimer:</strong> This report is provided for informational purposes only and should not be considered as professional tax advice. 
        Please consult with a registered tax professional or chartered accountant before submitting your tax return to SARS.</p>
        <p class="footer-note">Generated by Crypto Tax Calculator • Powered by TaxTim</p>
      </div>
    </div>
  `;
};

const getPrintStyles = () => {
  return `
    @media print {
      @page {
        margin: 2cm;
        size: A4;
      }
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      line-height: 1.6;
      color: #333;
      max-width: 210mm;
      margin: 0 auto;
      padding: 20px;
      background: white;
    }

    .report {
      background: white;
    }

    .report-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 3px solid #667eea;
    }

    .report-header h1 {
      margin: 0 0 10px 0;
      color: #2d3748;
      font-size: 28px;
    }

    .report-header h2 {
      margin: 0 0 10px 0;
      color: #667eea;
      font-size: 20px;
      font-weight: normal;
    }

    .report-date {
      margin: 10px 0;
      color: #718096;
      font-size: 14px;
    }

    .section {
      margin-bottom: 30px;
      page-break-inside: avoid;
    }

    .section h3 {
      color: #2d3748;
      font-size: 18px;
      margin: 0 0 15px 0;
      padding-bottom: 8px;
      border-bottom: 2px solid #e2e8f0;
    }

    .sub-info {
      color: #718096;
      font-size: 13px;
      margin-bottom: 15px;
    }

    .summary-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }

    .summary-table td {
      padding: 10px;
      border-bottom: 1px solid #e2e8f0;
    }

    .summary-table .label {
      width: 60%;
      color: #4a5568;
    }

    .summary-table .value {
      width: 40%;
      text-align: right;
      font-weight: 600;
      color: #2d3748;
    }

    .summary-table .positive {
      color: #38a169;
    }

    .summary-table .negative {
      color: #e53e3e;
    }

    .summary-table .highlight {
      background-color: #f7fafc;
      font-size: 16px;
    }

    .summary-table .highlight-primary {
      background-color: #ebf8ff;
      font-size: 16px;
    }

    .info-box {
      background: #f7fafc;
      border-left: 4px solid #4299e1;
      padding: 15px;
      margin-top: 15px;
    }

    .info-box p {
      margin: 5px 0;
      font-size: 13px;
      color: #4a5568;
    }

    ul {
      margin: 10px 0;
      padding-left: 25px;
    }

    li {
      margin: 8px 0;
      color: #4a5568;
      font-size: 14px;
    }

    .transaction-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 11px;
      margin-top: 15px;
    }

    .transaction-table thead {
      background: #2d3748;
      color: white;
    }

    .transaction-table th {
      padding: 10px 8px;
      text-align: left;
      font-weight: 600;
      font-size: 10px;
      text-transform: uppercase;
    }

    .transaction-table tbody tr {
      border-bottom: 1px solid #e2e8f0;
    }

    .transaction-table td {
      padding: 8px;
      color: #4a5568;
    }

    .badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 10px;
      font-size: 9px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .badge-buy {
      background-color: #c6f6d5;
      color: #22543d;
    }

    .badge-sell {
      background-color: #fed7d7;
      color: #742a2a;
    }

    .badge-trade, .badge-trade_sell, .badge-trade_buy {
      background-color: #bee3f8;
      color: #2c5282;
    }

    .fifo-detail td {
      padding: 12px !important;
      background-color: #fafbfc;
      border: 1px solid #e5e7eb;
    }

    .fifo-breakdown {
      font-size: 10px;
    }

    .fifo-breakdown > strong {
      color: #374151;
      font-size: 11px;
    }

    .fifo-table {
      width: 100%;
      margin-top: 10px;
      font-size: 10px;
      border-collapse: collapse;
      background: white;
      border: 1px solid #d1d5db;
    }

    .fifo-table thead {
      background: #374151;
    }

    .fifo-table th {
      padding: 7px 10px;
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .fifo-table tbody tr {
      border-bottom: 1px solid #e5e7eb;
    }

    .fifo-table td {
      padding: 6px 10px;
      color: #374151;
    }

    .fifo-table tfoot {
      background-color: #f3f4f6;
      border-top: 2px solid #d1d5db;
      font-weight: 600;
    }

    .fifo-table tfoot td {
      padding: 7px 10px;
      color: #1f2937;
    }

    .lt-badge {
      display: inline-block;
      margin-left: 4px;
      padding: 1px 4px;
      background-color: #d1fae5;
      color: #065f46;
      border-radius: 3px;
      font-size: 7px;
      font-weight: 700;
    }

    .fifo-note {
      margin-top: 8px;
      padding: 6px 8px;
      background-color: #f9fafb;
      border-left: 3px solid #6b7280;
      font-size: 9px;
      color: #6b7280;
      line-height: 1.4;
    }

    .report-footer {
      margin-top: 40px;
      padding-top: 20px;
      border-top: 2px solid #e2e8f0;
      font-size: 12px;
      color: #718096;
    }

    .report-footer p {
      margin: 10px 0;
      line-height: 1.5;
    }

    .footer-note {
      text-align: center;
      font-weight: 600;
      color: #4a5568;
    }

    @media print {
      .section {
        page-break-inside: avoid;
      }
      
      .transaction-table {
        page-break-inside: auto;
      }
      
      .transaction-table tr {
        page-break-inside: avoid;
        page-break-after: auto;
      }
    }
  `;
};
