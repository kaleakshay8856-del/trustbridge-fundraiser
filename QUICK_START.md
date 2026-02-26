# TrustBridge - Quick Start Guide (Local Development)

## Option 1: Using XAMPP (Easiest for Windows)

### Step 1: Install XAMPP
1. Download XAMPP from: https://www.apachefriends.org/
2. Install with PHP 8.0+ and PostgreSQL (or use MySQL as alternative)
3. Start Apache from XAMPP Control Panel

### Step 2: Setup Project
```bash
# Copy project to XAMPP htdocs folder
# Example: C:\xampp\htdocs\trustbridge\
```

### Step 3: Database Setup (Using MySQL instead of PostgreSQL for local)

**Create MySQL version of database:**

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Create database: `trustbridge`
3. Import the MySQL schema (see below)

### Step 4: Configure Database Connection

Edit `config/database.php`:
```php
<?php
// For local MySQL
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'trustbridge');
define('DB_USER', 'root');
define('DB_PASS', ''); // Empty for XAMPP default

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            // MySQL connection
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            throw $e;
        }
    }
}
```

### Step 5: Access the Application
```
Homepage: http://localhost/trustbridge/
Admin Dashboard: http://localhost/trustbridge/admin/dashboard.html
```

---

## Option 2: Using PHP Built-in Server (Simplest)

### Step 1: Install PHP
1. Download PHP 8.0+: https://windows.php.net/download/
2. Extract to `C:\php`
3. Add to PATH: System Properties → Environment Variables → Path → Add `C:\php`

### Step 2: Install SQLite (Easiest Database)
PHP comes with SQLite built-in!

### Step 3: Start Server
```bash
# Navigate to project folder
cd trustbridge

# Start PHP server
php -S localhost:8000
```

### Step 4: Access
```
Open browser: http://localhost:8000
```

---

## Option 3: Using Online Database (Supabase - Recommended)

### Step 1: Create Supabase Account
1. Go to: https://supabase.com
2. Create new project (FREE tier)
3. Wait 2 minutes for setup

### Step 2: Import Database
1. Go to SQL Editor in Supabase dashboard
2. Copy content from `database/schema.sql`
3. Paste and click "Run"

### Step 3: Get Connection Details
1. Go to Project Settings → Database
2. Copy connection string

### Step 4: Update config/database.php
```php
define('DB_HOST', 'db.xxxxx.supabase.co');
define('DB_PORT', '5432');
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres');
define('DB_PASS', 'your-password-here');
```

### Step 5: Run with PHP Server
```bash
php -S localhost:8000
```

---

## Testing the Application

### 1. Create First Admin User

Open browser console and run:
```javascript
fetch('http://localhost:8000/api/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'register',
        email: 'admin@test.com',
        password: 'admin123',
        full_name: 'Admin User',
        role: 'admin'
    })
})
.then(r => r.json())
.then(console.log);
```

### 2. Login
```javascript
fetch('http://localhost:8000/api/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'login',
        email: 'admin@test.com',
        password: 'admin123'
    })
})
.then(r => r.json())
.then(data => {
    localStorage.setItem('token', data.token);
    localStorage.setItem('user', JSON.stringify(data.user));
    console.log('Logged in!', data);
});
```

### 3. Create Test NGO
Use the registration form or insert directly into database.

---

## Common Issues & Solutions

### Issue 1: "Database connection failed"
**Solution**: Check database credentials in `config/database.php`

### Issue 2: "CORS error"
**Solution**: Add to API files:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### Issue 3: "QR Code not showing"
**Solution**: Include QRCode library in `index.html`:
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
```

### Issue 4: "JWT token invalid"
**Solution**: Change JWT secret in `config/jwt.php` to any random string

### Issue 5: "File not found"
**Solution**: Check file paths are relative to project root

---

## Development Workflow

1. **Start Server**
   ```bash
   php -S localhost:8000
   ```

2. **Open Browser**
   ```
   http://localhost:8000
   ```

3. **Make Changes**
   - Edit HTML/CSS/JS files
   - Refresh browser to see changes

4. **Test API**
   - Use browser console
   - Or use Postman/Insomnia

---

## Next Steps

1. ✅ Get it running locally
2. ✅ Create admin user
3. ✅ Test registration/login
4. ✅ Create test NGO
5. ✅ Test donation flow
6. 📚 Read SECURITY_GUIDE.md
7. 🚀 Deploy to production (see DEPLOYMENT_GUIDE.md)

---

## Need Help?

**Can't get database working?**
→ Use SQLite for now (simplest option)

**Can't install PHP?**
→ Use XAMPP (includes everything)

**Want to skip local setup?**
→ Deploy directly to free hosting:
- Vercel (frontend)
- Railway (backend + database)
- Supabase (database only)

---

## Quick Commands Reference

```bash
# Check PHP version
php -v

# Start server
php -S localhost:8000

# Test database connection
php -r "require 'config/database.php'; echo 'Connected!';"

# View PHP errors
tail -f error.log
```
