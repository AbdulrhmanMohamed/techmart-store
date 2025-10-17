# Vercel Deployment Guide for TechMart E-commerce

This guide will help you deploy your TechMart e-commerce application to Vercel using only the JSON database (no MySQL required).

## Prerequisites

1. A Vercel account (free tier available)
2. Git repository with your code
3. Vercel CLI (optional but recommended)

## Deployment Steps

### 1. Prepare Your Repository

Your application is already configured for Vercel deployment with:
- `vercel.json` - Vercel configuration
- `config/database_auto.php` - Auto-detects Vercel environment
- `config/database_vercel.php` - JSON-only database for Vercel
- All PHP files updated to use auto-detection

### 2. Deploy to Vercel

#### Option A: Using Vercel Dashboard (Recommended)

1. Go to [vercel.com](https://vercel.com) and sign in
2. Click "New Project"
3. Import your Git repository
4. Vercel will automatically detect the PHP configuration
5. Click "Deploy"

#### Option B: Using Vercel CLI

```bash
# Install Vercel CLI
npm i -g vercel

# Login to Vercel
vercel login

# Deploy from your project directory
vercel

# Follow the prompts:
# - Set up and deploy? Y
# - Which scope? (select your account)
# - Link to existing project? N
# - Project name? (accept default or customize)
# - Directory? ./
# - Override settings? N
```

### 3. Environment Variables (Optional)

In your Vercel dashboard, you can set these environment variables:
- `USE_JSON_DB=true` (automatically set by configuration)

### 4. Custom Domain (Optional)

1. In your Vercel project dashboard
2. Go to "Settings" → "Domains"
3. Add your custom domain
4. Follow DNS configuration instructions

## Features Available on Vercel

✅ **Fully Functional:**
- Product browsing and search
- Shopping cart (session-based)
- User registration and login
- Order processing
- Admin panel
- Theme switching (light/dark mode)
- All JSON database operations

✅ **Automatic Features:**
- HTTPS encryption
- Global CDN
- Automatic deployments from Git
- Serverless scaling

## File Structure for Vercel

```
/
├── vercel.json              # Vercel configuration
├── config/
│   ├── database_auto.php    # Auto-detects environment
│   ├── database_vercel.php  # JSON-only database
│   └── json_database.php    # JSON database engine
├── data/                    # JSON database files
│   ├── products.json
│   ├── users.json
│   ├── orders.json
│   └── ...
├── api/                     # API endpoints
├── admin/                   # Admin panel
└── *.php                    # Main application files
```

## Performance Considerations

- **Cold Starts**: First request may be slower (1-2 seconds)
- **File Persistence**: JSON files persist between requests
- **Concurrent Users**: Handles multiple users simultaneously
- **Scaling**: Automatically scales based on traffic

## Limitations on Vercel

❌ **Not Available:**
- MySQL database (using JSON instead)
- File uploads to server (files are ephemeral)
- Long-running processes
- WebSocket connections

## Troubleshooting

### Common Issues:

1. **500 Error on deployment:**
   - Check Vercel function logs in dashboard
   - Ensure all file paths use forward slashes
   - Verify JSON files are valid

2. **Database not working:**
   - Ensure `data/` directory exists
   - Check file permissions in JSON files
   - Verify `USE_JSON_DB` environment variable

3. **Images not loading:**
   - Use external image hosting (Cloudinary, etc.)
   - Or store images in `assets/images/` for static files

### Debugging:

1. Check Vercel function logs:
   ```bash
   vercel logs [deployment-url]
   ```

2. Test locally with JSON database:
   ```bash
   USE_JSON_DB=true php -S localhost:8000
   ```

## Cost Estimation

**Vercel Free Tier Includes:**
- 100GB bandwidth per month
- 100 serverless function executions per day
- Custom domains
- HTTPS certificates

**Paid Plans Start at $20/month for:**
- Unlimited bandwidth
- Unlimited function executions
- Advanced analytics
- Team collaboration

## Next Steps After Deployment

1. Test all functionality on your live site
2. Set up monitoring and analytics
3. Configure custom domain if needed
4. Set up automated backups for JSON data
5. Consider upgrading to paid plan for production use

## Support

For issues specific to this deployment:
1. Check Vercel documentation
2. Review function logs in Vercel dashboard
3. Test locally with `USE_JSON_DB=true`

Your TechMart e-commerce site is now ready for Vercel deployment! 🚀