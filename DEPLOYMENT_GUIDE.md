# TrustBridge Deployment Guide

## Prerequisites

- PHP 8.0 or higher
- PostgreSQL 13+ (Supabase recommended)
- Web server (Apache/Nginx)
- SSL certificate (Let's Encrypt)
- Composer (PHP package manager)

## Step 1: Database Setup (Supabase)

### Create Supabase Project
1. Go to https://supabase.com
2. Create new project
3. Note down connection details

### Import Schema
```bash
# Connect to Supabase PostgreSQL
psql -h your-project.supabase.co -U postgres -d postgres

# Import schema
\i database/schema.sql
```

### Configure Row Level Security
```sql
-- Enable RLS on all tables
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE ngos ENABLE ROW LEVEL SECURITY;
ALTER TABLE donations ENABLE ROW LEVEL SECURITY;

-- Example policy: Users can only see their own data
CREATE POLICY user_isolation ON users
FOR ALL USING (auth.uid() = id);
```

## Step 2: Configure Application

### Update Database Config
Edit `config/database.php`:
```php
define('DB_HOST', 'your-project.supabase.co');
define('DB_PORT', '5432');
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres');
define('DB_PASS', 'your-secure-password');
```

### Update JWT Secret
Edit `config/jwt.php`:
```php
define('JWT_SECRET', 'your-super-secret-key-change-this-in-production');
```

**Generate secure secret:**
```bash
php -r "echo bin2hex(random_bytes(32));"
```

## Step 3: Web Server Configuration

### Apache (.htaccess)
```apache
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# API routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1.php [L,QSA]

# Security headers
Header always set X-Frame-Options "DENY"
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Content-Type-Options "nosniff"
Header always set Strict-Transport-Security "max-age=31536000"
Header always set Content-Security-Policy "default-src 'self'"

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "\.(env|sql|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### Nginx (nginx.conf)
```nginx
server {
    listen 443 ssl http2;
    server_name trustbridge.org;
    
    ssl_certificate /etc/letsencrypt/live/trustbridge.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/trustbridge.org/privkey.pem;
    
    root /var/www/trustbridge;
    index index.html;
    
    # Security headers
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Strict-Transport-Security "max-age=31536000" always;
    
    # API routing
    location /api/ {
        try_files $uri $uri.php =404;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    # Static files
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # Deny access to sensitive files
    location ~ /\.(env|git|sql) {
        deny all;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name trustbridge.org;
    return 301 https://$server_name$request_uri;
}
```

## Step 4: SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Generate certificate
sudo certbot --apache -d trustbridge.org -d www.trustbridge.org

# Auto-renewal (cron job)
sudo crontab -e
# Add: 0 0 * * * certbot renew --quiet
```

## Step 5: File Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/trustbridge

# Set permissions
sudo find /var/www/trustbridge -type d -exec chmod 755 {} \;
sudo find /var/www/trustbridge -type f -exec chmod 644 {} \;

# Uploads directory (writable)
sudo chmod 775 /var/www/trustbridge/uploads
```

## Step 6: Environment Variables

Create `.env` file (outside web root):
```bash
DB_HOST=your-project.supabase.co
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres
DB_PASS=your-secure-password

JWT_SECRET=your-super-secret-key
JWT_EXPIRY=86400

UPLOAD_PATH=/var/www/uploads
MAX_FILE_SIZE=5242880

RATE_LIMIT_ENABLED=true
```

Load in PHP:
```php
// Load environment variables
$dotenv = parse_ini_file('/var/www/.env');
define('DB_HOST', $dotenv['DB_HOST']);
```

## Step 7: Create Admin User

```sql
-- Insert first admin user
INSERT INTO users (email, password_hash, full_name, role, status)
VALUES (
    'admin@trustbridge.org',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'Super Admin',
    'admin',
    'active'
);
```

**Change password immediately after first login!**

## Step 8: Testing

### Test Database Connection
```bash
php -r "require 'config/database.php'; echo 'Connected!';"
```

### Test API Endpoints
```bash
# Register user
curl -X POST https://trustbridge.org/api/auth \
  -H "Content-Type: application/json" \
  -d '{"action":"register","email":"test@example.com","password":"password123","full_name":"Test User"}'

# Login
curl -X POST https://trustbridge.org/api/auth \
  -H "Content-Type: application/json" \
  -d '{"action":"login","email":"test@example.com","password":"password123"}'
```

## Step 9: Monitoring & Logging

### Error Logging
Edit `php.ini`:
```ini
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

### Application Logging
```php
// Log to file
error_log("User login: " . $user_id, 3, "/var/log/trustbridge/app.log");
```

### Database Logging
```sql
-- Enable query logging in PostgreSQL
ALTER SYSTEM SET log_statement = 'all';
SELECT pg_reload_conf();
```

## Step 10: Backup Strategy

### Database Backup (Daily)
```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/trustbridge"

# Backup database
pg_dump -h your-project.supabase.co -U postgres -d postgres > $BACKUP_DIR/db_$DATE.sql

# Compress
gzip $BACKUP_DIR/db_$DATE.sql

# Delete backups older than 30 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete
```

### Cron Job
```bash
# Run daily at 2 AM
0 2 * * * /usr/local/bin/backup.sh
```

## Step 11: Performance Optimization

### Enable PHP OPcache
Edit `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### Database Indexing
Already included in `schema.sql`:
```sql
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_donations_status ON donations(verification_status);
```

### CDN for Static Assets
Use Cloudflare or similar:
- Cache CSS, JS, images
- Enable Brotli compression
- Minify assets

## Step 12: Security Hardening

### Disable PHP Functions
Edit `php.ini`:
```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
```

### Firewall Rules (UFW)
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw enable
```

### Fail2Ban (Brute Force Protection)
```bash
sudo apt install fail2ban

# Configure
sudo nano /etc/fail2ban/jail.local
```

## Troubleshooting

### Database Connection Failed
- Check Supabase credentials
- Verify IP whitelist in Supabase dashboard
- Test connection: `psql -h HOST -U USER -d DATABASE`

### 500 Internal Server Error
- Check PHP error log: `tail -f /var/log/php/error.log`
- Verify file permissions
- Check Apache/Nginx error log

### JWT Token Invalid
- Verify JWT_SECRET matches
- Check token expiry time
- Clear browser localStorage

## Production Checklist

- [ ] SSL certificate installed
- [ ] Database credentials secured
- [ ] JWT secret changed
- [ ] Error display disabled
- [ ] Logging enabled
- [ ] Backups configured
- [ ] Firewall rules set
- [ ] Admin user created
- [ ] Rate limiting enabled
- [ ] CSRF protection active
- [ ] File upload restrictions
- [ ] Security headers configured
- [ ] Monitoring tools installed
- [ ] Documentation updated

## Support

For deployment issues:
- Email: support@trustbridge.org
- Documentation: https://docs.trustbridge.org
