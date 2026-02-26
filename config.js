// Frontend Configuration
const CONFIG = {
    // For local development
    API_BASE_LOCAL: './api',
    
    // Production API URL (Railway backend)
    API_BASE_PROD: 'https://trustbridge-fundraiser-production.up.railway.app/api',
    
    // Auto-detect environment
    API_BASE: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
        ? './api'
        : 'https://trustbridge-fundraiser-production.up.railway.app/api'
};

// Export for use in other files
window.APP_CONFIG = CONFIG;

// Debug log
console.log('Config loaded:', CONFIG);
console.log('Using API_BASE:', CONFIG.API_BASE);
