import React, { useState } from 'react';
import './SuspiciousTransactionSummary.css';

function SuspiciousTransactionSummary({ redFlags, summary, auditRiskLevel, hasCriticalIssues }) {
  const [isExpanded, setIsExpanded] = useState(false);
  const [severityFilter, setSeverityFilter] = useState('all');

  if (!redFlags || redFlags.length === 0) {
    return (
      <div className="suspicious-summary no-issues">
        <div className="summary-header success">
          <span className="icon">✅</span>
          <h3>No Red Flags Detected</h3>
          <span className="risk-badge minimal">MINIMAL RISK</span>
        </div>
        <p className="summary-text">All transactions passed validation checks.</p>
      </div>
    );
  }

  // Get risk level styling
  const getRiskLevelClass = () => {
    if (summary.audit_risk_score >= 75) return 'very-high';
    if (summary.audit_risk_score >= 50) return 'high';
    if (summary.audit_risk_score >= 25) return 'medium';
    return 'low';
  };

  // Filter red flags by severity
  const filteredFlags = severityFilter === 'all' 
    ? redFlags 
    : redFlags.filter(flag => flag.severity === severityFilter);

  // Get severity icon
  const getSeverityIcon = (severity) => {
    switch(severity) {
      case 'CRITICAL': return '🚨';
      case 'HIGH': return '⚠️';
      case 'MEDIUM': return '⚡';
      case 'LOW': return 'ℹ️';
      default: return '🔔';
    }
  };

  // Get severity class
  const getSeverityClass = (severity) => {
    return `severity-${severity.toLowerCase()}`;
  };

  return (
    <div className={`suspicious-summary ${getRiskLevelClass()}`}>
      {/* Header */}
      <div className="summary-header">
        <span className="icon">🚩</span>
        <div className="header-content">
          <h3>
            Transaction Red Flags Detected
            {hasCriticalIssues && <span className="critical-badge">CRITICAL</span>}
          </h3>
          <p className="audit-risk">
            <strong>Audit Risk Level:</strong> {auditRiskLevel}
          </p>
        </div>
        <button 
          className="expand-btn"
          onClick={() => setIsExpanded(!isExpanded)}
        >
          {isExpanded ? '▼ Hide Details' : '▶ Show Details'}
        </button>
      </div>

      {/* Summary Stats */}
      <div className="summary-stats">
        <div className="stat-card">
          <div className="stat-value">{summary.total_flags}</div>
          <div className="stat-label">Total Flags</div>
        </div>
        <div className={`stat-card ${summary.critical_count > 0 ? 'critical' : ''}`}>
          <div className="stat-value">{summary.critical_count}</div>
          <div className="stat-label">Critical</div>
        </div>
        <div className={`stat-card ${summary.high_count > 0 ? 'high' : ''}`}>
          <div className="stat-value">{summary.high_count}</div>
          <div className="stat-label">High</div>
        </div>
        <div className={`stat-card ${summary.medium_count > 0 ? 'medium' : ''}`}>
          <div className="stat-value">{summary.medium_count}</div>
          <div className="stat-label">Medium</div>
        </div>
        <div className={`stat-card ${summary.low_count > 0 ? 'low' : ''}`}>
          <div className="stat-value">{summary.low_count}</div>
          <div className="stat-label">Low</div>
        </div>
        <div className="stat-card risk-score">
          <div className="stat-value">{summary.audit_risk_score}/100</div>
          <div className="stat-label">Risk Score</div>
        </div>
      </div>

      {/* Expanded Details */}
      {isExpanded && (
        <div className="details-section">
          {/* Severity Filter */}
          <div className="filter-section">
            <label>Filter by Severity:</label>
            <div className="severity-filters">
              <button 
                className={severityFilter === 'all' ? 'active' : ''}
                onClick={() => setSeverityFilter('all')}
              >
                All ({redFlags.length})
              </button>
              {summary.critical_count > 0 && (
                <button 
                  className={`severity-critical ${severityFilter === 'CRITICAL' ? 'active' : ''}`}
                  onClick={() => setSeverityFilter('CRITICAL')}
                >
                  🚨 Critical ({summary.critical_count})
                </button>
              )}
              {summary.high_count > 0 && (
                <button 
                  className={`severity-high ${severityFilter === 'HIGH' ? 'active' : ''}`}
                  onClick={() => setSeverityFilter('HIGH')}
                >
                  ⚠️ High ({summary.high_count})
                </button>
              )}
              {summary.medium_count > 0 && (
                <button 
                  className={`severity-medium ${severityFilter === 'MEDIUM' ? 'active' : ''}`}
                  onClick={() => setSeverityFilter('MEDIUM')}
                >
                  ⚡ Medium ({summary.medium_count})
                </button>
              )}
              {summary.low_count > 0 && (
                <button 
                  className={`severity-low ${severityFilter === 'LOW' ? 'active' : ''}`}
                  onClick={() => setSeverityFilter('LOW')}
                >
                  ℹ️ Low ({summary.low_count})
                </button>
              )}
            </div>
          </div>

          {/* Flagged Transactions List */}
          <div className="flagged-transactions">
            <h4>Flagged Transactions ({filteredFlags.length})</h4>
            <div className="flags-list">
              {filteredFlags.map((flag, index) => (
                <div key={index} className={`flag-item ${getSeverityClass(flag.severity)}`}>
                  <div className="flag-header">
                    <span className="flag-icon">{getSeverityIcon(flag.severity)}</span>
                    <span className="flag-severity">{flag.severity}</span>
                    <span className="flag-code">{flag.code}</span>
                    <span className="flag-line">Line {flag.line_number}</span>
                  </div>
                  <div className="flag-message">{flag.message}</div>
                  <div className="flag-transaction-details">
                    <div className="flag-transaction-row">
                      <strong>Type:</strong> <span>{flag.transaction.type}</span>
                    </div>
                    <div className="flag-transaction-row">
                      <strong>From:</strong> <span>{flag.transaction.from}</span>
                    </div>
                    <div className="flag-transaction-row">
                      <strong>To:</strong> <span>{flag.transaction.to}</span>
                    </div>
                    <div className="flag-transaction-row">
                      <strong>Price per Unit:</strong> <span>R{typeof flag.transaction.price === 'number' ? flag.transaction.price.toFixed(2) : flag.transaction.price}</span>
                    </div>
                    <div className="flag-transaction-row">
                      <strong>Date:</strong> <span>{flag.transaction.date}</span>
                    </div>
                  </div>
                  <div className="currency-note">
                    💰 <em>Note: All amounts are in Rands (ZAR). Price shows ZAR value per unit.</em>
                  </div>
                  {flag.metadata && Object.keys(flag.metadata).length > 0 && (
                    <div className="flag-metadata">
                      {Object.entries(flag.metadata).map(([key, value]) => (
                        <span key={key} className="metadata-item">
                          <strong>{key}:</strong> {typeof value === 'number' ? value.toFixed(2) : value}
                        </span>
                      ))}
                    </div>
                  )}
                  <div className="flag-timestamp">{flag.timestamp}</div>
                </div>
              ))}
            </div>
          </div>

          {/* Recommendations */}
          <div className="recommendations">
            <h4>📋 Recommendations</h4>
            <ul>
              {hasCriticalIssues && (
                <li className="critical">
                  <strong>Critical issues detected:</strong> Please review and correct incomplete or invalid transaction data before submitting to SARS.
                </li>
              )}
              {summary.high_count > 0 && (
                <li className="high">
                  <strong>High-risk items:</strong> Review large transactions, duplicates, and negative balances. These may trigger SARS audit attention.
                </li>
              )}
              {summary.medium_count > 0 && (
                <li className="medium">
                  <strong>Medium-risk items:</strong> Check for wash trading patterns and excessive fees. Consider consulting a tax professional.
                </li>
              )}
              {summary.audit_risk_score >= 50 && (
                <li className="warning">
                  <strong>High audit risk score:</strong> We recommend professional tax advice before filing with SARS.
                </li>
              )}
            </ul>
          </div>
        </div>
      )}
    </div>
  );
}

export default SuspiciousTransactionSummary;
