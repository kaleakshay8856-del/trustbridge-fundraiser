# Quick Deployment Guide

## 🚀 Deploy in 15 Minutes

### 1. Setup Supabase (5 min)

1. Go to https://supabase.com/dashboard
2. Click "New Project"
3. Fill in:
   - Name: `trustbridge`
   - Database Password: (generate strong password)
   - Region: Choose closest to you
4. Wait 2-3 minutes for setup
5. Go to **SQL Editor** and paste the entire content from `database/schema.sql`
6. Click **Run**
7. Go to **Settings** → **Database** and copy:
   - Host
   - Database name
   - Port
   - User
   - Password

### 2. Deploy Backend to Railway (5 min)

1. Go to https://railway.app
2. Click "Start a New Project"
3. Choose "Deploy from GitHub repo"
4. Connect your GitHub and select this repository
5. Click "Add Variables" and add:
   ```
   DB_HOST=your-project.supabase.co
   DB_PORT=5432
   DB_NAME=postgres
   DB_USER=postgres
   DB_PASS=your-supabase-password
   JWT_SECRET=random-secret-key-here
   ```
6. Railway will auto-deploy
7. Copy your Railway URL (e.g., `https://trustbridge.railway.app`)

### 3. Deploy Frontend to Vercel (5 min)

1. Go to https://vercel.com
2. Click "Add New" → "Project"
3. Import your GitHub repository
4. Configure:
   - Framework Preset: Other
   - Root Directory: ./
   - Build Command: (leave empty)
   - Output Directory: ./
5. Add Environment Variable:
   ```
   NEXT_PUBLIC_API_URL=https://your-backend.railway.app
   ```
6. Click "Deploy"
7. Wait 2-3 minutes

### 4. Update Configuration

1. Open `config.js` and update:
   ```javascript
   API_BASE_PROD: 'https://your-backend.railway.app/api'
   ```

2. Open `utils/cors.php` and update:
   ```php
   'https://your-app.vercel.app'
   ```

3. Commit and push changes - both will auto-redeploy

### 5. Test Your Deployment

1. Visit your Vercel URL
2. Register a new user
3. Login and test features
4. Register as NGO and test approval flow

## 🎉 Done!

Your app is now live at:
- Frontend: `https://your-app.vercel.app`
- Backend: `https://your-backend.railway.app`

## 📊 Free Tier Limits

- **Supabase**: 500MB database, 1GB storage, 2GB bandwidth
- **Railway**: $5 credit/month (~550 hours)
- **Vercel**: 100GB bandwidth, unlimited sites

## 🔧 Troubleshooting

### "Database connection failed"
- Check Supabase credentials in Railway environment variables
- Ensure SSL mode is enabled in database config

### "CORS error"
- Update `utils/cors.php` with your Vercel URL
- Redeploy Railway backend

### "API not found"
- Update `config.js` with correct Railway URL
- Redeploy Vercel frontend

## 📝 Next Steps

1. Setup custom domain (optional)
2. Configure email notifications
3. Setup Supabase Storage for file uploads
4. Add monitoring and analytics
5. Setup backup strategy
