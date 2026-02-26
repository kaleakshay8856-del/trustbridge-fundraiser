# TrustBridge - Secure NGO Fundraising Platform

## Tech Stack
- Frontend: HTML5, CSS3, Vanilla JavaScript (ES6+), GSAP
- Backend: PHP (REST API), JWT Authentication
- Database: PostgreSQL (Supabase)
- Payment: UPI QR Code based donations

## Security Features
- Multi-admin approval system
- Fraud detection
- Immutable audit logs
- JWT authentication
- Password hashing
- CSRF protection
- Rate limiting

## Setup Instructions

1. Import `database/schema.sql` to PostgreSQL
2. Configure `config/database.php` with your Supabase credentials
3. Set JWT secret in `config/jwt.php`
4. Run on HTTPS server
5. Access via `index.html`

## User Roles
- Donor: Browse, donate, track donations
- NGO: Create campaigns, receive donations
- Admin: Approve NGOs (requires 2+ admins)
- Verification Admin: Review documents
- Finance Admin: Verify transactions

## Project Structure
See folder structure below.
