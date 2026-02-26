# TrustBridge Deployment Guide

## Prerequisites
- Supabase account (free): https://supabase.com
- Vercel account (free): https://vercel.com
- Railway account (free): https://railway.app

## Step 1: Setup Supabase Database

1. Go to https://supabase.com and create a new project
2. Wait for the database to be ready (2-3 minutes)
3. Go to SQL Editor and run the schema from `database/schema.sql`
4. Note down your database credentials from Settings -> Database:
   - Host
   - Database name
   - User
   - Password
   - Port (5432)

## Step 2: Deploy Backend (PHP APIs) to Railway

1. Install Railway CLI:
   ```bash
   npm install -g @railway/cli
   ```

2. Login to Railway:
   ```bash
   railway login
   ```

3. Initialize project:
   ```bash
   railway init
   ```

4. Set environment variables:
   ```bash
   railway variables set DB_HOST=your-project.supabase.co
   railway variables set DB_PORT=5432
   railway variables set DB_NAME=postgres
   railway variables set DB_USER=postgres
   railway variables set DB_PASS=your-password
   ```

5. Deploy:
   ```bash
   railway up
   ```

6. Note your Railway backend URL (e.g., `https://your-app.railway.app`)

## Step 3: Deploy Frontend to Vercel

1. Install Vercel CLI:
   ```bash
   npm install -g vercel
   ```

2. Create `vercel.json` in project root (already created)

3. Update API URLs in JavaScript files to point to Railway backend

4. Deploy:
   ```bash
   vercel --prod
   ```

## Step 4: Update Configuration

1. Replace `config/database.php` with `config/database-supabase.php`
2. Update all API calls in JavaScript to use your Railway backend URL
3. Configure CORS in PHP files to allow Vercel domain

## Environment Variables

### Railway (Backend)
```
DB_HOST=your-project.supabase.co
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres
DB_PASS=your-password
JWT_SECRET=your-super-secret-key-change-this
```

### Vercel (Frontend)
```
NEXT_PUBLIC_API_URL=https://your-app.railway.app
```

## File Upload Configuration

For production, use Supabase Storage:
1. Go to Supabase -> Storage
2. Create a bucket named "documents"
3. Set bucket to public or authenticated access
4. Update upload code to use Supabase Storage API

## Testing

1. Test frontend: Visit your Vercel URL
2. Test backend: Visit `https://your-app.railway.app/api/test.php`
3. Test database: Try registering a user

## Troubleshooting

### CORS Issues
Add to all PHP files:
```php
header('Access-Control-Allow-Origin: https://your-vercel-app.vercel.app');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### Database Connection Issues
- Check Supabase connection pooler settings
- Verify SSL mode is set to 'require'
- Check firewall rules

### File Upload Issues
- Use Supabase Storage instead of local filesystem
- Update file paths to use Supabase URLs

## Cost Estimate

- Supabase: Free (500MB database, 1GB storage)
- Railway: Free ($5 credit/month, ~550 hours)
- Vercel: Free (100GB bandwidth)

Total: **FREE** for small to medium traffic
