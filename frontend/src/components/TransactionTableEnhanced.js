import React, { useState } from 'react';
import './TransactionTableEnhanced.css';

const TransactionTableEnhanced = ({ transactions }) => {
  const [expandedRows, setExpandedRows] = useState(new Set());
  const [sortConfig, setSortConfig] = useState({ key: 'date', direction: 'desc' });

  if (!transactions || transactions.length === 0) {
    return (
      <div className="no-transactions">
        <p>No transactions to display with current filters.</p>
      </div>
    );
  }

  const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '-';
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
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

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
    if (expandedRows.size > 0) {
      setExpandedRows(new Set());
    } else {
      setExpandedRows(new Set(transactions.map((_, idx) => idx)));
    }
  };

  const handleSort = (key) => {
    let direction = 'asc';
    if (sortConfig.key === key && sortConfig.direction === 'asc') {
      direction = 'desc';
    }
    setSortConfig({ key, direction });
  };

  const sortedTransactions = [...transactions].sort((a, b) => {
    const aVal = a[sortConfig.key];
    const bVal = b[sortConfig.key];
    
    if (aVal === bVal) return 0;
    
    const comparison = aVal < bVal ? -1 : 1;
    return sortConfig.direction === 'asc' ? comparison : -comparison;
  });

  const getTypeBadgeClass = (type) => {
    switch (type?.toUpperCase()) {
      case 'BUY':
        return 'badge-buy';
      case 'SELL':
        return 'badge-sell';
      case 'TRADE':
      case 'TRADE_SELL':
      case 'TRADE_BUY':
        return 'badge-trade';
      default:
        return '';
    }
  };

  const getSortIcon = (key) => {
    if (sortConfig.key !== key) return '⇅';
    return sortConfig.direction === 'asc' ? '↑' : '↓';
  };

  return (
    <div className="transaction-table-enhanced">
      <div className="table-header-section">
        <h2>Transaction Details</h2>
        <div className="table-actions">
          <button className="btn btn-secondary btn-sm" onClick={toggleExpandAll}>
            {expandedRows.size > 0 ? '⬆ Collapse All' : '⬇ Expand All'}
          </button>
          <span className="transaction-count">{transactions.length} transactions</span>
        </div>
      </div>

      <div className="table-wrapper">
        <table className="transaction-table">
          <thead>
            <tr>
              <th className="expand-header"></th>
              <th className="sortable" onClick={() => handleSort('date')}>
                Date {getSortIcon('date')}
              </th>
              <th className="sortable" onClick={() => handleSort('type')}>
                Type {getSortIcon('type')}
              </th>
              <th>Currency</th>
              <th>Amount</th>
              <th className="sortable" onClick={() => handleSort('proceeds')}>
                Proceeds {getSortIcon('proceeds')}
              </th>
              <th className="sortable" onClick={() => handleSort('costBase')}>
                Cost Base {getSortIcon('costBase')}
              </th>
              <th className="sortable" onClick={() => handleSort('capitalGain')}>
                Gain/Loss {getSortIcon('capitalGain')}
              </th>
              <th className="sortable" onClick={() => handleSort('taxYear')}>
                Tax Year {getSortIcon('taxYear')}
              </th>
            </tr>
          </thead>
          <tbody>
            {sortedTransactions.map((tx, index) => (
              <React.Fragment key={index}>
                <tr 
                  className={`transaction-row ${expandedRows.has(index) ? 'expanded' : ''}`}
                  onClick={() => toggleRow(index)}
                >
                  <td className="expand-cell">
                    <button className="expand-btn">
                      {expandedRows.has(index) ? '▼' : '▶'}
                    </button>
                  </td>
                  <td className="date-cell">{formatDate(tx.date)}</td>
                  <td>
                    <span className={`badge ${getTypeBadgeClass(tx.type)}`}>
                      {tx.type}
                    </span>
                  </td>
                  <td className="currency-cell">{tx.currency}</td>
                  <td className="amount-cell">{formatCrypto(tx.amount)}</td>
                  <td className="proceeds-cell">
                    {(() => {
                      const txType = (tx.type || '').toUpperCase();
                      if (txType === 'BUY' && tx.totalCost > 0) {
                        return formatCurrency(tx.totalCost);
                      }
                      return tx.proceeds > 0 ? formatCurrency(tx.proceeds) : '-';
                    })()}
                  </td>
                  <td className="cost-cell">
                    {(() => {
                      const txType = (tx.type || '').toUpperCase();
                      if (txType === 'BUY' && tx.costPerUnit > 0) {
                        return formatCurrency(tx.costPerUnit);
                      }
                      return tx.costBase > 0 ? formatCurrency(tx.costBase) : '-';
                    })()}
                  </td>
                  <td className={`gain-cell ${
                    tx.capitalGain > 0 ? 'positive' : 
                    tx.capitalGain < 0 ? 'negative' : ''
                  }`}>
                    {(() => {
                      const txType = (tx.type || '').toUpperCase();
                      if (txType === 'BUY') {
                        return '-';
                      }
                      return tx.capitalGain !== 0 ? formatCurrency(tx.capitalGain) : '-';
                    })()}
                  </td>
                  <td className="tax-year-cell">{tx.taxYear || '-'}</td>
                </tr>

                {expandedRows.has(index) && (
                  <tr className="expanded-detail-row">
                    <td colSpan="9">
                      <div className="expanded-content">
                        <div className="detail-sections">
                          {/* Basic Details */}
                          <div className="detail-section">
                            <h4>Transaction Details</h4>
                            <div className="detail-grid">
                              <div className="detail-item">
                                <span className="detail-label">Line Number:</span>
                                <span className="detail-value">{tx.lineNumber || '-'}</span>
                              </div>
                              <div className="detail-item">
                                <span className="detail-label">Wallet:</span>
                                <span className="detail-value">{tx.wallet || 'Default'}</span>
                              </div>
                              <div className="detail-item">
                                <span className="detail-label">Fee:</span>
                                <span className="detail-value">{formatCurrency(tx.fee || 0)}</span>
                              </div>
                              {tx.totalCost && (
                                <div className="detail-item">
                                  <span className="detail-label">Total Cost:</span>
                                  <span className="detail-value">{formatCurrency(tx.totalCost)}</span>
                                </div>
                              )}
                              {tx.costPerUnit && (
                                <div className="detail-item">
                                  <span className="detail-label">Cost Per Unit:</span>
                                  <span className="detail-value">{formatCurrency(tx.costPerUnit)}</span>
                                </div>
                              )}
                            </div>
                          </div>

                          {/* FIFO Lots Consumed (for SELL/TRADE) */}
                          {tx.lotsConsumed && tx.lotsConsumed.length > 0 && (
                            <div className="detail-section fifo-section">
                              <h4>📦 FIFO Lots Consumed</h4>
                              <p className="fifo-intro">
                                This sale consumed {tx.lotsConsumed.length} lot{tx.lotsConsumed.length !== 1 ? 's' : ''} from your purchase history (oldest first):
                              </p>
                              <div className="fifo-table-wrapper">
                                <table className="fifo-table">
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
                                    {tx.lotsConsumed.map((lot, idx) => (
                                      <tr key={idx} className="fifo-row">
                                        <td className="date-cell">{formatDate(lot.purchaseDate)}</td>
                                        <td className="amount-cell">{formatCrypto(lot.amountConsumed)}</td>
                                        <td>{formatCurrency(lot.costPerUnit)}</td>
                                        <td className="cost-cell">{formatCurrency(lot.costBase)}</td>
                                        <td className="age-cell">
                                          {lot.ageInDays !== null && lot.ageInDays !== undefined ? (
                                            <span className={lot.ageInDays >= 365 ? 'long-term' : 'short-term'}>
                                              {lot.ageInDays} 
                                              {lot.ageInDays >= 365 && <span className="term-badge">LT</span>}
                                            </span>
                                          ) : '-'}
                                        </td>
                                      </tr>
                                    ))}
                                  </tbody>
                                  <tfoot>
                                    <tr className="fifo-total">
                                      <td colSpan="3"><strong>Total Cost Base:</strong></td>
                                      <td className="cost-cell">
                                        <strong>
                                          {formatCurrency(
                                            tx.lotsConsumed.reduce((sum, lot) => sum + lot.costBase, 0)
                                          )}
                                        </strong>
                                      </td>
                                      <td></td>
                                    </tr>
                                  </tfoot>
                                </table>
                              </div>
                              <div className="fifo-note">
                                <strong>FIFO Method:</strong> First-In-First-Out means the oldest coins you purchased 
                                are considered sold first, which affects your holding period and cost basis calculations.
                              </div>
                            </div>
                          )}

                          {/* Capital Gain Calculation (for SELL/TRADE) */}
                          {tx.capitalGain !== null && tx.capitalGain !== undefined && (
                            <div className="detail-section calculation-section">
                              <h4>Capital Gain Calculation</h4>
                              <div className="calculation-breakdown">
                                <div className="calc-row">
                                  <span className="calc-label">Proceeds (after fees):</span>
                                  <span className="calc-value">{formatCurrency(tx.proceeds)}</span>
                                </div>
                                <div className="calc-row">
                                  <span className="calc-label">Cost Base (FIFO):</span>
                                  <span className="calc-value">- {formatCurrency(tx.costBase)}</span>
                                </div>
                                <div className="calc-row calc-total">
                                  <span className="calc-label"><strong>Capital Gain/Loss:</strong></span>
                                  <span className={`calc-value ${tx.capitalGain >= 0 ? 'positive' : 'negative'}`}>
                                    <strong>{formatCurrency(tx.capitalGain)}</strong>
                                  </span>
                                </div>
                                <div className="calc-row calc-taxable">
                                  <span className="calc-label">Taxable (40% inclusion):</span>
                                  <span className="calc-value">
                                    {formatCurrency(tx.capitalGain >= 0 ? tx.capitalGain * 0.4 : 0)}
                                  </span>
                                </div>
                              </div>
                            </div>
                          )}
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

export default TransactionTableEnhanced;
