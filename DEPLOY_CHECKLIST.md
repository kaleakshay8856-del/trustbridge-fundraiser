# 🚀 Deployment Checklist

## ✅ Prerequisites (Already Done!)
- [x] Supabase project created
- [x] Database schema executed
- [x] All 9 tables created
- [x] Storage bucket configured
- [x] Supabase credentials saved

## 📋 Deployment Steps

### 1. Push to GitHub
```bash
# Initialize git (if not already done)
git init

# Add all files
git add .

# Commit
git commit -m "Initial commit - TrustBridge platform"

# Add remote (replace with your GitHub repo URL)
git remote add origin https://github.com/YOUR_USERNAME/trustbridge-fundraiser.git

# Push to GitHub
git branch -M main
git push -u origin main
```

### 2. Deploy Backend to Railway

1. Go to: https://railway.app
2. Click "New Project" → "Deploy from GitHub repo"
3. Select your repository
4. Add environment variables:
   ```
   DB_HOST=your-supabase-host.supabase.co
   DB_PORT=5432
   DB_NAME=postgres
   DB_USER=postgres
   DB_PASS=your-supabase-password
   SUPABASE_URL=https://your-project.supabase.co
   SUPABASE_ANON_KEY=your-anon-key
   SUPABASE_SERVICE_KEY=your-service-key
   JWT_SECRET=random-secret-key-here
   ```
5. Click "Deploy"
6. Copy Railway URL: `_______________________`

### 3. Deploy Frontend to Vercel

1. Go to: https://vercel.com
2. Click "Add New" → "Project"
3. Import your GitHub repository
4. Settings:
   - Framework: Other
   - Root Directory: ./
   - Build Command: (empty)
   - Output Directory: ./
5. Click "Deploy"
6. Copy Vercel URL: `_______________________`

### 4. Update Configuration

1. Open `config.js`
2. Replace `https://your-backend.railway.app` with your Railway URL
3. Open `utils/cors.php`
4. Replace `https://your-app.vercel.app` with your Vercel URL
5. Commit and push:
   ```bash
   git add config.js utils/cors.php
   git commit -m "Update production URLs"
   git push
   ```
6. Both Railway and Vercel will auto-redeploy

### 5. Test Deployment

- [ ] Visit Vercel URL
- [ ] Register new user
- [ ] Login successfully
- [ ] Register as NGO
- [ ] Upload documents
- [ ] Admin approval works
- [ ] Donations work
- [ ] QR codes generate

## 🎯 Your URLs

- **Frontend (Vercel)**: `_______________________`
- **Backend (Railway)**: `_______________________`
- **Database (Supabase)**: Already configured ✅

## 💡 Tips

- Railway free tier: $5 credit/month (~550 hours)
- Vercel free tier: 100GB bandwidth
- Supabase free tier: 500MB database
- Both platforms auto-deploy on git push
- Check Railway logs if backend issues occur
- Check Vercel logs if frontend issues occur

## 🔧 Common Issues

**"Database connection failed"**
- Check Railway environment variables
- Verify Supabase credentials

**"CORS error"**
- Update `utils/cors.php` with Vercel URL
- Redeploy Railway

**"API not found"**
- Update `config.js` with Railway URL
- Redeploy Vercel

## 📞 Need Help?

- Railway docs: https://docs.railway.app
- Vercel docs: https://vercel.com/docs
- Supabase docs: https://supabase.com/docs
