import React, { useState } from 'react';
import { formatDate, formatCurrency, formatCrypto } from '../services/api';
import './TransactionTable.css';

const TransactionTable = ({ transactions }) => {
  const [expandedRows, setExpandedRows] = useState(new Set());
  const [expandAll, setExpandAll] = useState(false);

  if (!transactions || transactions.length === 0) {
    return null;
  }

  const toggleRow = (index) => {
    const newExpanded = new Set(expandedRows);
    if (newExpanded.has(index)) {
      newExpanded.delete(index);
    } else {
      newExpanded.add(index);
    }
    setExpandedRows(newExpanded);
  };

  const toggleExpandAll = () => {
    if (expandAll) {
      setExpandedRows(new Set());
    } else {
      setExpandedRows(new Set(transactions.map((_, idx) => idx)));
    }
    setExpandAll(!expandAll);
  };

  const getTypeBadgeClass = (type) => {
    switch (type.toUpperCase()) {
      case 'BUY':
        return 'badge-buy';
      case 'SELL':
        return 'badge-sell';
      case 'TRADE':
        return 'badge-trade';
      default:
        return '';
    }
  };

  return (
    <div className="transaction-table-container">
      <div className="table-header">
        <h2>📈 Transaction Details</h2>
        <button className="btn btn-secondary" onClick={toggleExpandAll}>
          {expandAll ? '⬆️ Collapse All' : '⬇️ Expand All'}
        </button>
      </div>

      <div className="table-wrapper">
        <table className="transaction-table">
          <thead>
            <tr>
              <th></th>
              <th>Date</th>
              <th>Type</th>
              <th>From</th>
              <th>To</th>
              <th>Price (ZAR)</th>
              <th>Fee</th>
              {transactions.some(t => t.wallet) && <th>Wallet</th>}
            </tr>
          </thead>
          <tbody>
            {transactions.map((transaction, index) => (
              <React.Fragment key={index}>
                <tr className="transaction-row" onClick={() => toggleRow(index)}>
                  <td className="expand-cell">
                    <button className="expand-btn">
                      {expandedRows.has(index) ? '▼' : '▶'}
                    </button>
                  </td>
                  <td>{formatDate(transaction.date)}</td>
                  <td>
                    <span className={`badge ${getTypeBadgeClass(transaction.type)}`}>
                      {transaction.type}
                    </span>
                  </td>
                  <td>
                    {formatCrypto(transaction.fromAmount)} {transaction.fromCurrency}
                  </td>
                  <td>
                    {formatCrypto(transaction.toAmount)} {transaction.toCurrency}
                  </td>
                  <td>{formatCurrency(transaction.price)}</td>
                  <td>{transaction.fee > 0 ? formatCurrency(transaction.fee) : '-'}</td>
                  {transactions.some(t => t.wallet) && (
                    <td>{transaction.wallet || '-'}</td>
                  )}
                </tr>
                {expandedRows.has(index) && (
                  <tr className="expanded-row">
                    <td colSpan={transactions.some(t => t.wallet) ? 8 : 7}>
                      <div className="expanded-content">
                        <h4>Transaction Details</h4>
                        <div className="detail-grid">
                          <div className="detail-item">
                            <span className="detail-label">Transaction Type:</span>
                            <span className="detail-value">{transaction.type}</span>
                          </div>
                          <div className="detail-item">
                            <span className="detail-label">Date & Time:</span>
                            <span className="detail-value">{formatDate(transaction.date)}</span>
                          </div>
                          <div className="detail-item">
                            <span className="detail-label">From Currency:</span>
                            <span className="detail-value">{transaction.fromCurrency}</span>
                          </div>
                          <div className="detail-item">
                            <span className="detail-label">From Amount:</span>
                            <span className="detail-value">{formatCrypto(transaction.fromAmount)}</span>
                          </div>
                          <div className="detail-item">
                            <span className="detail-label">To Currency:</span>
                            <span className="detail-value">{transaction.toCurrency}</span>
                          </div>
                          <div className="detail-item">
                            <span className="detail-label">To Amount:</span>
                            <span className="detail-value">{formatCrypto(transaction.toAmount)}</span>
                          </div>
                          <div className="detail-item">
                            <span className="detail-label">Unit Price (ZAR):</span>
                            <span className="detail-value">{formatCurrency(transaction.price)}</span>
                          </div>
                          <div className="detail-item">
                            <span className="detail-label">Transaction Fee:</span>
                            <span className="detail-value">
                              {transaction.fee > 0 ? formatCurrency(transaction.fee) : 'No fee'}
                            </span>
                          </div>
                          {transaction.wallet && (
                            <div className="detail-item">
                              <span className="detail-label">Wallet:</span>
                              <span className="detail-value">{transaction.wallet}</span>
                            </div>
                          )}
                        </div>
                        
                        <div className="calculation-note">
                          <p><strong>📝 Note:</strong> Detailed FIFO calculations and capital gains will be available in the next sprint. Currently showing parsed and validated transaction data.</p>
                        </div>
                      </div>
                    </td>
                  </tr>
                )}
              </React.Fragment>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default TransactionTable;
