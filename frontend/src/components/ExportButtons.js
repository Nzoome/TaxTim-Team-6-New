import React, { useState } from 'react';
import { generatePDF } from '../services/pdfGenerator';
import { generateExcel } from '../services/excelGenerator';
import './ExportButtons.css';

const ExportButtons = ({ transactions, summary, filters }) => {
  const [isGeneratingPDF, setIsGeneratingPDF] = useState(false);
  const [isGeneratingExcel, setIsGeneratingExcel] = useState(false);

  const handlePDFExport = async () => {
    setIsGeneratingPDF(true);
    try {
      await generatePDF(transactions, summary, filters);
    } catch (error) {
      console.error('Error generating PDF:', error);
      alert('Failed to generate PDF. Please try again.');
    } finally {
      setIsGeneratingPDF(false);
    }
  };

  const handleExcelExport = async () => {
    setIsGeneratingExcel(true);
    try {
      await generateExcel(transactions, summary, filters);
    } catch (error) {
      console.error('Error generating Excel:', error);
      alert('Failed to generate Excel file. Please try again.');
    } finally {
      setIsGeneratingExcel(false);
    }
  };

  return (
    <div className="export-buttons-section">
      <div className="export-header">
        <h3>Export Reports</h3>
        <p className="export-description">
          Generate SARS-ready audit reports for your tax submissions
        </p>
      </div>

      <div className="export-buttons-grid">
        {/* PDF Export */}
        <div className="export-card">
          <div className="export-card-icon"></div>
          <div className="export-card-content">
            <h4>PDF Audit Report</h4>
            <p>
              Comprehensive audit report with complete FIFO breakdown, 
              tax calculations, and transaction details suitable for SARS submission.
            </p>
            <ul className="export-features">
              <li>✓ Summary of capital gains/losses</li>
              <li>✓ Transaction-by-transaction breakdown</li>
              <li>✓ FIFO lot consumption details</li>
              <li>✓ Tax year allocation</li>
            </ul>
            <button
              className="btn btn-export btn-pdf"
              onClick={handlePDFExport}
              disabled={isGeneratingPDF || !transactions || transactions.length === 0}
            >
              {isGeneratingPDF ? (
                <>
                  <span className="spinner-small"></span>
                  Generating PDF...
                </>
              ) : (
                <>Download PDF Report</>
              )}
            </button>
          </div>
        </div>

        {/* Excel Export */}
        <div className="export-card">
          <div className="export-card-icon"></div>
          <div className="export-card-content">
            <h4>Excel Spreadsheet</h4>
            <p>
              Detailed spreadsheet with all transaction data, FIFO calculations, 
              and summary statistics for further analysis and record-keeping.
            </p>
            <ul className="export-features">
              <li>✓ All transaction data</li>
              <li>✓ Capital gains calculations</li>
              <li>✓ Multiple worksheets (Summary, Transactions, Lots)</li>
              <li>✓ Easy filtering and sorting</li>
            </ul>
            <button
              className="btn btn-export btn-excel"
              onClick={handleExcelExport}
              disabled={isGeneratingExcel || !transactions || transactions.length === 0}
            >
              {isGeneratingExcel ? (
                <>
                  <span className="spinner-small"></span>
                  Generating Excel...
                </>
              ) : (
                <>Download Excel File</>
              )}
            </button>
          </div>
        </div>
      </div>

      {/* SARS Notice */}
      <div className="sars-notice">
        <div className="notice-icon">⚠️</div>
        <div className="notice-content">
          <h4>Important Notice for SARS Submission</h4>
          <p>
            These reports are designed to assist with your South African tax return preparation. 
            Always consult with a registered tax professional or accountant before submitting to SARS.
          </p>
          <p>
            The 40% inclusion rate for individuals is automatically calculated. Companies and trusts 
            may have different rates. Annual exclusions (R40,000 for individuals) are NOT automatically 
            applied and should be handled by your tax professional.
          </p>
        </div>
      </div>
    </div>
  );
};

export default ExportButtons;
