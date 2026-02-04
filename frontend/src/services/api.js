import axios from 'axios';

const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';

/**
 * Upload and process a crypto transaction file
 * @param {File} file - CSV or XLSX file
 * @returns {Promise<Object>} Processed transactions and summary
 */
export const uploadTransactionFile = async (file) => {
  const formData = new FormData();
  formData.append('file', file);

  try {
    const response = await axios.post(`${API_BASE_URL}`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    return response.data;
  } catch (error) {
    console.error('Upload error:', error);
    if (error.response && error.response.data) {
      throw error.response.data;
    }
    throw {
      success: false,
      error: 'Network error. Please check your connection and try again.',
      errors: {}
    };
  }
};

/**
 * Format currency amount for display
 */
export const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-ZA', {
    style: 'currency',
    currency: 'ZAR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
};

/**
 * Format crypto amount for display
 */
export const formatCrypto = (amount, decimals = 8) => {
  return parseFloat(amount).toFixed(decimals);
};

/**
 * Format date for display
 */
export const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-ZA', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

/**
 * Get stored transaction data and analytics
 * @returns {Promise<Object>} Transaction data and analytics
 */
export const getTransactionData = async () => {
  try {
    const response = await axios.get(`${API_BASE_URL}/transactions`);
    return response.data;
  } catch (error) {
    console.error('Failed to fetch transaction data:', error);
    if (error.response && error.response.data) {
      throw error.response.data;
    }
    throw {
      success: false,
      error: 'Failed to fetch transaction data',
      data: null
    };
  }
};
