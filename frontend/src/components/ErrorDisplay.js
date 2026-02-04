import React from 'react';
import './ErrorDisplay.css';

const ErrorDisplay = ({ error, errors }) => {
  if (!error && (!errors || Object.keys(errors).length === 0)) {
    return null;
  }

  return (
    <div className="error-display">
      <div className="error-header">
        <span className="error-icon">❌</span>
        <h3>Upload Failed</h3>
      </div>
      
      {error && (
        <p className="error-main-message">{error}</p>
      )}

      {errors && Object.keys(errors).length > 0 && (
        <div className="error-details">
          <h4>Issues Found:</h4>
          <ul className="error-list">
            {[...new Set(
              Object.entries(errors).flatMap(([key, messages]) => 
                Array.isArray(messages) ? messages : [messages]
              )
            )].map((msg, idx) => (
              <li key={idx}>{msg}</li>
            ))}
          </ul>
        </div>
      )}

      <div className="error-help">
        <p>💡 <strong>Tips:</strong></p>
        <ul>
          <li>Ensure all required columns are present</li>
          <li>Check that dates are in a valid format (YYYY-MM-DD)</li>
          <li>Verify all amounts are positive numbers</li>
          <li>Transaction type must be BUY, SELL, or TRADE</li>
        </ul>
      </div>
    </div>
  );
};

export default ErrorDisplay;
