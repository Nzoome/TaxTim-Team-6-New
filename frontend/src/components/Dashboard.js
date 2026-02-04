import React, { useState, useEffect, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import './Dashboard.css';
import SummaryCards from './SummaryCards';
import TransactionTableEnhanced from './TransactionTableEnhanced';
import FIFOExplorer from './FIFOExplorer';
import Charts from './Charts';
import FilterPanel from './FilterPanel';
import ExportButtons from './ExportButtons';

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
            return {
              ...tx,
              currency: breakdown?.currency || tx.toCurrency || tx.fromCurrency,
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
      // Filter by asset
      if (filters.asset !== 'all') {
        const currencies = [tx.currency, tx.fromCurrency, tx.toCurrency].filter(Boolean);
        if (!currencies.includes(filters.asset)) return false;
      }

      // Filter by type
      if (filters.type !== 'all' && tx.type !== filters.type) {
        return false;
      }

      // Filter by tax year
      if (filters.taxYear !== 'all' && tx.taxYear !== filters.taxYear) {
        return false;
      }

      return true;
    });
  }, [data, filters]);

  // Calculate filtered summary
  const filteredSummary = useMemo(() => {
    // Use analytics data from backend if available
    if (data && data.analytics) {
      return {
        totalProceeds: data.analytics.total_proceeds || 0,
        totalCostBase: data.analytics.total_cost_base || 0,
        totalCapitalGain: data.analytics.total_capital_gain || 0,
        totalCapitalLoss: data.analytics.total_capital_loss || 0,
        netCapitalGain: data.analytics.capital_gain || 0,
        taxableCapitalGain: (data.analytics.capital_gain || 0) > 0 ? (data.analytics.capital_gain || 0) * 0.4 : 0,
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

    // Calculate taxable capital gain (40% inclusion rate for individuals in SA)
    summary.taxableCapitalGain = summary.netCapitalGain > 0 ? summary.netCapitalGain * 0.4 : 0;

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

        {/* Sprint 4 Note */}
        <div className="sprint-note">
          <h4>✅ Sprint 4 Complete - Audit Ready</h4>
          <p>
            Full FIFO traceability, tax calculations, and SARS-ready exports are now available.
            All capital gains calculations follow South African tax law (40% inclusion rate).
          </p>
        </div>
      </div>
    </div>
  );
}

export default Dashboard;
