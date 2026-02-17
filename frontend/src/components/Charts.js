import React from 'react';
import { BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import './Charts.css';

const Charts = ({ transactions, summary }) => {
  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-ZA', {
      style: 'currency',
      currency: 'ZAR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value);
  };

  // Prepare data for gains/losses by asset
  const assetData = React.useMemo(() => {
    const assetMap = new Map();

    transactions.forEach(tx => {
      const txType = (tx.type || '').toUpperCase();
      
      // Include SELL and TRADE transactions (both generate capital gains/losses)
      if (txType === 'SELL' || txType === 'TRADE') {
        // For SELL: use currency field or fromCurrency
        // For TRADE: use currency field or fromCurrency (the asset being disposed)
        const currency = tx.currency || tx.fromCurrency;
        const gain = tx.capitalGain || 0;

        if (currency && !assetMap.has(currency)) {
          assetMap.set(currency, {
            asset: currency,
            gains: 0,
            losses: 0,
            netGain: 0
          });
        }

        if (currency) {
          const data = assetMap.get(currency);
          if (gain >= 0) {
            data.gains += gain;
          } else {
            data.losses += Math.abs(gain);
          }
          data.netGain += gain;
        }
      }
    });

    return Array.from(assetMap.values()).sort((a, b) => b.netGain - a.netGain);
  }, [transactions]);

  // Prepare data for gains/losses by tax year
  const taxYearData = React.useMemo(() => {
    const yearMap = new Map();

    transactions.forEach(tx => {
      const txType = (tx.type || '').toUpperCase();
      
      // Include SELL and TRADE transactions (both generate capital gains/losses)
      if (txType === 'SELL' || txType === 'TRADE') {
        const year = tx.taxYear || 'Unknown';
        const gain = tx.capitalGain || 0;

        if (!yearMap.has(year)) {
          yearMap.set(year, {
            taxYear: year,
            gains: 0,
            losses: 0,
            netGain: 0
          });
        }

        const data = yearMap.get(year);
        if (gain >= 0) {
          data.gains += gain;
        } else {
          data.losses += Math.abs(gain);
        }
        data.netGain += gain;
      }
    });

    return Array.from(yearMap.values()).sort((a, b) => {
      if (a.taxYear === 'Unknown') return 1;
      if (b.taxYear === 'Unknown') return -1;
      return a.taxYear.localeCompare(b.taxYear);
    });
  }, [transactions]);

  // Prepare donut chart data for overall distribution
  const distributionData = [
    { name: 'Capital Gains', value: summary.totalCapitalGain, color: '#48bb78' },
    { name: 'Capital Losses', value: summary.totalCapitalLoss, color: '#f56565' }
  ].filter(item => item.value > 0);

  // Prepare transaction type distribution
  const typeData = React.useMemo(() => {
    const typeMap = new Map();

    transactions.forEach(tx => {
      const type = tx.type;
      if (!typeMap.has(type)) {
        typeMap.set(type, { type, count: 0 });
      }
      typeMap.get(type).count++;
    });

    return Array.from(typeMap.values());
  }, [transactions]);

  const COLORS = ['#48bb78', '#f56565', '#4299e1', '#ed8936', '#9f7aea', '#38b2ac'];

  const CustomTooltip = ({ active, payload, label }) => {
    if (active && payload && payload.length) {
      return (
        <div className="custom-tooltip">
          <p className="tooltip-label">{label}</p>
          {payload.map((entry, index) => (
            <p key={index} style={{ color: entry.color }}>
              {entry.name}: {formatCurrency(entry.value)}
            </p>
          ))}
        </div>
      );
    }
    return null;
  };

  const CustomPieTooltip = ({ active, payload }) => {
    if (active && payload && payload.length) {
      return (
        <div className="custom-tooltip">
          <p className="tooltip-label">{payload[0].name}</p>
          <p style={{ color: payload[0].payload.color }}>
            {formatCurrency(payload[0].value)}
          </p>
        </div>
      );
    }
    return null;
  };

  if (!transactions || transactions.length === 0) {
    return null;
  }

  return (
    <>
      <h2 className="charts-header">Visual Analytics</h2>
      
      <div className="charts-section">
        {/* Legend and Info Box */}
        <div className="charts-legend-box">
          <div className="legend-header">
            <h4>Chart Legend & Insights</h4>
          </div>
          <div className="legend-content">
            <div className="legend-category">
              <h5>Capital Gains/Losses</h5>
              <div className="legend-item">
                <span className="legend-dot" style={{ backgroundColor: '#48bb78' }}></span>
                <span>Gains: {formatCurrency(summary.totalCapitalGain)}</span>
              </div>
              <div className="legend-item">
                <span className="legend-dot" style={{ backgroundColor: '#f56565' }}></span>
                <span>Losses: {formatCurrency(summary.totalCapitalLoss)}</span>
              </div>
            </div>
            
            <div className="legend-category">
              <h5>Tax Year Analysis</h5>
              <div className="legend-item">
                <span className="legend-dot" style={{ backgroundColor: '#4299e1' }}></span>
                <span>Year Gains</span>
              </div>
              <div className="legend-item">
                <span className="legend-dot" style={{ backgroundColor: '#ed8936' }}></span>
                <span>Year Losses</span>
              </div>
            </div>

            <div className="legend-category">
              <h5>Summary</h5>
              <div className="legend-item">
                <span className="legend-dot" style={{ backgroundColor: '#9f7aea' }}></span>
                <span>Taxable (40%): {formatCurrency(summary.taxableCapitalGain)}</span>
              </div>
              <div className="legend-item">
                <span className="legend-dot" style={{ backgroundColor: '#38b2ac' }}></span>
                <span>Total Transactions: {transactions.length}</span>
              </div>
            </div>
          </div>
        </div>

        <div className="charts-grid">
        {/* Gains/Losses by Asset */}
        {assetData.length > 0 && (
          <div className="chart-card">
            <h3>Gains/Losses by Asset</h3>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={assetData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="asset" stroke="#4a5568" />
                <YAxis stroke="#4a5568" tickFormatter={(value) => formatCurrency(value)} />
                <Tooltip content={<CustomTooltip />} />
                <Legend />
                <Bar dataKey="gains" name="Gains" fill="#48bb78" radius={[8, 8, 0, 0]} />
                <Bar dataKey="losses" name="Losses" fill="#f56565" radius={[8, 8, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        )}

        {/* Gains/Losses by Tax Year */}
        {taxYearData.length > 0 && (
          <div className="chart-card">
            <h3>Gains/Losses by Tax Year</h3>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={taxYearData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="taxYear" stroke="#4a5568" />
                <YAxis stroke="#4a5568" tickFormatter={(value) => formatCurrency(value)} />
                <Tooltip content={<CustomTooltip />} />
                <Legend />
                <Bar dataKey="gains" name="Gains" fill="#4299e1" radius={[8, 8, 0, 0]} />
                <Bar dataKey="losses" name="Losses" fill="#ed8936" radius={[8, 8, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        )}

        {/* Overall Gains vs Losses Donut */}
        {distributionData.length > 0 && (
          <div className="chart-card">
            <h3>Overall Gains vs Losses</h3>
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={distributionData}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={100}
                  paddingAngle={5}
                  dataKey="value"
                  label={(entry) => `${entry.name}: ${formatCurrency(entry.value)}`}
                  labelLine={false}
                >
                  {distributionData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip content={<CustomPieTooltip />} />
              </PieChart>
            </ResponsiveContainer>
          </div>
        )}

        {/* Transaction Type Distribution */}
        {typeData.length > 0 && (
          <div className="chart-card">
            <h3>Transaction Type Distribution</h3>
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={typeData}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={100}
                  paddingAngle={5}
                  dataKey="count"
                  label={(entry) => `${entry.type}: ${entry.count}`}
                >
                  {typeData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </div>
        )}
      </div>
      </div>
    </>
  );
};

export default Charts;
