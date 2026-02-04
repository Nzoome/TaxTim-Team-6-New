import React from 'react';
import './TransactionSummary.css';

const TransactionSummary = ({ summary }) => {
  if (!summary) return null;

  return (
    <div className="transaction-summary">
      <h2>📊 Transaction Summary</h2>
      
      <div className="summary-grid">
        <div className="summary-card">
          <div className="summary-icon">📝</div>
          <div className="summary-content">
            <h3>Total Transactions</h3>
            <p className="summary-value">{summary.total_transactions}</p>
          </div>
        </div>

        <div className="summary-card">
          <div className="summary-icon">💰</div>
          <div className="summary-content">
            <h3>Currencies</h3>
            <p className="summary-value">{summary.currencies?.length || 0}</p>
            <p className="summary-detail">
              {summary.currencies?.join(', ')}
            </p>
          </div>
        </div>

        <div className="summary-card">
          <div className="summary-icon">📅</div>
          <div className="summary-content">
            <h3>Date Range</h3>
            <p className="summary-value">
              {summary.date_range?.earliest} <br />
              to {summary.date_range?.latest}
            </p>
          </div>
        </div>

        <div className="summary-card">
          <div className="summary-icon">🔄</div>
          <div className="summary-content">
            <h3>Transaction Types</h3>
            {summary.transaction_types && (
              <div className="transaction-types">
                {Object.entries(summary.transaction_types).map(([type, count]) => (
                  <div key={type} className="type-row">
                    <span className={`badge badge-${type.toLowerCase()}`}>{type}</span>
                    <span className="type-count">{count}</span>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default TransactionSummary;
