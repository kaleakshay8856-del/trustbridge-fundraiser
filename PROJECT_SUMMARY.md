# TrustBridge - Project Summary

## Overview
TrustBridge is a production-level secure NGO fundraising platform that connects verified NGOs with donors through a transparent, fraud-resistant system using UPI QR code-based donations.

## Tech Stack
- **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6+), GSAP animations
- **Backend**: PHP 8.0+ (REST API architecture), JWT authentication
- **Database**: PostgreSQL (Supabase)
- **Payment**: UPI QR Code system (no third-party payment gateway)

## Core Features

### 1. Multi-Role System
- **Donor**: Browse NGOs, donate, track donations
- **NGO**: Register, create campaigns, receive donations
- **Admin**: Approve NGOs (requires 2+ admins)
- **Verification Admin**: Review documents only
- **Finance Admin**: Verify transaction IDs only

### 2. Security Features
✅ JWT authentication with expiry
✅ Password hashing (bcrypt)
✅ Prepared statements (SQL injection prevention)
✅ CSRF protection
✅ Rate limiting (5 login attempts per 5 min)
✅ XSS filtering
✅ Secure file uploads
✅ HTTPS required
✅ Row Level Security (Supabase)

### 3. Multi-Admin Approval System
- Minimum 2 admin approvals required for NGO verification
- Immutable audit logs (cannot be deleted/modified)
- IP address tracking for all admin actions
- Daily approval limit (max 5 per admin)
- Separation of duties (different admin roles)

### 4. Trust Score System
```
Score Calculation:
+ Government Registration: +40
+ 80G Certificate: +20
+ 3+ Years Operation: +15
+ Address Verified: +10
- Each Complaint: -30

Minimum Score: 60 (for approval)
Maximum Score: 100
```

### 5. Fraud Detection
- Duplicate PAN number detection
- Duplicate UPI ID detection
- Suspicious IP monitoring (multiple accounts)
- Auto-suspension at 10+ complaints
- Fraud flag system with severity levels

### 6. UPI QR Donation Flow
```
1. Donor enters amount
2. System generates UPI QR code
   Format: upi://pay?pa=ngo_upi&pn=NAME&am=AMOUNT&cu=INR
3. Donor scans with UPI app (GPay/PhonePe/Paytm)
4. Donor submits transaction ID
5. Status: pending_verification
6. Finance admin verifies manually
7. Status: approved/rejected
```

**Security**: Only transaction ID stored, no banking credentials

### 7. Design System
- **Colors**: Deep Blue (#1E3A8A), Emerald Green (#10B981)
- **Style**: Glassmorphism cards, 16px border radius
- **Animations**: GSAP smooth transitions, fade-in on scroll
- **Responsive**: Mobile-first, tablet optimized, desktop dashboard
- **Typography**: Inter font, clean hierarchy

## File Structure
```
trustbridge/
├── index.html                 # Homepage
├── config/                    # Database & JWT config
├── database/                  # PostgreSQL schema
├── api/                       # REST API endpoints
├── utils/                     # Auth, CSRF, rate limiting
├── assets/                    # CSS, JS, images
├── admin/                     # Admin dashboard
├── donor/                     # Donor dashboard
├── ngo/                       # NGO dashboard
└── uploads/                   # Secure file storage
```

## Database Schema
- **users**: Authentication & roles
- **ngos**: NGO profiles with UPI ID
- **ngo_documents**: Verification documents
- **campaigns**: Fundraising campaigns
- **donations**: Transaction records
- **ngo_approvals**: Multi-admin approval tracking
- **admin_logs**: Immutable audit trail
- **fraud_flags**: Fraud detection system
- **complaints**: User complaints

## API Endpoints

### Public
- `POST /api/auth.php` - Login/Register
- `GET /api/ngos.php` - Browse NGOs

### Authenticated
- `POST /api/donations.php` - Submit donation
- `GET /api/donations.php` - View donation history

### Admin Only
- `POST /api/ngo-approval.php` - Approve/reject NGO
- `POST /api/verify-donation.php` - Verify transaction
- `GET /api/admin-stats.php` - Dashboard statistics

## Security Measures

### Admin Corruption Prevention
1. **No Single Point of Failure**: 2+ admin approvals required
2. **Immutable Logs**: Database triggers prevent deletion
3. **IP Tracking**: All actions logged with IP address
4. **Daily Limits**: Max 5 approvals per admin per day
5. **Automated Alerts**: Suspicious activity flagged

### Payment Security
- ✅ Store: Transaction ID, amount, UPI ID
- ❌ Never store: Banking credentials, OTPs, passwords
- ✅ Manual verification by finance admin
- ✅ Fraud detection on duplicate transactions

### Data Protection
- Password hashing with bcrypt
- Prepared statements for all queries
- Input validation and sanitization
- CSRF tokens on all forms
- Rate limiting on sensitive endpoints

## Deployment Checklist
- [ ] Import database schema to Supabase
- [ ] Configure database credentials
- [ ] Change JWT secret key
- [ ] Enable HTTPS (SSL certificate)
- [ ] Set up Row Level Security
- [ ] Configure file upload directory
- [ ] Enable error logging
- [ ] Set up automated backups
- [ ] Configure firewall rules
- [ ] Create first admin user
- [ ] Test all API endpoints
- [ ] Enable rate limiting
- [ ] Set security headers

## Key Differentiators

### vs Traditional Platforms
1. **No Payment Gateway Fees**: Direct UPI donations
2. **Multi-Admin Approval**: Prevents single-point corruption
3. **Trust Score System**: Transparent NGO verification
4. **Immutable Audit Logs**: Complete transparency
5. **Fraud Detection**: Automated monitoring

### vs Razorpay/Stripe
- No transaction fees (0% vs 2-3%)
- No PCI compliance needed
- Direct to NGO UPI account
- Manual verification adds trust layer
- India-specific UPI integration

## Performance Optimizations
- PHP OPcache enabled
- Database indexes on all foreign keys
- GSAP for smooth animations
- Lazy loading for images
- CDN for static assets
- Minified CSS/JS in production

## Monitoring & Maintenance
- Error logging to `/var/log/php/error.log`
- Admin action logs in database
- Fraud flag monitoring
- Daily database backups
- Weekly security audits
- Monthly trust score recalculation

## Future Enhancements
1. Email notifications (donation receipts)
2. SMS alerts for transaction verification
3. Mobile app (React Native)
4. Impact report generation
5. Donor leaderboard
6. NGO analytics dashboard
7. Automated document verification (OCR)
8. Blockchain donation tracking
9. Multi-language support
10. Social media integration

## Support & Documentation
- **README.md**: Quick start guide
- **FOLDER_STRUCTURE.md**: Project organization
- **SECURITY_GUIDE.md**: Security implementation details
- **DEPLOYMENT_GUIDE.md**: Step-by-step deployment
- **TRUST_SCORE_EXAMPLES.md**: Trust score calculations

## License
MIT License - Free for commercial and non-commercial use

## Contact
- Website: https://trustbridge.org
- Email: support@trustbridge.org
- Security: security@trustbridge.org

---

## Quick Start

1. **Clone repository**
```bash
git clone https://github.com/trustbridge/platform.git
cd platform
```

2. **Import database**
```bash
psql -h your-project.supabase.co -U postgres -d postgres -f database/schema.sql
```

3. **Configure**
```bash
# Edit config/database.php
# Edit config/jwt.php
```

4. **Deploy**
```bash
# Upload to web server
# Configure Apache/Nginx
# Enable HTTPS
```

5. **Create admin**
```sql
INSERT INTO users (email, password_hash, full_name, role)
VALUES ('admin@trustbridge.org', '$2y$10$...', 'Admin', 'admin');
```

6. **Access**
```
Homepage: https://trustbridge.org
Admin: https://trustbridge.org/admin/dashboard.html
```

---

**Built with ❤️ for transparent giving**
