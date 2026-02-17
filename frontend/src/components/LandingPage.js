import React, { useState, useEffect, useMemo, useRef } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';
import { getTransactionData, uploadTransactionFile } from '../services/api';
import { generatePDF } from '../services/pdfGenerator';
import { generateExcel } from '../services/excelGenerator';
import './LandingPage.css';
import './Dashboard.css';
import SummaryCards from './SummaryCards';
import TransactionTableEnhanced from './TransactionTableEnhanced';
import Charts from './Charts';
import FilterPanel from './FilterPanel';
import ExportButtons from './ExportButtons';
import SuspiciousTransactionSummary from './SuspiciousTransactionSummary';
import TaxStatusSummary from './TaxStatusSummary';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';

const LandingPage = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const fileInputRef = useRef(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(true);
  const [data, setData] = useState(null);
  const [filters, setFilters] = useState({
    asset: 'all',
    type: 'all',
    taxYear: 'all'
  });
  const [uploadState, setUploadState] = useState({
    isUploading: false,
    error: null,
    errors: {},
    success: false
  });
  const [isGeneratingPDF, setIsGeneratingPDF] = useState(false);
  const [isGeneratingExcel, setIsGeneratingExcel] = useState(false);

  useEffect(() => {
    // Clear cache on initial page load (not on navigation)
    const clearCacheAndFetch = async () => {
      if (!location.state?.fromProcessing) {
        try {
          await fetch(`${API_BASE_URL}/clear_cache.php`, { method: 'POST' });
        } catch (err) {
          console.error('Error clearing cache:', err);
        }
      }
      fetchData();
    };
    
    clearCacheAndFetch();
  }, [location]); // Refetch when location changes (e.g., navigating back)

  const fetchData = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${API_BASE_URL}/transactions.php`);
      const result = await response.json();
  
      if (result.success) {
        // Enrich transactions with FIFO breakdown data
        if (result.data.transactions && result.data.analytics && result.data.analytics.fifo_breakdowns) {
          const enrichedTransactions = result.data.transactions.map((tx, index) => {
            const breakdown = result.data.analytics.fifo_breakdowns[index];
  
            // Determine the primary currency based on transaction type
            let primaryCurrency;
            const txType = (tx.type || '').toUpperCase();
            if (txType === 'BUY') {
              primaryCurrency = breakdown?.currency || tx.toCurrency;
            } else if (txType === 'SELL') {
              primaryCurrency = breakdown?.currency || tx.fromCurrency;
            } else if (txType === 'TRADE') {
              primaryCurrency = breakdown?.fromCurrency || tx.fromCurrency;
            } else {
              primaryCurrency = breakdown?.currency || tx.toCurrency || tx.fromCurrency;
            }
  
            return {
              ...tx,
              currency: primaryCurrency,
              amount: breakdown?.amount || tx.toAmount || tx.fromAmount || 0,
              capitalGain: breakdown?.capitalGain || 0,
              proceeds: breakdown?.proceeds || 0,
              costBase: breakdown?.costBase || 0,
              totalCost: breakdown?.totalCost || 0,
              costPerUnit: breakdown?.costPerUnit || 0,
              taxYear: breakdown?.taxYear || null,
              lotsConsumed: breakdown?.lotsConsumed || null
            };
          });
          result.data.transactions = enrichedTransactions;
        }
  
        setData(result.data);
      } else {
        console.error('Failed to load transaction data:', result.error);
        setData(null);
      }
    } catch (error) {
      console.error('Error fetching transaction data:', error);
      setData(null);
    } finally {
      setLoading(false);
    }
  };

  const transactions = data?.transactions || [];
  
  // Filter transactions based on dashboard filters
  const filteredTransactions = useMemo(() => {
    if (!data || !data.transactions) return [];

    return data.transactions.filter(tx => {
      // Search filter
      if (searchTerm) {
        const search = searchTerm.toLowerCase();
        const txString = JSON.stringify(tx).toLowerCase();
        if (!txString.includes(search)) return false;
      }

      // Filter by asset
      if (filters.asset !== 'all') {
        if (tx.currency !== filters.asset) {
          return false;
        }
      }

      // Filter by type
      if (filters.type !== 'all') {
        const txType = (tx.type || '').toUpperCase();
        const filterType = (filters.type || '').toUpperCase();
        if (txType !== filterType) return false;
      }

      // Filter by tax year
      if (filters.taxYear !== 'all' && tx.taxYear !== filters.taxYear) {
        return false;
      }

      return true;
    });
  }, [data, filters, searchTerm]);

  // Helper function to calculate taxable gain with annual exclusion
  const calculateTaxableGain = (netGain) => {
    if (netGain <= 0) return { taxable: 0, exclusionApplied: 0, exclusionUsed: false };
    
    const ANNUAL_EXCLUSION = 40000;
    const INCLUSION_RATE = 0.4;
    
    if (netGain > ANNUAL_EXCLUSION) {
      const afterExclusion = netGain - ANNUAL_EXCLUSION;
      return {
        taxable: afterExclusion * INCLUSION_RATE,
        exclusionApplied: ANNUAL_EXCLUSION,
        exclusionUsed: true
      };
    } else {
      return {
        taxable: 0,
        exclusionApplied: netGain,
        exclusionUsed: true
      };
    }
  };

  // Calculate filtered summary
  const filteredSummary = useMemo(() => {
    // Use analytics data from backend if available
    if (data && data.analytics) {
      const taxCalc = calculateTaxableGain(data.analytics.capital_gain || 0);
      return {
        totalProceeds: data.analytics.total_proceeds || 0,
        totalCostBase: data.analytics.total_cost_base || 0,
        totalCapitalGain: data.analytics.total_capital_gain || 0,
        totalCapitalLoss: data.analytics.total_capital_loss || 0,
        netCapitalGain: data.analytics.capital_gain || 0,
        taxableCapitalGain: taxCalc.taxable,
        annualExclusionApplied: taxCalc.exclusionApplied,
        annualExclusionUsed: taxCalc.exclusionUsed,
        transactionsProcessed: data.analytics.transactions_processed || 0
      };
    }

    if (!filteredTransactions.length) {
      return {
        totalProceeds: 0,
        totalCostBase: 0,
        totalCapitalGain: 0,
        totalCapitalLoss: 0,
        netCapitalGain: 0,
        taxableCapitalGain: 0,
        annualExclusionApplied: 0,
        annualExclusionUsed: false,
        transactionsProcessed: 0
      };
    }

    const summary = filteredTransactions.reduce((acc, tx) => {
      if (tx.type === 'SELL' || tx.type === 'TRADE_SELL') {
        acc.totalProceeds += tx.proceeds || 0;
        acc.totalCostBase += tx.costBase || 0;
        
        const gain = tx.capitalGain || 0;
        if (gain >= 0) {
          acc.totalCapitalGain += gain;
        } else {
          acc.totalCapitalLoss += Math.abs(gain);
        }
        acc.netCapitalGain += gain;
      }
      acc.transactionsProcessed++;
      return acc;
    }, {
      totalProceeds: 0,
      totalCostBase: 0,
      totalCapitalGain: 0,
      totalCapitalLoss: 0,
      netCapitalGain: 0,
      transactionsProcessed: 0
    });

    const taxCalc = calculateTaxableGain(summary.netCapitalGain);
    summary.taxableCapitalGain = taxCalc.taxable;
    summary.annualExclusionApplied = taxCalc.exclusionApplied;
    summary.annualExclusionUsed = taxCalc.exclusionUsed;

    return summary;
  }, [data, filteredTransactions]);

  // Calculate unfiltered summary for charts (all assets, all transactions)
  const unfilteredSummary = useMemo(() => {
    // Use analytics data from backend if available
    if (data && data.analytics) {
      const taxCalc = calculateTaxableGain(data.analytics.capital_gain || 0);
      return {
        totalProceeds: data.analytics.total_proceeds || 0,
        totalCostBase: data.analytics.total_cost_base || 0,
        totalCapitalGain: data.analytics.total_capital_gain || 0,
        totalCapitalLoss: data.analytics.total_capital_loss || 0,
        netCapitalGain: data.analytics.capital_gain || 0,
        taxableCapitalGain: taxCalc.taxable,
        annualExclusionApplied: taxCalc.exclusionApplied,
        annualExclusionUsed: taxCalc.exclusionUsed,
        transactionsProcessed: data.analytics.transactions_processed || 0
      };
    }

    if (!transactions.length) {
      return {
        totalProceeds: 0,
        totalCostBase: 0,
        totalCapitalGain: 0,
        totalCapitalLoss: 0,
        netCapitalGain: 0,
        taxableCapitalGain: 0,
        annualExclusionApplied: 0,
        annualExclusionUsed: false,
        transactionsProcessed: 0
      };
    }

    const summary = transactions.reduce((acc, tx) => {
      if (tx.type === 'SELL' || tx.type === 'TRADE_SELL') {
        acc.totalProceeds += tx.proceeds || 0;
        acc.totalCostBase += tx.costBase || 0;
        
        const gain = tx.capitalGain || 0;
        if (gain >= 0) {
          acc.totalCapitalGain += gain;
        } else {
          acc.totalCapitalLoss += Math.abs(gain);
        }
        acc.netCapitalGain += gain;
      }
      acc.transactionsProcessed++;
      return acc;
    }, {
      totalProceeds: 0,
      totalCostBase: 0,
      totalCapitalGain: 0,
      totalCapitalLoss: 0,
      netCapitalGain: 0,
      transactionsProcessed: 0
    });

    const taxCalc = calculateTaxableGain(summary.netCapitalGain);
    summary.taxableCapitalGain = taxCalc.taxable;
    summary.annualExclusionApplied = taxCalc.exclusionApplied;
    summary.annualExclusionUsed = taxCalc.exclusionUsed;

    return summary;
  }, [data, transactions]);

  // Extract unique tax years from data
  const taxYears = useMemo(() => {
    if (!data || !data.transactions) return [];
    
    const years = new Set();
    data.transactions.forEach(tx => {
      if (tx.taxYear) years.add(tx.taxYear);
    });
    
    return Array.from(years).sort();
  }, [data]);

  // Extract unique assets
  const assets = useMemo(() => {
    if (!data || !data.transactions) return [];
    
    const assetSet = new Set();
    data.transactions.forEach(tx => {
      if (tx.currency) assetSet.add(tx.currency);
      if (tx.fromCurrency) assetSet.add(tx.fromCurrency);
      if (tx.toCurrency) assetSet.add(tx.toCurrency);
    });
    
    return Array.from(assetSet).sort();
  }, [data]);
  
  const handleFilterChange = (newFilters) => {
    setFilters(newFilters);
  };
  
  // Get unique currencies for filter dropdown (for sidebar)
  const currencies = assets;

  // Check if we have any transactions
  const hasTransactions = data && data.transactions && data.transactions.length > 0;

  const handleUploadClick = () => {
    // Trigger file input click instead of navigating
    if (fileInputRef.current) {
      fileInputRef.current.click();
    }
  };

  const handleFileChange = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate file type
    const validTypes = [
      'text/csv',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    if (!validTypes.includes(file.type) && 
        !file.name.endsWith('.csv') && 
        !file.name.endsWith('.xlsx') && 
        !file.name.endsWith('.xls')) {
      setUploadState({
        isUploading: false,
        error: 'Please upload a CSV or XLSX file',
        errors: {},
        success: false
      });
      return;
    }

    // Start upload
    setUploadState({
      isUploading: true,
      error: null,
      errors: {},
      success: false
    });

    try {
      const response = await uploadTransactionFile(file);
      
      if (response.success) {
        // Keep showing the processing message for 1.5 seconds before showing success
        setTimeout(async () => {
          setUploadState({
            isUploading: false,
            error: null,
            errors: {},
            success: true
          });
          // Refresh data after successful upload
          await fetchData();
          // Clear success message after 3 seconds
          setTimeout(() => {
            setUploadState(prev => ({ ...prev, success: false }));
          }, 3000);
        }, 1500);
      } else {
        setUploadState({
          isUploading: false,
          error: response.error || 'An error occurred during upload',
          errors: response.errors || {},
          success: false
        });
      }
    } catch (err) {
      setUploadState({
        isUploading: false,
        error: err.error || 'Failed to process file',
        errors: err.errors || {},
        success: false
      });
    }

    // Reset file input
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleResetFilters = () => {
    setFilters({
      asset: 'all',
      type: 'all',
      taxYear: 'all'
    });
    setSearchTerm('');
  };

  const handlePDFDownload = async () => {
    setIsGeneratingPDF(true);
    try {
      await generatePDF(filteredTransactions, filteredSummary, filters);
    } catch (error) {
      console.error('Error generating PDF:', error);
      alert('Failed to generate PDF. Please try again.');
    } finally {
      setIsGeneratingPDF(false);
    }
  };

  const handleExcelDownload = async () => {
    setIsGeneratingExcel(true);
    try {
      await generateExcel(filteredTransactions, filteredSummary, filters);
    } catch (error) {
      console.error('Error generating Excel:', error);
      alert('Failed to generate Excel file. Please try again.');
    } finally {
      setIsGeneratingExcel(false);
    }
  };

  const handleRestart = async () => {
    try {
      // Clear the cache
      await fetch('http://localhost:8000/clear_cache.php', { method: 'POST' });
      // Clear session storage
      sessionStorage.removeItem('justProcessedFile');
      // Reload the page
      window.location.reload();
    } catch (err) {
      console.error('Error restarting:', err);
      // Reload anyway
      window.location.reload();
    }
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
      {/* Hidden file input */}
      <input
        ref={fileInputRef}
        type="file"
        accept=".csv,.xlsx,.xls"
        onChange={handleFileChange}
        style={{ display: 'none' }}
      />

      {/* Upload Modal/Overlay */}
      {(uploadState.isUploading || uploadState.error || uploadState.success) && (
        <div className="upload-overlay">
          <div className="upload-modal">
            {uploadState.isUploading && (
              <>
                <div className="spinner"></div>
                <h3>Processing File...</h3>
                <p>Please wait while we process your transaction file.</p>
              </>
            )}
            {uploadState.error && (
              <>
                <div className="error-icon">❌</div>
                <h3>Upload Error</h3>
                <p className="error-message">{uploadState.error}</p>
                {uploadState.errors && Object.keys(uploadState.errors).length > 0 && (() => {
                  // Aggregate all errors across rows and deduplicate
                  const allErrors = new Set();
                  const structuralErrors = [];
                  
                  Object.entries(uploadState.errors).forEach(([key, messages]) => {
                    const messageArray = Array.isArray(messages) ? messages : [messages];
                    
                    // Separate structural errors (like missing columns) from row errors
                    if (key === 'structure' || key === 'general') {
                      structuralErrors.push(...messageArray);
                    } else {
                      // Add row-specific errors to the set (automatically deduplicates)
                      messageArray.forEach(msg => allErrors.add(msg));
                    }
                  });
                  
                  const uniqueErrors = [...allErrors];
                  
                  return (
                    <div className="error-details">
                      <h4>Validation Errors:</h4>
                      {structuralErrors.length > 0 && (
                        <div className="structural-errors">
                          <strong>File Structure Issues:</strong>
                          <ul className="error-list">
                            {structuralErrors.map((error, idx) => (
                              <li key={idx}>{error}</li>
                            ))}
                          </ul>
                        </div>
                      )}
                      {uniqueErrors.length > 0 && (
                        <div className="data-errors">
                          <strong>Data Validation Issues:</strong>
                          <ul className="error-list">
                            {uniqueErrors.map((error, idx) => (
                              <li key={idx}>{error}</li>
                            ))}
                          </ul>
                        </div>
                      )}
                    </div>
                  );
                })()}
                <button 
                  className="btn-primary" 
                  onClick={() => setUploadState({ isUploading: false, error: null, errors: {}, success: false })}
                >
                  Close
                </button>
              </>
            )}
            {uploadState.success && (
              <>
                <div className="success-icon">✅</div>
                <h3>File Processed Successfully!</h3>
                <p>Your transactions have been imported and calculated.</p>
              </>
            )}
          </div>
        </div>
      )}

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
              value={filters.asset}
              onChange={(e) => setFilters({...filters, asset: e.target.value})}
            >
              <option value="all">All Assets</option>
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
                checked={filters.type === 'all' || filters.type === 'BUY'}
                onChange={() => setFilters({...filters, type: filters.type === 'BUY' ? 'all' : 'BUY'})}
              />
              <span>Buy</span>
            </label>
            <label className="filter-checkbox">
              <input 
                type="checkbox" 
                checked={filters.type === 'all' || filters.type === 'SELL'}
                onChange={() => setFilters({...filters, type: filters.type === 'SELL' ? 'all' : 'SELL'})}
              />
              <span>Sell</span>
            </label>
            <label className="filter-checkbox">
              <input 
                type="checkbox" 
                checked={filters.type === 'all' || filters.type === 'TRADE'}
                onChange={() => setFilters({...filters, type: filters.type === 'TRADE' ? 'all' : 'TRADE'})}
              />
              <span>Trade</span>
            </label>
          </div>

          <button className="reset-filters-btn" onClick={handleResetFilters}>
            Reset Filters
          </button>

          {/* Download Buttons - Show only when data is loaded */}
          {data && data.transactions && data.transactions.length > 0 && (
            <div className="sidebar-downloads">
              <button 
                className="sidebar-download-btn sidebar-download-pdf" 
                onClick={handlePDFDownload}
                disabled={isGeneratingPDF}
              >
                {isGeneratingPDF ? (
                  <>
                    <span className="spinner-small"></span>
                    Generating...
                  </>
                ) : (
                  <>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M7 18H17V16H7V18Z" fill="currentColor"/>
                      <path d="M17 14H7V12H17V14Z" fill="currentColor"/>
                      <path d="M7 10H11V8H7V10Z" fill="currentColor"/>
                      <path fillRule="evenodd" clipRule="evenodd" d="M6 2C4.34315 2 3 3.34315 3 5V19C3 20.6569 4.34315 22 6 22H18C19.6569 22 21 20.6569 21 19V9C21 5.13401 17.866 2 14 2H6ZM6 4H13V9H19V19C19 19.5523 18.5523 20 18 20H6C5.44772 20 5 19.5523 5 19V5C5 4.44772 5.44772 4 6 4ZM15 4.10002C16.6113 4.4271 17.9413 5.52906 18.584 7H15V4.10002Z" fill="currentColor"/>
                    </svg>
                    Download PDF
                  </>
                )}
              </button>
              
              <button 
                className="sidebar-download-btn sidebar-download-excel" 
                onClick={handleExcelDownload}
                disabled={isGeneratingExcel}
              >
                {isGeneratingExcel ? (
                  <>
                    <span className="spinner-small"></span>
                    Generating...
                  </>
                ) : (
                  <>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M14 2H6C4.9 2 4 2.9 4 4V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V8L14 2ZM18 20H6V4H13V9H18V20Z" fill="currentColor"/>
                      <path d="M8 15.5L10 18L12 15.5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                      <path d="M12 12L14 14.5L16 12" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                    </svg>
                    Download Excel
                  </>
                )}
              </button>
            </div>
          )}
        </div>

        <div className="add-file-section">
          <button className="add-file-btn" onClick={handleUploadClick}>
            <span>+</span> Add new file
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <main className="main-content">
        <div className="dashboard-header">
          <div className="dashboard-title">
            <h1>Crypto Tax Dashboard</h1>
            <p className="date-range">SARS-Ready Capital Gains Report</p>
          </div>
          <button className="restart-btn" onClick={handleRestart} title="Clear data and restart">
            Restart
          </button>
        </div>

        {/* Red Flag Summary - Always shown when we have transaction data */}
        {hasTransactions && (
          <SuspiciousTransactionSummary 
            redFlags={data?.red_flags || []}
            summary={data?.red_flag_summary || {
              total_flags: 0,
              critical_count: 0,
              high_count: 0,
              medium_count: 0,
              low_count: 0,
              audit_risk_score: 0,
              audit_risk_level: 'MINIMAL'
            }}
          />
        )}

        {/* Tax Status Summary - Always shown when we have transaction data */}
        {hasTransactions && (
          <TaxStatusSummary 
            taxStatus={data?.tax_status || {
              non_taxable_events: [],
              taxable_events: [],
              summary: {
                total_events: 0,
                non_taxable_count: 0,
                taxable_count: 0,
                buy_count: 0,
                transfer_count: 0,
                sell_count: 0,
                trade_count: 0
              },
              tax_obligation_exists: false
            }}
          />
        )}

        {/* Summary Cards or Empty State */}
        {!hasTransactions ? (
          <div className="empty-dashboard-state">
            <div className="empty-state-icon"></div>
            <h2>No Transaction Data</h2>
            <p>Upload and process a transaction file to view your tax summary and analytics.</p>
            <button className="upload-cta-btn" onClick={handleUploadClick}>
              Upload Transaction File
            </button>
          </div>
        ) : (
          <>
            <SummaryCards 
              summary={filteredSummary} 
              taxYears={taxYears}
              selectedTaxYear={filters.taxYear}
              onTaxYearChange={(year) => setFilters({...filters, taxYear: year})}
            />

            {/* Charts Section */}
            <Charts 
              transactions={transactions}
              summary={unfilteredSummary}
            />

            {/* Filter Panel */}
            <FilterPanel
              filters={filters}
              onFilterChange={handleFilterChange}
              assets={assets}
              taxYears={taxYears}
            />

            {/* Enhanced Transaction Table with FIFO Details */}
            <TransactionTableEnhanced 
              transactions={filteredTransactions}
            />

            {/* Export Buttons */}
            <ExportButtons 
              transactions={filteredTransactions}
              summary={filteredSummary}
              filters={filters}
            />
          </>
        )}
      </main>
    </div>
  );
};

export default LandingPage;
