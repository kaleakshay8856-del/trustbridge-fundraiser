// Frontend Configuration
// Update this after deploying backend to Railway

const CONFIG = {
    // For local development
    API_BASE_LOCAL: './api',
    
    // For production (update with your Railway URL after deployment)
    API_BASE_PROD: 'https://your-backend.railway.app/api',
    
    // Auto-detect environment
    API_BASE: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
        ? './api'
        : 'https://your-backend.railway.app/api'
    
    // INSTRUCTIONS: After deploying to Railway, replace 'your-backend.railway.app' 
    // with your actual Railway URL (e.g., 'trustbridge-production.up.railway.app')
};

// Export for use in other files
window.APP_CONFIG = CONFIG;
