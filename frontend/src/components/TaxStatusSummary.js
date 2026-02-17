import React, { useState } from 'react';
import './TaxStatusSummary.css';

function TaxStatusSummary({ taxStatus }) {
  const [isExpanded, setIsExpanded] = useState(false);
  const [eventFilter, setEventFilter] = useState('all'); // 'all', 'taxable', 'non-taxable'

  if (!taxStatus || !taxStatus.summary) {
    return null;
  }

  const summary = taxStatus.summary;
  const hasTaxObligation = taxStatus.tax_obligation_exists;

  // Get filtered events
  const getFilteredEvents = () => {
    if (eventFilter === 'taxable') {
      return taxStatus.taxable_events || [];
    } else if (eventFilter === 'non-taxable') {
      return taxStatus.non_taxable_events || [];
    } else {
      return [
        ...(taxStatus.non_taxable_events || []),
        ...(taxStatus.taxable_events || [])
      ];
    }
  };

  const filteredEvents = getFilteredEvents();

  return (
    <div className={`tax-status-summary ${hasTaxObligation ? 'has-obligation' : 'no-obligation'}`}>
      {/* Header */}
      <div className="tax-status-header">
        <div className="header-content">
          <h3>
            {hasTaxObligation ? 'Tax Obligation Detected' : 'No Tax Obligation'}
          </h3>
          <p className="tax-obligation-note">
            {hasTaxObligation ? (
              <>Based on South African tax law, you have <strong>{summary.taxable_count}</strong> taxable event(s) that must be reported to SARS.</>
            ) : (
              <>All your transactions are non-taxable events (buying and holding). No SARS reporting required yet.</>
            )}
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
      <div className="tax-status-stats">
        <div className="stat-card total">
          <div className="stat-value">{summary.total_transactions}</div>
          <div className="stat-label">Total Transactions</div>
        </div>
        <div className={`stat-card non-taxable ${summary.non_taxable_count > 0 ? 'active' : ''}`}>
          <div className="stat-value">{summary.non_taxable_count}</div>
          <div className="stat-label">Non-Taxable</div>
        </div>
        <div className={`stat-card taxable ${summary.taxable_count > 0 ? 'active' : ''}`}>
          <div className="stat-value">{summary.taxable_count}</div>
          <div className="stat-label">Taxable Events</div>
        </div>
      </div>

      {/* Expanded Details */}
      {isExpanded && (
        <div className="tax-status-details">
          {/* Event Type Breakdown */}
          <div className="breakdown-section">
            <h4>Event Breakdown</h4>
            <div className="breakdown-grid">
              <div className="breakdown-item non-taxable">
                <span className="breakdown-label">Buying with ZAR:</span>
                <span className="breakdown-value">{summary.buy_with_zar}</span>
              </div>
              <div className="breakdown-item non-taxable">
                <span className="breakdown-label">Internal Transfers:</span>
                <span className="breakdown-value">{summary.internal_transfers}</span>
              </div>
              <div className="breakdown-item taxable">
                <span className="breakdown-label">Sells (to ZAR):</span>
                <span className="breakdown-value">{summary.sells}</span>
              </div>
              <div className="breakdown-item taxable">
                <span className="breakdown-label">Trades (crypto-to-crypto):</span>
                <span className="breakdown-value">{summary.trades}</span>
              </div>
              <div className="breakdown-item taxable">
                <span className="breakdown-label">Other Taxable:</span>
                <span className="breakdown-value">{summary.other_taxable}</span>
              </div>
            </div>
          </div>

          {/* Event Filter */}
          <div className="filter-section">
            <label>Filter Events:</label>
            <div className="event-filters">
              <button 
                className={eventFilter === 'all' ? 'active' : ''}
                onClick={() => setEventFilter('all')}
              >
                All ({summary.total_transactions})
              </button>
              <button 
                className={`non-taxable-btn ${eventFilter === 'non-taxable' ? 'active' : ''}`}
                onClick={() => setEventFilter('non-taxable')}
              >
                Non-Taxable ({summary.non_taxable_count})
              </button>
              <button 
                className={`taxable-btn ${eventFilter === 'taxable' ? 'active' : ''}`}
                onClick={() => setEventFilter('taxable')}
              >
                Taxable ({summary.taxable_count})
              </button>
            </div>
          </div>

          {/* Event List */}
          <div className="event-list">
            <h4>Transaction Events ({filteredEvents.length})</h4>
            <div className="events-container">
              {filteredEvents.map((event, index) => (
                <div 
                  key={index} 
                  className={`event-item ${event.is_taxable ? 'taxable' : 'non-taxable'}`}
                >
                  <div className="event-header">
                    <span className="event-status">
                      {event.is_taxable ? 'TAXABLE' : 'NON-TAXABLE'}
                    </span>
                    <span className="event-type">{event.type}</span>
                    <span className="event-line">Line {event.line_number}</span>
                  </div>
                  <div className="event-transaction">
                    <strong>Transaction:</strong> {event.from} → {event.to}
                  </div>
                  <div className="event-reason">
                    <strong>
                      {event.is_taxable ? 'Tax Type:' : 'Reason:'}
                    </strong>
                    {' '}
                    {event.is_taxable ? event.tax_type : event.reason}
                  </div>
                  <div className="event-explanation">
                    {event.explanation}
                  </div>
                  {event.note && (
                    <div className="event-note">
                      <em>{event.note}</em>
                    </div>
                  )}
                  {event.sars_requirement && (
                    <div className="sars-requirement">
                      <strong>SARS:</strong> {event.sars_requirement}
                    </div>
                  )}
                  <div className="event-date">{event.date}</div>
                </div>
              ))}
            </div>
          </div>

          {/* Tax Guidance */}
          <div className="tax-guidance">
            <h4>Tax Guidance</h4>
            {hasTaxObligation ? (
              <div className="guidance-content taxable">
                <p><strong>You have taxable events that must be reported to SARS:</strong></p>
                <ul>
                  <li>Capital Gains Tax (CGT) applies to crypto disposals</li>
                  <li>R40,000 annual CGT exclusion available</li>
                  <li>40% inclusion rate on gains above exclusion</li>
                  <li>Losses can offset gains within the same tax year</li>
                  <li>Keep all transaction records and exchange statements</li>
                </ul>
                <p className="professional-advice">
                  <strong>Recommendation:</strong> Consult with a tax professional familiar with cryptocurrency taxation in South Africa.
                </p>
              </div>
            ) : (
              <div className="guidance-content non-taxable">
                <p><strong>No immediate tax obligation:</strong></p>
                <ul>
                  <li>Buying and holding crypto is not taxable</li>
                  <li>Internal transfers between your wallets are not taxable</li>
                  <li>Keep records for future cost-basis calculations</li>
                  <li>Tax obligation arises when you sell, trade, or spend crypto</li>
                </ul>
                <p className="record-keeping">
                  <strong>Important:</strong> Maintain accurate records of all purchases for future cost-basis calculations.
                </p>
              </div>
            )}
          </div>

          {/* What's Taxable Reference */}
          <div className="taxable-reference">
            <h4>Quick Reference: What's Taxable in South Africa</h4>
            <div className="reference-grid">
              <div className="reference-column">
                <h5>Non-Taxable Events</h5>
                <ul>
                  <li>Buying with ZAR</li>
                  <li>Holding crypto</li>
                  <li>Internal wallet transfers</li>
                </ul>
              </div>
              <div className="reference-column">
                <h5>Taxable Events</h5>
                <ul>
                  <li>Selling for ZAR</li>
                  <li>Crypto-to-crypto trades</li>
                  <li>Spending crypto</li>
                  <li>Gifting crypto (>R100k)</li>
                  <li>Mining/Staking/Airdrops</li>
                  <li>Getting paid in crypto</li>
                  <li>NFT transactions</li>
                  <li>DeFi activities</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default TaxStatusSummary;
