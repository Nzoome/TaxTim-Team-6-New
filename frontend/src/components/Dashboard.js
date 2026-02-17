import React, { useState, useEffect, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import './Dashboard.css';
import SummaryCards from './SummaryCards';
import TransactionTableEnhanced from './TransactionTableEnhanced';
import FIFOExplorer from './FIFOExplorer';
import Charts from './Charts';
import FilterPanel from './FilterPanel';
import ExportButtons from './ExportButtons';
import SuspiciousTransactionSummary from './SuspiciousTransactionSummary';
import TaxStatusSummary from './TaxStatusSummary';

function Dashboard() {
  const navigate = useNavigate();
  const [data, setData] = useState(null);
  const [selectedTaxYear, setSelectedTaxYear] = useState('all');
  const [filters, setFilters] = useState({
    asset: 'all',
    type: 'all',
    taxYear: 'all'
  });
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Load the latest transaction data from the backend
    loadTransactionData();
  }, []);

  const loadTransactionData = async () => {
    try {
      const response = await fetch('http://localhost:8000/transactions.php');
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
              // For BUY: primary currency is what you're buying (toCurrency)
              primaryCurrency = breakdown?.currency || tx.toCurrency;
            } else if (txType === 'SELL') {
              // For SELL: primary currency is what you're selling (fromCurrency)
              primaryCurrency = breakdown?.currency || tx.fromCurrency;
            } else if (txType === 'TRADE') {
              // For TRADE: primary currency is what you're trading away (fromCurrency)
              // This is the asset with the capital gain/loss event
              primaryCurrency = breakdown?.fromCurrency || tx.fromCurrency;
            } else {
              // Fallback
              primaryCurrency = breakdown?.currency || tx.toCurrency || tx.fromCurrency;
            }
            
            return {
              ...tx,
              currency: primaryCurrency,
              capitalGain: breakdown?.capitalGain || 0,
              proceeds: breakdown?.proceeds || 0,
              costBase: breakdown?.costBase || 0,
              taxYear: breakdown?.taxYear || null,
              lotsConsumed: breakdown?.lotsConsumed || null
            };
          });
          result.data.transactions = enrichedTransactions;
        }
        
        setData(result.data);
        setIsLoading(false);
      } else {
        console.error('Failed to load data');
        setIsLoading(false);
      }
    } catch (error) {
      console.error('Error loading data:', error);
      setIsLoading(false);
    }
  };

  // Filter transactions based on selected filters
  const filteredTransactions = useMemo(() => {
    if (!data || !data.transactions) return [];

    return data.transactions.filter(tx => {
      // Filter by asset - use the enriched currency field which represents the primary asset
      if (filters.asset !== 'all') {
        // The tx.currency field has been enriched to represent the primary asset
        // based on transaction type (see enrichment logic above)
        if (tx.currency !== filters.asset) {
          return false;
        }
      }

      // Filter by type - only apply if a specific type is selected (not 'all')
      if (filters.type !== 'all') {
        const txType = (tx.type || '').toUpperCase();
        const filterType = (filters.type || '').toUpperCase();
        if (txType !== filterType) return false;
      }

      // Filter by tax year - only apply if a specific year is selected (not 'all')
      if (filters.taxYear !== 'all' && tx.taxYear !== filters.taxYear) {
        return false;
      }

      return true;
    });
  }, [data, filters]);

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

    // Calculate taxable capital gain with annual exclusion
    const taxCalc = calculateTaxableGain(summary.netCapitalGain);
    summary.taxableCapitalGain = taxCalc.taxable;
    summary.annualExclusionApplied = taxCalc.exclusionApplied;
    summary.annualExclusionUsed = taxCalc.exclusionUsed;

    return summary;
  }, [data, filteredTransactions]);

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

  const handleBackToUpload = () => {
    navigate('/process');
  };

  if (isLoading) {
    return (
      <div className="dashboard-loading">
        <div className="spinner"></div>
        <p>Loading dashboard...</p>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="dashboard-empty">
        <h2>No Data Available</h2>
        <p>Please upload a transaction file to view your tax report.</p>
        <button className="btn btn-primary" onClick={handleBackToUpload}>
          📁 Upload Transactions
        </button>
      </div>
    );
  }

  return (
    <div className="dashboard">
      <header className="dashboard-header">
        <div className="header-content">
          <button className="back-btn" onClick={handleBackToUpload}>
            ← Upload New File
          </button>
          <h1>📊 Crypto Tax Dashboard</h1>
          <p className="header-subtitle">SARS-Ready Capital Gains Report</p>
        </div>
      </header>

      <div className="dashboard-container">
        {/* Red Flags / Suspicious Transaction Summary - Always show */}
        <SuspiciousTransactionSummary
          redFlags={data.red_flags || []}
          summary={data.red_flag_summary || { 
            total_flags: 0, 
            critical_count: 0, 
            high_count: 0, 
            medium_count: 0, 
            low_count: 0, 
            audit_risk_score: 0 
          }}
          auditRiskLevel={data.audit_risk_level || 'MINIMAL - No significant issues detected'}
          hasCriticalIssues={data.has_critical_issues || false}
        />

        {/* Tax Status Summary - Always show */}
        <TaxStatusSummary
          taxStatus={data.tax_status || {
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

        {/* Summary Cards */}
        <SummaryCards 
          summary={filteredSummary} 
          taxYears={taxYears}
          selectedTaxYear={filters.taxYear}
          onTaxYearChange={(year) => setFilters({...filters, taxYear: year})}
        />

        {/* Charts Section */}
        <Charts 
          transactions={filteredTransactions}
          summary={filteredSummary}
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
      </div>
    </div>
  );
}

export default Dashboard;
