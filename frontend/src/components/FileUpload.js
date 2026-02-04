import React, { useState } from 'react';
import './FileUpload.css';

const FileUpload = ({ onUpload, isLoading }) => {
  const [dragActive, setDragActive] = useState(false);
  const [selectedFile, setSelectedFile] = useState(null);

  const handleDrag = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === "dragenter" || e.type === "dragover") {
      setDragActive(true);
    } else if (e.type === "dragleave") {
      setDragActive(false);
    }
  };

  const handleDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);

    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFile(e.dataTransfer.files[0]);
    }
  };

  const handleChange = (e) => {
    e.preventDefault();
    if (e.target.files && e.target.files[0]) {
      handleFile(e.target.files[0]);
    }
  };

  const handleFile = (file) => {
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
      alert('Please upload a CSV or XLSX file');
      return;
    }

    setSelectedFile(file);
  };

  const handleUpload = () => {
    if (selectedFile) {
      onUpload(selectedFile);
    }
  };

  const handleClear = () => {
    setSelectedFile(null);
  };

  return (
    <div className="file-upload-container">
      <div 
        className={`file-upload-area ${dragActive ? 'drag-active' : ''} ${selectedFile ? 'file-selected' : ''}`}
        onDragEnter={handleDrag}
        onDragLeave={handleDrag}
        onDragOver={handleDrag}
        onDrop={handleDrop}
      >
        <input
          type="file"
          id="file-input"
          accept=".csv,.xlsx,.xls"
          onChange={handleChange}
          style={{ display: 'none' }}
          disabled={isLoading}
        />
        
        {!selectedFile ? (
          <>
            <div className="upload-icon">📁</div>
            <h3>Upload Your Transaction File</h3>
            <p>Drag and drop your CSV or XLSX file here</p>
            <p className="upload-or">or</p>
            <label htmlFor="file-input" className="btn btn-secondary">
              Browse Files
            </label>
            <p className="upload-hint">Supported formats: CSV, XLSX</p>
          </>
        ) : (
          <>
            <div className="upload-icon">✅</div>
            <h3>File Ready to Process</h3>
            <p className="file-name">{selectedFile.name}</p>
            <p className="file-size">
              {(selectedFile.size / 1024).toFixed(2)} KB
            </p>
            <div className="upload-actions">
              <button 
                className="btn btn-primary" 
                onClick={handleUpload}
                disabled={isLoading}
              >
                {isLoading ? (
                  <>
                    <div className="spinner"></div>
                    Processing...
                  </>
                ) : (
                  'Calculate Taxes'
                )}
              </button>
              <button 
                className="btn btn-secondary" 
                onClick={handleClear}
                disabled={isLoading}
              >
                Change File
              </button>
            </div>
          </>
        )}
      </div>

      <div className="file-format-help">
        <h4>📋 Required File Format</h4>
        <p>Your file must include these columns:</p>
        <ul>
          <li><strong>Date</strong> - Transaction date</li>
          <li><strong>Type</strong> - BUY, SELL, or TRADE</li>
          <li><strong>From Currency</strong> - Source currency (e.g., ZAR, BTC)</li>
          <li><strong>From Amount</strong> - Amount of source currency</li>
          <li><strong>To Currency</strong> - Destination currency</li>
          <li><strong>To Amount</strong> - Amount of destination currency</li>
          <li><strong>Price</strong> - Price per unit in ZAR</li>
          <li><strong>Fee</strong> - Transaction fee (optional)</li>
          <li><strong>Wallet</strong> - Wallet identifier (optional)</li>
        </ul>
      </div>
    </div>
  );
};

export default FileUpload;
