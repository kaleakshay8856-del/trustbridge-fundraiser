// TrustBridge Main JavaScript

const API_BASE = './api';
let currentUser = null;

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    initAnimations();
    loadFeaturedNGOs();
    checkAuth();
});

// Animations
function initAnimations() {
    // Animated counters
    const counters = document.querySelectorAll('[data-target]');
    counters.forEach(counter => {
        const target = parseFloat(counter.dataset.target);
        animateCounter(counter, target);
    });
    
    // Scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    });
    
    document.querySelectorAll('.ngo-card').forEach(card => {
        observer.observe(card);
    });
}

function animateCounter(element, target) {
    let current = 0;
    const increment = target / 100;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = Math.round(target);
            clearInterval(timer);
        } else {
            element.textContent = Math.round(current);
        }
    }, 20);
}

// Authentication
function checkAuth() {
    const token = localStorage.getItem('token');
    if (token) {
        currentUser = JSON.parse(localStorage.getItem('user'));
        updateNavbar();
    }
}

function updateNavbar() {
    const navActions = document.querySelector('.nav-actions');
    if (currentUser) {
        navActions.innerHTML = `
            <span>Welcome, ${currentUser.name}</span>
            <button class="btn btn-outline" onclick="logout()">Logout</button>
        `;
    }
}

async function login(email, password) {
    try {
        const response = await fetch(`${API_BASE}/auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'login', email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            currentUser = data.user;
            closeModal('loginModal');
            updateNavbar();
            showToast('Login successful!', 'success');
            
            // Redirect based on role
            if (data.user.role === 'admin') {
                setTimeout(() => {
                    window.location.href = 'admin/dashboard.html';
                }, 1000);
            } else if (data.user.role === 'ngo') {
                setTimeout(() => {
                    window.location.href = 'ngo/dashboard.html';
                }, 1000);
            }
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('Login failed', 'error');
    }
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    currentUser = null;
    window.location.href = './index.html';
}

// Load NGOs
async function loadFeaturedNGOs() {
    try {
        const response = await fetch(`${API_BASE}/ngos.php?status=approved&limit=6`);
        const data = await response.json();
        
        const grid = document.getElementById('ngoGrid');
        
        if (data.ngos && data.ngos.length > 0) {
            grid.innerHTML = data.ngos.map(ngo => createNGOCard(ngo)).join('');
        } else {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                    <p style="color: var(--text-light); font-size: 1.2rem;">No NGOs available yet. Check back soon!</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Failed to load NGOs', error);
        const grid = document.getElementById('ngoGrid');
        grid.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                <p style="color: var(--text-light);">Unable to load NGOs. Please try again later.</p>
            </div>
        `;
    }
}

function createNGOCard(ngo) {
    const raised = parseFloat(ngo.total_raised || 0);
    const goal = 1000000; // Default goal of 10 lakhs
    const progress = Math.min((raised / goal) * 100, 100);
    
    return `
        <div class="ngo-card">
            <span class="verified-badge">✓ Verified</span>
            <h3>${ngo.ngo_name}</h3>
            <p>${(ngo.description || 'No description available').substring(0, 100)}...</p>
            <div class="trust-score" style="margin: 1rem 0; color: var(--secondary);">
                Trust Score: ${ngo.trust_score || 0}/100
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: ${progress}%"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 1rem;">
                <span>₹${raised.toLocaleString('en-IN')}</span>
                <span>${ngo.donation_count || 0} donations</span>
            </div>
            <button class="btn btn-primary btn-block ripple" onclick="openDonationModal('${ngo.id}', '${ngo.upi_id}')">
                Donate Now
            </button>
        </div>
    `;
}

// Donation Flow
let currentNGO = null;

function openDonationModal(ngoId, upiId) {
    if (!currentUser) {
        showLogin();
        return;
    }
    
    currentNGO = { id: ngoId, upi_id: upiId };
    document.getElementById('donationModal').classList.add('active');
}

document.getElementById('generateQRBtn')?.addEventListener('click', () => {
    const amount = document.getElementById('donationAmount').value;
    
    if (!amount || amount <= 0) {
        showToast('Enter valid amount', 'error');
        return;
    }
    
    // Use test UPI ID if NGO doesn't have one
    const upiId = currentNGO.upi_id || 'akshay.kale2@axl';
    
    generateUPIQR(upiId, amount);
    document.getElementById('qrCodeSection').style.display = 'block';
    document.getElementById('generateQRBtn').style.display = 'none';
});

function generateUPIQR(upiId, amount) {
    const upiString = `upi://pay?pa=${upiId}&pn=NGO&am=${amount}&cu=INR`;
    
    const qrContainer = document.getElementById('qrCanvas');
    // Clear previous QR code
    qrContainer.innerHTML = '';
    
    // Generate new QR code
    new QRCode(qrContainer, {
        text: upiString,
        width: 256,
        height: 256,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
}

document.getElementById('donationForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const amount = document.getElementById('donationAmount').value;
    const transactionId = document.getElementById('transactionId').value;
    
    try {
        const response = await fetch(`${API_BASE}/donations.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                ngo_id: currentNGO.id,
                amount: parseFloat(amount),
                transaction_id: transactionId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Donation submitted for verification!', 'success');
            closeModal('donationModal');
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('Donation failed', 'error');
    }
});

// Modal Functions
function showLogin() {
    document.getElementById('loginModal').classList.add('active');
}

function showRegister() {
    document.getElementById('registerModal').classList.add('active');
}

function showAllNGOs() {
    // Scroll to NGOs section and load all
    document.getElementById('ngos').scrollIntoView({behavior: 'smooth'});
    // Could expand to show a dedicated browse page
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Toast Notifications
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 2rem;
        background: ${type === 'success' ? '#10B981' : '#EF4444'};
        color: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        z-index: 3000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Login Form Handler
document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = e.target[0].value;
    const password = e.target[1].value;
    await login(email, password);
});

// Register Form Handler
document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = {
        full_name: e.target[0].value,
        email: e.target[1].value,
        phone: e.target[2].value,
        password: e.target[3].value,
        role: e.target[4].value,
        action: 'register'
    };
    
    try {
        const response = await fetch(`${API_BASE}/auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Registration successful! Please login.', 'success');
            closeModal('registerModal');
            setTimeout(() => showLogin(), 500);
        } else {
            showToast(data.error || 'Registration failed', 'error');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showToast('Registration failed. Please try again.', 'error');
    }
});

// Contact Form Handler
document.getElementById('contactForm')?.addEventListener('submit', (e) => {
    e.preventDefault();
    showToast('Message sent! We will get back to you soon.', 'success');
    e.target.reset();
});
