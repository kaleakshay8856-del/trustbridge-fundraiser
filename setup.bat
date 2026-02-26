@echo off
echo ========================================
echo TrustBridge - Quick Setup Script
echo ========================================
echo.

REM Check if PHP is installed
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP is not installed or not in PATH
    echo.
    echo Please install PHP from: https://windows.php.net/download/
    echo Or install XAMPP from: https://www.apachefriends.org/
    pause
    exit /b 1
)

echo [OK] PHP is installed
php -v
echo.

REM Check current directory
echo Current directory: %cd%
echo.

REM Start PHP built-in server
echo ========================================
echo Starting PHP Development Server...
echo ========================================
echo.
echo Access the application at:
echo.
echo   Homepage: http://localhost:8000
echo   Admin:    http://localhost:8000/admin/dashboard.html
echo.
echo Default Login Credentials:
echo   Email:    admin@trustbridge.local
echo   Password: admin123
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

php -S localhost:8000
