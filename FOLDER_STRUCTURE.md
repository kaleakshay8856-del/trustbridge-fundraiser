# TrustBridge - Folder Structure

```
trustbridge/
│
├── index.html                      # Public homepage
├── README.md                       # Project documentation
├── FOLDER_STRUCTURE.md            # This file
│
├── config/                        # Configuration files
│   ├── database.php               # PostgreSQL connection
│   └── jwt.php                    # JWT authentication
│
├── database/                      # Database files
│   └── schema.sql                 # Complete PostgreSQL schema
│
├── api/                          # REST API endpoints
│   ├── auth.php                  # Login/Register
│   ├── ngo-approval.php          # Multi-admin NGO approval
│   ├── donations.php             # Donation submission
│   ├── verify-donation.php       # Finance admin verification
│   ├── fraud-detection.php       # Fraud detection system
│   ├── ngos.php                  # NGO CRUD operations
│   ├── campaigns.php             # Campaign management
│   ├── admin-stats.php           # Dashboard statistics
│   ├── admin-ngos.php            # Admin NGO management
│   └── admin-donations.php       # Admin donation management
│
├── utils/                        # Utility functions
│   ├── auth-middleware.php       # JWT verification middleware
│   ├── csrf-protection.php       # CSRF token generation
│   ├── rate-limiter.php          # Rate limiting
│   └── file-upload.php           # Secure file upload handler
│
├── assets/                       # Frontend assets
│   ├── css/
│   │   ├── style.css            # Main styles
│   │   └── dashboard.css        # Dashboard styles
│   │
│   ├── js/
│   │   ├── main.js              # Main JavaScript
│   │   ├── dashboard.js         # Admin dashboard
│   │   ├── qr-generator.js      # UPI QR code generation
│   │   └── animations.js        # GSAP animations
│   │
│   └── images/                  # Images and icons
│
├── admin/                        # Admin panel
│   ├── dashboard.html           # Admin dashboard
│   ├── ngo-approvals.html       # NGO approval interface
│   ├── donations.html           # Donation verification
│   └── fraud-flags.html         # Fraud monitoring
│
├── donor/                        # Donor dashboard
│   ├── dashboard.html           # Donor dashboard
│   ├── browse-ngos.html         # Browse NGOs
│   └── my-donations.html        # Donation history
│
├── ngo/                         # NGO dashboard
│   ├── dashboard.html           # NGO dashboard
│   ├── campaigns.html           # Campaign management
│   └── reports.html             # Impact reports
│
└── uploads/                     # Uploaded files
    ├── documents/               # NGO verification documents
    └── images/                  # Campaign images
```

## Key Features by Module

### Security
- JWT authentication (config/jwt.php)
- Password hashing (api/auth.php)
- CSRF protection (utils/csrf-protection.php)
- Rate limiting (utils/rate-limiter.php)
- Prepared statements (all API files)

### Multi-Admin Approval
- Minimum 2 admin approvals required
- Immutable audit logs
- IP tracking
- Daily approval limits
- Trust score calculation

### UPI Donation System
- Dynamic QR code generation
- Transaction ID verification
- No banking credentials stored
- Finance admin verification

### Fraud Detection
- Duplicate PAN detection
- Duplicate UPI detection
- IP monitoring
- Auto-suspension at 10+ complaints
- Fraud flag system

### Trust Score System
- Govt registration: +40
- 80G certificate: +20
- 3+ years old: +15
- Address verified: +10
- Complaints: -30 each
- Minimum score: 60 for approval
