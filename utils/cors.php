<?php
// CORS Configuration for Production

// Allow requests from Vercel frontend
// INSTRUCTIONS: After deploying to Vercel, add your Vercel URL here
$allowed_origins = [
    'http://localhost',
    'http://localhost:8000',
    'https://your-app.vercel.app', // Replace with your actual Vercel URL
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: *");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
