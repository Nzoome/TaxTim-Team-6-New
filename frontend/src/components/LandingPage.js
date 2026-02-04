import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';
import { getTransactionData } from '../services/api';
import './LandingPage.css';

const LandingPage = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedAsset, setSelectedAsset] = useState('All Assets');
  const [transactionFilters, setTransactionFilters] = useState({
    buy: true,
    sell: true,
    transfer: true
  });
  const [loading, setLoading] = useState(true);
  const [data, setData] = useState(null);

  useEffect(() => {
    fetchData();
  }, [location]); // Refetch when location changes (e.g., navigating back)

  const fetchData = async () => {
    try {
      setLoading(true);
      const response = await getTransactionData();
      if (response.success) {
        setData(response.data);
      }
    } catch (err) {
      console.error('Error fetching data:', err);
    } finally {
      setLoading(false);
    }
  };

  // Use real data or fallback to sample data
  const analytics = data?.analytics || {
    total_proceeds: 0,
    total_cost_base: 0,
    capital_gain: 0,
    transaction_history: [],
    transaction_breakdown: []
  };

  const transactions = data?.transactions || [];
  
  // Filter transactions based on selected filters
  const getFilteredTransactions = () => {
    return transactions.filter(transaction => {
      const type = transaction.type.toUpperCase();
      if (type === 'BUY' && !transactionFilters.buy) return false;
      if (type === 'SELL' && !transactionFilters.sell) return false;
      if (type === 'TRADE' && !transactionFilters.transfer) return false;
      
      if (selectedAsset !== 'All Assets') {
        const matchesAsset = transaction.from_currency === selectedAsset || 
                            transaction.to_currency === selectedAsset;
        if (!matchesAsset) return false;
      }
      
      return true;
    });
  };

  const filteredTransactions = getFilteredTransactions();
  
  // Get unique currencies for filter dropdown
  const currencies = data?.summary?.currencies || [];

  // Prepare display data
  const displayTransactions = filteredTransactions.slice(0, 5).map(transaction => {
    const type = transaction.type.toUpperCase();
    const isSell = type === 'SELL';
    const isBuy = type === 'BUY';
    const asset = isSell ? transaction.from_currency : transaction.to_currency;
    
    return {
      date: new Date(transaction.date).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit' 
      }),
      asset: asset,
      type: type === 'TRADE' ? 'Transfer' : (isSell ? 'Sell' : 'Buy'),
      proceeds: isSell ? `R ${parseFloat(transaction.to_amount).toLocaleString('en-ZA')}` : '-',
      costBasis: isBuy ? `R ${parseFloat(transaction.from_amount).toLocaleString('en-ZA')}` : '-',
      gain: '-',
      color: isSell ? '#ef4444' : (isBuy ? '#8b5cf6' : '#06b6d4')
    };
  });

  const handleUploadClick = () => {
    navigate('/process');
  };

  const handleFilterChange = (filterType) => {
    setTransactionFilters(prev => ({
      ...prev,
      [filterType]: !prev[filterType]
    }));
  };

  const handleResetFilters = () => {
    setSelectedAsset('All Assets');
    setTransactionFilters({
      buy: true,
      sell: true,
      transfer: true
    });
  };

  const formatCurrency = (amount) => {
    return `R ${parseFloat(amount).toLocaleString('en-ZA', { 
      minimumFractionDigits: 0, 
      maximumFractionDigits: 0 
    })}`;
  };

  if (loading) {
    return (
      <div className="landing-page">
        <div className="loading-container">
          <div className="spinner"></div>
          <p>Loading dashboard...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="landing-page">
      {/* Sidebar */}
      <aside className="sidebar">
        <div className="logo">
          <div className="logo-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="3" width="18" height="18" rx="2" fill="currentColor"/>
              <path d="M8 8h8M8 12h8M8 16h5" stroke="#1a1f2e" strokeWidth="2" strokeLinecap="round"/>
            </svg>
          </div>
          <span className="logo-text">TaxTim</span>
        </div>

        <div className="search-box">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="11" cy="11" r="8" stroke="currentColor" strokeWidth="2"/>
            <path d="M21 21l-4.35-4.35" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
          </svg>
          <input 
            type="text" 
            placeholder="Search..." 
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>

        <div className="upload-section">
          <div className="file-status">
            <div className="upload-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 15V3m0 0L8 7m4-4l4 4M2 17l.621 2.485A2 2 0 004.561 21h14.878a2 2 0 001.94-1.515L22 17" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
            </div>
            <div className="file-info">
              <div className="checkmark">✓</div>
              <span>crypto_transactions_20...</span>
            </div>
          </div>
          <button className="upload-btn" onClick={handleUploadClick}>
            Upload / calculate
          </button>
        </div>

        <div className="filters-section">
          <h3>Filters</h3>
          
          <div className="filter-group">
            <h4>Asset</h4>
            <select 
              className="filter-select"
              value={selectedAsset}
              onChange={(e) => setSelectedAsset(e.target.value)}
            >
              <option>All Assets</option>
              {currencies.map(currency => (
                <option key={currency} value={currency}>{currency}</option>
              ))}
            </select>
          </div>

          <div className="filter-group">
            <h4>Transaction Type</h4>
            <label className="filter-checkbox">
              <input 
                type="checkbox" 
                checked={transactionFilters.buy}
                onChange={() => handleFilterChange('buy')}
              />
              <span>Buy</span>
            </label>
            <label className="filter-checkbox">
              <input 
                type="checkbox" 
                checked={transactionFilters.sell}
                onChange={() => handleFilterChange('sell')}
              />
              <span>Sell</span>
            </label>
            <label className="filter-checkbox">
              <input 
                type="checkbox" 
                checked={transactionFilters.transfer}
                onChange={() => handleFilterChange('transfer')}
              />
              <span>Transfer</span>
            </label>
          </div>

          <button className="reset-filters-btn" onClick={handleResetFilters}>
            Reset Filters
          </button>
        </div>

        <div className="add-file-section">
          <button className="add-file-btn">
            <span>+</span> Add new file
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <main className="main-content">
        <div className="dashboard-header">
          <div>
            <h1>Tax Year 2026</h1>
            <p className="date-range">January 1, 2026 - December 31, 2026</p>
          </div>
          <button className="download-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5m0 0l5-5m-5 5V3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
            </svg>
            Download audit report
          </button>
        </div>

        {/* Summary Cards */}
        <div className="summary-cards">
          <div className="summary-card">
            <h3>Total Proceeds</h3>
            <p className="amount proceeds">{formatCurrency(analytics.total_proceeds)}</p>
          </div>
          <div className="summary-card">
            <h3>Total Cost Base</h3>
            <p className="amount cost-base">{formatCurrency(analytics.total_cost_base)}</p>
          </div>
          <div className="summary-card">
            <h3>Capital Gain</h3>
            <p className="amount capital-gain">{formatCurrency(analytics.capital_gain)}</p>
          </div>
        </div>

        {/* Charts Section */}
        <div className="charts-section">
          <div className="chart-card">
            <h3>Transaction History</h3>
            {analytics.transaction_history.length > 0 ? (
              <ResponsiveContainer width="100%" height={250}>
                <BarChart data={analytics.transaction_history}>
                  <CartesianGrid strokeDasharray="3 3" stroke="#2a3142" />
                  <XAxis dataKey="name" stroke="#64748b" />
                  <YAxis stroke="#64748b" />
                  <Tooltip 
                    contentStyle={{ backgroundColor: '#1e293b', border: 'none', borderRadius: '8px' }}
                    labelStyle={{ color: '#e2e8f0' }}
                  />
                  <Bar dataKey="value" fill="#8b5cf6" radius={[8, 8, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            ) : (
              <div className="no-data">No transaction data available</div>
            )}
          </div>

          <div className="chart-card">
            <h3>Transaction Breakdown</h3>
            {analytics.transaction_breakdown.length > 0 ? (
              <>
                <div className="pie-chart-container">
                  <ResponsiveContainer width="100%" height={250}>
                    <PieChart>
                      <Pie
                        data={analytics.transaction_breakdown}
                        cx="50%"
                        cy="50%"
                        innerRadius={60}
                        outerRadius={90}
                        fill="#8884d8"
                        dataKey="value"
                        label={false}
                      >
                        {analytics.transaction_breakdown.map((entry, index) => (
                          <Cell key={`cell-${index}`} fill={entry.color} />
                        ))}
                      </Pie>
                    </PieChart>
                  </ResponsiveContainer>
                  {analytics.transaction_breakdown.length > 0 && (
                    <div className="pie-center-label">
                      <div className="pie-percentage">{analytics.transaction_breakdown[0].value}%</div>
                      <div className="pie-label">Primary transactions</div>
                    </div>
                  )}
                </div>
                <div className="chart-legend">
                  {analytics.transaction_breakdown.map((item, index) => (
                    <div className="legend-item" key={index}>
                      <div className="legend-dot" style={{ backgroundColor: item.color }}></div>
                      <span>{item.name}</span>
                    </div>
                  ))}
                </div>
              </>
            ) : (
              <div className="no-data">No transaction data available</div>
            )}
          </div>
        </div>

        {/* Transaction Table */}
        <div className="transaction-table-section">
          <div className="table-header">
            <h3>Transaction History</h3>
            <div className="table-controls">
              <span>Filter at</span>
              <select className="table-filter-select" value={selectedAsset} onChange={(e) => setSelectedAsset(e.target.value)}>
                <option>All Assets</option>
                {currencies.map(currency => (
                  <option key={currency} value={currency}>{currency}</option>
                ))}
              </select>
              <button className="filter-icon-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3 4h18M6 8h12M9 12h6M11 16h2" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                </svg>
              </button>
            </div>
          </div>

          {displayTransactions.length > 0 ? (
            <>
              <table className="transaction-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Asset</th>
                    <th>Type</th>
                    <th>Proceeds</th>
                    <th>Cost Basis</th>
                    <th>Gain / Loss</th>
                  </tr>
                </thead>
                <tbody>
                  {displayTransactions.map((transaction, index) => (
                    <tr key={index}>
                      <td>{transaction.date}</td>
                      <td>
                        <span className="asset-badge" style={{ color: transaction.color }}>
                          ● {transaction.asset}
                        </span>
                      </td>
                      <td>
                        <span className={`type-badge ${transaction.type.toLowerCase()}`}>
                          ● {transaction.type}
                        </span>
                      </td>
                      <td>{transaction.proceeds}</td>
                      <td>{transaction.costBasis}</td>
                      <td className={transaction.gain !== '-' ? 'gain-positive' : ''}>
                        {transaction.gain}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>

              <div className="pagination">
                <button className="pagination-btn">{'<'}</button>
                <button className="pagination-btn active">1</button>
                <button className="pagination-btn">2</button>
                <button className="pagination-btn">3</button>
                <button className="pagination-btn">...</button>
                <button className="pagination-btn">{'>'}</button>
              </div>
            </>
          ) : (
            <div className="no-data">
              <p>No transactions match your current filters</p>
              <button className="btn-upload-first" onClick={handleUploadClick}>
                Upload your first transaction file
              </button>
            </div>
          )}
        </div>
      </main>
    </div>
  );
};

export default LandingPage;
