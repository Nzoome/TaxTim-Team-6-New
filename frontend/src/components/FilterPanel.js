import React from 'react';
import './FilterPanel.css';

const FilterPanel = ({ filters, onFilterChange, assets, taxYears }) => {
  const handleFilterUpdate = (key, value) => {
    onFilterChange({
      ...filters,
      [key]: value
    });
  };

  const clearFilters = () => {
    onFilterChange({
      asset: 'all',
      type: 'all',
      taxYear: 'all'
    });
  };

  const hasActiveFilters = filters.asset !== 'all' || filters.type !== 'all' || filters.taxYear !== 'all';

  return (
    <div className="filter-panel">
      <div className="filter-header">
        <h3>🔍 Filter Transactions</h3>
        {hasActiveFilters && (
          <button className="btn btn-clear" onClick={clearFilters}>
            Clear All Filters
          </button>
        )}
      </div>

      <div className="filter-grid">
        {/* Asset Filter */}
        <div className="filter-group">
          <label htmlFor="asset-filter">
            <span className="filter-icon">🪙</span>
            Asset/Currency
          </label>
          <select
            id="asset-filter"
            value={filters.asset}
            onChange={(e) => handleFilterUpdate('asset', e.target.value)}
            className="filter-select"
          >
            <option value="all">All Assets</option>
            {assets.map(asset => (
              <option key={asset} value={asset}>{asset}</option>
            ))}
          </select>
        </div>

        {/* Type Filter */}
        <div className="filter-group">
          <label htmlFor="type-filter">
            <span className="filter-icon">🔄</span>
            Transaction Type
          </label>
          <select
            id="type-filter"
            value={filters.type}
            onChange={(e) => handleFilterUpdate('type', e.target.value)}
            className="filter-select"
          >
            <option value="all">All Types</option>
            <option value="BUY">BUY</option>
            <option value="SELL">SELL</option>
            <option value="TRADE">TRADE</option>
            <option value="TRADE_SELL">TRADE (SELL)</option>
            <option value="TRADE_BUY">TRADE (BUY)</option>
          </select>
        </div>

        {/* Tax Year Filter */}
        <div className="filter-group">
          <label htmlFor="tax-year-filter">
            <span className="filter-icon">📅</span>
            Tax Year
          </label>
          <select
            id="tax-year-filter"
            value={filters.taxYear}
            onChange={(e) => handleFilterUpdate('taxYear', e.target.value)}
            className="filter-select"
          >
            <option value="all">All Tax Years</option>
            {taxYears.map(year => (
              <option key={year} value={year}>{year}</option>
            ))}
          </select>
        </div>
      </div>

      {hasActiveFilters && (
        <div className="active-filters">
          <span className="active-filters-label">Active Filters:</span>
          <div className="filter-badges">
            {filters.asset !== 'all' && (
              <span className="filter-badge">
                Asset: {filters.asset}
                <button 
                  className="remove-filter" 
                  onClick={() => handleFilterUpdate('asset', 'all')}
                  aria-label="Remove asset filter"
                >
                  ×
                </button>
              </span>
            )}
            {filters.type !== 'all' && (
              <span className="filter-badge">
                Type: {filters.type}
                <button 
                  className="remove-filter" 
                  onClick={() => handleFilterUpdate('type', 'all')}
                  aria-label="Remove type filter"
                >
                  ×
                </button>
              </span>
            )}
            {filters.taxYear !== 'all' && (
              <span className="filter-badge">
                Tax Year: {filters.taxYear}
                <button 
                  className="remove-filter" 
                  onClick={() => handleFilterUpdate('taxYear', 'all')}
                  aria-label="Remove tax year filter"
                >
                  ×
                </button>
              </span>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default FilterPanel;
