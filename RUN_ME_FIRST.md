# 🚀 HOW TO RUN TRUSTBRIDGE

## ⚡ FASTEST WAY (3 Steps)

### Step 1: Install PHP
**Option A - XAMPP (Recommended for beginners)**
1. Download: https://www.apachefriends.org/download.html
2. Install XAMPP
3. Start Apache from XAMPP Control Panel

**Option B - PHP Only**
1. Download PHP 8.0+: https://windows.php.net/download/
2. Extract to `C:\php`
3. Add to PATH

### Step 2: Setup Database
**Option A - Use XAMPP MySQL (Easiest)**
1. Start MySQL from XAMPP Control Panel
2. Open: http://localhost/phpmyadmin
3. Click "Import" tab
4. Choose file: `database/schema-mysql.sql`
5. Click "Go"

**Option B - Use Supabase (Cloud, Free)**
1. Go to: https://supabase.com
2. Create account (free)
3. Create new project
4. Go to SQL Editor
5. Copy-paste content from `database/schema.sql`
6. Click "Run"
7. Update `config/database.php` with Supabase credentials

### Step 3: Run the Application

**If using XAMPP:**
1. Copy this folder to: `C:\xampp\htdocs\trustbridge`
2. Open browser: http://localhost/trustbridge

**If using PHP only:**
1. Double-click `setup.bat`
2. Open browser: http://localhost:8000

---

## 🎯 DEFAULT LOGIN

After setup, login with:
```
Email:    admin@trustbridge.local
Password: admin123
```

---

## 📱 WHAT YOU CAN DO

1. **Browse NGOs** - See verified NGOs on homepage
2. **Register as Donor** - Click "Get Started"
3. **Donate** - Click "Donate Now" on any NGO card
4. **Admin Panel** - Login and go to `/admin/dashboard.html`

---

## 🔧 TROUBLESHOOTING

### "Database connection failed"
→ Check `config/database.php` has correct credentials

### "Cannot access localhost"
→ Make sure PHP server is running (run `setup.bat`)

### "QR code not showing"
→ Check internet connection (needs CDN for QRCode library)

### "Page not found"
→ Make sure you're in the correct directory

---

## 📂 PROJECT STRUCTURE

```
trustbridge/
├── index.html              ← Start here (Homepage)
├── admin/dashboard.html    ← Admin panel
├── config/database.php     ← Database settings
├── api/                    ← Backend API
├── assets/                 ← CSS, JS, images
└── database/               ← SQL schemas
```

---

## 🎓 LEARNING PATH

1. ✅ Run the application (you are here!)
2. 📖 Read `QUICK_START.md` for detailed setup
3. 🔒 Read `SECURITY_GUIDE.md` to understand security
4. 🚀 Read `DEPLOYMENT_GUIDE.md` for production
5. 📊 Read `TRUST_SCORE_EXAMPLES.md` for scoring logic

---

## 💡 QUICK TIPS

**Test Donation Flow:**
1. Login as donor
2. Click "Donate Now" on any NGO
3. Enter amount (e.g., 1000)
4. Click "Generate QR Code"
5. Enter fake transaction ID: TEST123456
6. Submit

**Test Admin Approval:**
1. Login as admin
2. Go to admin dashboard
3. Review pending NGOs
4. Approve/Reject

**Create New NGO:**
1. Register with role "ngo"
2. Fill NGO details
3. Upload documents
4. Wait for admin approval

---

## 🆘 NEED HELP?

**Can't get it running?**
→ Open an issue with error message

**Want video tutorial?**
→ Check YouTube for "PHP local server setup"

**Prefer online hosting?**
→ Deploy to:
- Vercel (frontend)
- Railway (backend)
- Supabase (database)

---

## ✨ FEATURES TO TRY

- ✅ Multi-admin approval system
- ✅ Trust score calculation
- ✅ UPI QR code generation
- ✅ Fraud detection
- ✅ Donation tracking
- ✅ Animated dashboard
- ✅ Responsive design

---

## 🎉 YOU'RE READY!

Run `setup.bat` and start exploring TrustBridge!

Questions? Check other .md files in this folder.
