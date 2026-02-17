import React from 'react';
import './SummaryCards.css';

const SummaryCards = ({ summary, taxYears, selectedTaxYear, onTaxYearChange }) => {
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-ZA', {
      style: 'currency',
      currency: 'ZAR',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount);
  };

  const formatPercentage = (value) => {
    return `${(value * 100).toFixed(1)}%`;
  };

  return (
    <>
      <div className="summary-header">
        <h2>Tax Summary</h2>
        <div className="tax-year-selector">
          <label htmlFor="taxYear">Tax Year:</label>
          <select 
            id="taxYear" 
            value={selectedTaxYear} 
            onChange={(e) => onTaxYearChange(e.target.value)}
            className="tax-year-select"
          >
            <option value="all">All Years</option>
            {taxYears.map(year => (
              <option key={year} value={year}>{year}</option>
            ))}
          </select>
        </div>
      </div>

      <div className="summary-cards">
        {/* SARS Information Box */}
        <div className="sars-info-box">
          <div className="sars-info-header">
            <h4>SARS Capital Gains Tax Information</h4>
          </div>
          <div className="sars-info-content">
            <p>
              <strong>Inclusion Rate:</strong> 40% of net capital gain is included in taxable income for individuals.
            </p>
            <p>
              <strong>Annual Exclusion:</strong> R40,000 per year {summary.annualExclusionUsed ? 
                `(R${summary.annualExclusionApplied?.toLocaleString('en-ZA', {minimumFractionDigits: 2, maximumFractionDigits: 2})} applied in this calculation)` : 
                '(not applied in this calculation)'}.
            </p>
            <p>
              <strong>Tax Year:</strong> March 1 to February 28/29 of the following year.
            </p>
          </div>
        </div>

        <div className="cards-grid">
        {/* Total Capital Gains */}
        <div className="summary-card card-primary">
          <div className="card-content">
            <h3>Total Capital Gains</h3>
            <p className="card-value positive">{formatCurrency(summary.totalCapitalGain)}</p>
            <p className="card-detail">From all disposals</p>
          </div>
        </div>

        {/* Total Capital Losses */}
        <div className="summary-card card-secondary">
          <div className="card-content">
            <h3>Total Capital Losses</h3>
            <p className="card-value negative">{formatCurrency(summary.totalCapitalLoss)}</p>
            <p className="card-detail">Offset against gains</p>
          </div>
        </div>

        {/* Net Capital Gain */}
        <div className={`summary-card ${summary.netCapitalGain >= 0 ? 'card-success' : 'card-warning'}`}>
          <div className="card-content">
            <h3>Net Capital Gain</h3>
            <p className={`card-value ${summary.netCapitalGain >= 0 ? 'positive' : 'negative'}`}>
              {formatCurrency(summary.netCapitalGain)}
            </p>
            <p className="card-detail">Gains minus losses</p>
          </div>
        </div>

        {/* After Annual Exclusion - Only show if exclusion was applied */}
        {summary.annualExclusionUsed && summary.netCapitalGain > 0 && (
          <div className="summary-card card-info">
            <div className="card-content">
              <h3>After Annual Exclusion</h3>
              <p className="card-value">
                {formatCurrency(Math.max(0, summary.netCapitalGain - (summary.annualExclusionApplied || 0)))}
              </p>
              <p className="card-detail">
                Net gain minus R{(summary.annualExclusionApplied || 0).toLocaleString('en-ZA', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
              </p>
            </div>
          </div>
        )}

        {/* Taxable Capital Gain */}
        <div className="summary-card card-highlight">
          <div className="card-content">
            <h3>Taxable Capital Gain</h3>
            <p className="card-value highlight">{formatCurrency(summary.taxableCapitalGain)}</p>
            <p className="card-detail">40% inclusion rate (SARS)</p>
          </div>
        </div>

        {/* Total Proceeds */}
        <div className="summary-card card-info">
          <div className="card-content">
            <h3>Total Proceeds</h3>
            <p className="card-value">{formatCurrency(summary.totalProceeds)}</p>
            <p className="card-detail">From all sales</p>
          </div>
        </div>

        {/* Total Cost Base */}
        <div className="summary-card card-info">
          <div className="card-content">
            <h3>Total Cost Base</h3>
            <p className="card-value">{formatCurrency(summary.totalCostBase)}</p>
            <p className="card-detail">FIFO acquisition cost</p>
          </div>
        </div>

        {/* Transactions Processed */}
        <div className="summary-card card-neutral">
          <div className="card-content">
            <h3>Transactions</h3>
            <p className="card-value">{summary.transactionsProcessed}</p>
            <p className="card-detail">Total processed</p>
          </div>
        </div>

        {/* Effective Tax Rate Indicator */}
        {summary.netCapitalGain > 0 && (
          <div className="summary-card card-tax">
            <div className="card-content">
              <h3>CGT Inclusion Rate</h3>
              <p className="card-value">40%</p>
              <p className="card-detail">For individuals (SARS)</p>
            </div>
          </div>
        )}
        </div>
      </div>
    </>
  );
};

export default SummaryCards;
