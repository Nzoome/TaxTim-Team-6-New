import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import './ProcessingPage.css';
import FileUpload from './FileUpload';
import ErrorDisplay from './ErrorDisplay';
import TransactionSummary from './TransactionSummary';
import TransactionTable from './TransactionTable';
import SuspiciousTransactionSummary from './SuspiciousTransactionSummary';
import TaxStatusSummary from './TaxStatusSummary';
import { uploadTransactionFile } from '../services/api';

function ProcessingPage() {
  const navigate = useNavigate();
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [errors, setErrors] = useState({});
  const [result, setResult] = useState(null);

  const handleFileUpload = async (file) => {
    setIsLoading(true);
    setError(null);
    setErrors({});
    setResult(null);

    try {
      const response = await uploadTransactionFile(file);
      
      if (response.success) {
        setResult(response.data);
        // Show success notification and option to view dashboard
      } else {
        setError(response.error || 'An error occurred');
        setErrors(response.errors || {});
      }
    } catch (err) {
      setError(err.error || 'Failed to process file');
      setErrors(err.errors || {});
    } finally {
      setIsLoading(false);
    }
  };

  const handleReset = () => {
    setResult(null);
    setError(null);
    setErrors({});
  };

  const handleBackToHome = () => {
    navigate('/', { state: { fromProcessing: true } });
  };

  const handleViewDashboard = () => {
    navigate('/', { state: { fromProcessing: true } });
  };

  return (
    <div className="processing-page">
      <div className="container">
        <header className="app-header">
          <div className="header-content">
            <button className="back-btn" onClick={handleBackToHome}>
              ← Back to Dashboard
            </button>
            <h1>🪙 Crypto Tax Calculator</h1>
            <p className="header-subtitle">Calculate your cryptocurrency capital gains for SARS</p>
            <p className="header-partner">Powered by <strong>TaxTim</strong></p>
          </div>
        </header>

        <div className="card">
          {!result ? (
            <>
              <FileUpload onUpload={handleFileUpload} isLoading={isLoading} />
              <ErrorDisplay error={error} errors={errors} />
            </>
          ) : (
            <>
              <div className="success-message">
                <span className="success-icon">✅</span>
                <div>
                  <h3>File Processed Successfully!</h3>
                  <p>Your transactions have been parsed, validated, and sorted chronologically.</p>
                </div>
              </div>

              {/* Red Flags / Suspicious Transaction Summary */}
              {result.red_flags && result.red_flags.length > 0 && (
                <SuspiciousTransactionSummary
                  redFlags={result.red_flags}
                  summary={result.red_flag_summary}
                  auditRiskLevel={result.audit_risk_level}
                  hasCriticalIssues={result.has_critical_issues}
                />
              )}

              {/* Tax Status Summary */}
              {result.tax_status && (
                <TaxStatusSummary taxStatus={result.tax_status} />
              )}

              <TransactionSummary summary={result.summary} />
              <TransactionTable transactions={result.transactions} />

              <div className="action-buttons">
                <button className="btn btn-secondary" onClick={handleViewDashboard}>
                  📊 View Dashboard
                </button>
                <button className="btn btn-primary" onClick={handleReset}>
                  📁 Upload Another File
                </button>
              </div>

              <div className="sprint-note">
                <h4>📌 Sprint 4 Complete - Audit Ready</h4>
                <p>
                  Full FIFO calculations, capital gains computation, tax year reporting, visual analytics, 
                  and SARS-ready PDF/Excel exports are now available on the Dashboard.
                </p>
              </div>
            </>
          )}
        </div>

        <footer className="app-footer">
          <p>© 2026 TaxTim - Making Tax Easy for Everyone</p>
          <p className="footer-links">
            <a href="https://www.taxtim.com" target="_blank" rel="noopener noreferrer">Visit TaxTim</a>
            <span>•</span>
            <a href="#help">Help & Support</a>
            <span>•</span>
            <a href="#about">About This Tool</a>
          </p>
        </footer>
      </div>
    </div>
  );
}

export default ProcessingPage;
