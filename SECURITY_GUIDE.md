# TrustBridge Security Guide

## Security Features Implemented

### 1. Authentication & Authorization
- **JWT Tokens**: Stateless authentication with expiry
- **Password Hashing**: bcrypt with automatic salt
- **Role-Based Access Control**: Separate permissions for donor, NGO, admin roles
- **Session Management**: Secure token storage

### 2. Multi-Admin Approval System
- **Minimum 2 Approvals**: No single admin can approve NGO
- **Immutable Audit Logs**: Cannot be deleted or modified
- **IP Tracking**: All admin actions logged with IP
- **Daily Limits**: Max 5 approvals per admin per day
- **Separation of Duties**: Different admin roles (verification, finance)

### 3. Fraud Detection

#### Automatic Checks
```sql
-- Duplicate PAN detection
SELECT id FROM ngos WHERE pan_number = ? AND id != ?

-- Duplicate UPI detection  
SELECT id FROM ngos WHERE upi_id = ? AND id != ?

-- Suspicious IP (multiple accounts)
SELECT COUNT(*) FROM users WHERE ip_address = ?

-- Auto-suspend at 10+ complaints
UPDATE ngos SET status = 'suspended' WHERE complaint_count >= 10
```

#### Trust Score Calculation
- Government registration: +40 points
- 80G certificate: +20 points
- 3+ years operation: +15 points
- Address verified: +10 points
- Each complaint: -30 points
- **Minimum score for approval: 60**

### 4. Payment Security

#### UPI QR Code System
```javascript
// Generate UPI string (no sensitive data stored)
upi://pay?pa=ngo_upi_id&pn=NGO_NAME&am=AMOUNT&cu=INR
```

**What We Store:**
- ✅ Transaction ID (for verification)
- ✅ Amount
- ✅ UPI ID (NGO's public identifier)

**What We DON'T Store:**
- ❌ Banking credentials
- ❌ Card details
- ❌ OTPs
- ❌ Passwords

#### Donation Verification Flow
1. Donor generates QR → Pays via UPI app
2. Donor submits transaction ID
3. Status: `pending_verification`
4. Finance admin manually verifies
5. Status: `approved` or `rejected`

### 5. Database Security

#### Prepared Statements (SQL Injection Prevention)
```php
// ✅ SAFE
$stmt = $db->query("SELECT * FROM users WHERE email = ?", [$email]);

// ❌ UNSAFE (Never do this)
$query = "SELECT * FROM users WHERE email = '$email'";
```

#### Row Level Security (Supabase)
```sql
-- Enable RLS
ALTER TABLE donations ENABLE ROW LEVEL SECURITY;

-- Donors can only see their own donations
CREATE POLICY donor_donations ON donations
FOR SELECT USING (auth.uid() = donor_id);
```

### 6. Input Validation & Sanitization

```php
// Email validation
$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Invalid email');
}

// HTML sanitization
$name = htmlspecialchars($input['name'], ENT_QUOTES, 'UTF-8');

// Number validation
$amount = floatval($input['amount']);
if ($amount <= 0) {
    throw new Exception('Invalid amount');
}
```

### 7. CSRF Protection

```php
// Generate token
$token = CSRFProtection::generateToken();

// Validate on form submission
CSRFProtection::verify();
```

```javascript
// Include in AJAX requests
fetch('/api/endpoint', {
    headers: {
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
    }
});
```

### 8. Rate Limiting

```php
// Limits per action
'login' => 5 attempts per 5 minutes
'register' => 3 attempts per hour
'donation' => 10 per hour
'api' => 100 per minute

// Usage
checkRateLimit('login');
```

### 9. File Upload Security

```php
// Allowed file types for NGO documents
$allowed = ['pdf', 'jpg', 'jpeg', 'png'];

// Validate file type
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    throw new Exception('Invalid file type');
}

// Rename file (prevent overwrite attacks)
$newName = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

// Store outside web root
move_uploaded_file($_FILES['file']['tmp_name'], '/secure/uploads/' . $newName);
```

### 10. XSS Prevention

```javascript
// Escape user input before displaying
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Use textContent instead of innerHTML
element.textContent = userInput; // Safe
element.innerHTML = userInput;   // Unsafe
```

## Admin Corruption Prevention

### Golden Rule: "Trust the system, not the admin"

1. **No Single Point of Failure**
   - Minimum 2 admin approvals required
   - Different admin roles with limited permissions

2. **Immutable Audit Trail**
   - All actions logged with timestamp, IP, details
   - Logs cannot be deleted or modified (database trigger)

3. **Automated Alerts**
   - Admin approves >5 NGOs/day → Alert
   - Same IP multiple accounts → Flag
   - Duplicate PAN/UPI → Auto-flag

4. **Separation of Duties**
   - Verification Admin: Reviews documents only
   - Finance Admin: Verifies transactions only
   - Super Admin: Full access (use sparingly)

## Production Deployment Checklist

- [ ] Change JWT secret key
- [ ] Enable HTTPS (SSL certificate)
- [ ] Configure Supabase Row Level Security
- [ ] Set up database backups
- [ ] Enable error logging (not display)
- [ ] Configure rate limiting with Redis
- [ ] Set up monitoring (Sentry, LogRocket)
- [ ] Enable CORS for specific domains only
- [ ] Use environment variables for secrets
- [ ] Set secure cookie flags (HttpOnly, Secure, SameSite)
- [ ] Implement Content Security Policy headers
- [ ] Regular security audits
- [ ] Penetration testing

## Environment Variables (.env)

```bash
DB_HOST=your-project.supabase.co
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres
DB_PASS=your-secure-password

JWT_SECRET=your-super-secret-key-min-32-chars
JWT_EXPIRY=86400

UPLOAD_PATH=/secure/uploads
MAX_FILE_SIZE=5242880

RATE_LIMIT_ENABLED=true
REDIS_HOST=localhost
REDIS_PORT=6379
```

## Security Headers (Add to .htaccess or nginx.conf)

```apache
# Prevent clickjacking
Header always set X-Frame-Options "DENY"

# XSS Protection
Header always set X-XSS-Protection "1; mode=block"

# Prevent MIME sniffing
Header always set X-Content-Type-Options "nosniff"

# HTTPS only
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Content Security Policy
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com"
```

## Incident Response Plan

1. **Detect**: Monitor logs, fraud flags, user reports
2. **Contain**: Suspend affected accounts, block IPs
3. **Investigate**: Review audit logs, identify breach
4. **Remediate**: Fix vulnerability, restore data
5. **Document**: Record incident, update procedures
6. **Notify**: Inform affected users (if required by law)

## Contact Security Team

For security issues, email: security@trustbridge.org
