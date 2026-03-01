// NGO Dashboard JavaScript

// Hardcoded Railway backend URL for production
const API_BASE = 'https://trustbridge-fundraiser-production.up.railway.app/api';
let currentNGO = null;
let currentUser = null;

document.addEventListener('DOMContentLoaded', () => {
    checkNGOAuth();
    loadNGOData();
});

function checkNGOAuth() {
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user'));
    
    if (!token || !user || user.role !== 'ngo') {
        window.location.href = '../index.html';
        return;
    }
    
    currentUser = user;
    document.getElementById('ngoName').textContent = user.name;
}

async function loadNGOData() {
    await Promise.all([
        loadNGOProfile(),
        loadNGOStats(),
        loadDocuments(),
        loadCampaigns(),
        loadDonations()
    ]);
}

async function loadNGOStats() {
    try {
        const response = await fetch(`${API_BASE}/ngo-profile.php`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        
        if (data.ngo) {
            // Calculate total raised from donations
            const donationsResponse = await fetch(`${API_BASE}/ngo-donations.php`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });
            
            const donationsData = await donationsResponse.json();
            const approvedDonations = donationsData.donations?.filter(d => d.verification_status === 'approved') || [];
            const totalRaised = approvedDonations.reduce((sum, d) => sum + parseFloat(d.amount), 0);
            const donationCount = approvedDonations.length;
            
            // Update stats
            document.getElementById('totalRaised').textContent = `₹${totalRaised.toLocaleString('en-IN')}`;
            document.getElementById('donationCount').textContent = donationCount;
            document.getElementById('trustScore').textContent = data.ngo.trust_score || 0;
            
            // Count active campaigns
            const campaignsResponse = await fetch(`${API_BASE}/ngo-campaigns.php`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });
            const campaignsData = await campaignsResponse.json();
            const activeCampaigns = campaignsData.campaigns?.filter(c => c.status === 'active').length || 0;
            document.getElementById('activeCampaigns').textContent = activeCampaigns;
        }
    } catch (error) {
        console.error('Failed to load NGO stats', error);
    }
}

async function loadNGOProfile() {
    try {
        const response = await fetch(`${API_BASE}/ngo-profile.php`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        
        if (data.ngo) {
            currentNGO = data.ngo;
            populateProfileForm(data.ngo);
            updateStatusAlert(data.ngo.status);
        } else {
            updateStatusAlert('incomplete');
        }
    } catch (error) {
        console.error('Failed to load NGO profile', error);
    }
}

function populateProfileForm(ngo) {
    const form = document.getElementById('ngoProfileForm');
    Object.keys(ngo).forEach(key => {
        const input = form.elements[key];
        if (input) {
            if (input.type === 'checkbox') {
                input.checked = ngo[key] == 1;
            } else {
                input.value = ngo[key] || '';
            }
        }
    });
}

function updateStatusAlert(status) {
    const alert = document.getElementById('statusAlert');
    const submitSection = document.getElementById('submitVerificationSection');
    
    const messages = {
        'incomplete': {
            text: '<i class="fas fa-exclamation-circle"></i> Please complete your NGO profile and upload required documents to submit for approval.',
            class: 'alert-warning'
        },
        'pending': {
            text: '<i class="fas fa-clock"></i> Your NGO profile is pending admin approval. You will be notified once approved.',
            class: 'alert-info'
        },
        'under_review': {
            text: '<i class="fas fa-search"></i> Your NGO is under review by our admin team.',
            class: 'alert-info'
        },
        'approved': {
            text: '<i class="fas fa-check-circle"></i> Your NGO is approved! You can now create campaigns and receive donations.',
            class: 'alert-success'
        },
        'rejected': {
            text: '<i class="fas fa-times-circle"></i> Your NGO application was rejected. Please contact support for details.',
            class: 'alert-error'
        }
    };
    
    const msg = messages[status] || messages['incomplete'];
    alert.innerHTML = `<div class="${msg.class}">${msg.text}</div>`;
    
    // Show submit button only if profile is complete but not yet submitted
    if (!status || status === 'incomplete') {
        submitSection.style.display = 'block';
    } else {
        submitSection.style.display = 'none';
    }
}

// Profile Form Handler
document.getElementById('ngoProfileForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    data.has_80g = formData.get('has_80g') ? 1 : 0;
    
    try {
        const response = await fetch(`${API_BASE}/ngo-profile.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Profile saved successfully!', 'success');
            loadNGOProfile();
        } else {
            showToast(result.error || 'Failed to save profile', 'error');
        }
    } catch (error) {
        showToast('Failed to save profile', 'error');
    }
});

// Submit for Verification
async function submitForVerification() {
    if (!currentNGO) {
        showToast('Please complete your profile first', 'error');
        return;
    }
    
    if (!confirm('Are you sure you want to submit your NGO for admin verification? Make sure all information is correct.')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/ngo-profile.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                action: 'submit_verification'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Submitted for verification! Admin will review your application.', 'success');
            loadNGOProfile();
        } else {
            showToast(result.error || 'Failed to submit', 'error');
        }
    } catch (error) {
        showToast('Failed to submit for verification', 'error');
    }
}

// Documents
async function loadDocuments() {
    try {
        const response = await fetch(`${API_BASE}/ngo-documents.php`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        const container = document.getElementById('documentsList');
        
        if (data.documents && data.documents.length > 0) {
            container.innerHTML = data.documents.map(doc => {
                // Fix the path for viewing
                let viewUrl = doc.document_url;
                
                // If it's a local upload (starts with uploads/), make it relative to web root
                if (viewUrl.startsWith('uploads/')) {
                    // From ngo/dashboard.html, go to root then to uploads
                    viewUrl = '../' + viewUrl;
                }
                
                console.log('Document URL:', viewUrl); // Debug log
                
                return `
                    <div class="document-item glass" style="padding: 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>${doc.document_type.replace(/_/g, ' ').toUpperCase()}</strong>
                            <br>
                            <small style="color: #6B7280;">
                                ${doc.verified ? '<i class="fas fa-check-circle" style="color: #059669;"></i> Verified' : '<i class="fas fa-clock" style="color: #F59E0B;"></i> Pending Verification'}
                                <br>
                                File: ${doc.file_name}
                            </small>
                        </div>
                        <div>
                            <a href="${viewUrl}" target="_blank" class="btn btn-outline" style="margin-right: 0.5rem;">View</a>
                            <button class="btn btn-outline" onclick="downloadDocument('${viewUrl}', '${doc.file_name}')">Download</button>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<p style="color: #6B7280;">No documents uploaded yet</p>';
        }
    } catch (error) {
        console.error('Failed to load documents', error);
    }
}

function downloadDocument(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
}

document.getElementById('documentUploadForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const uploadMethod = formData.get('upload_method');
    
    // Validate based on upload method
    if (uploadMethod === 'file') {
        const file = formData.get('document_file');
        if (!file || file.size === 0) {
            showToast('Please select a file to upload', 'error');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showToast('File size must be less than 5MB', 'error');
            return;
        }
    } else {
        const url = formData.get('document_url');
        if (!url) {
            showToast('Please provide a document URL', 'error');
            return;
        }
    }
    
    try {
        const response = await fetch(`${API_BASE}/ngo-documents.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Document uploaded successfully!', 'success');
            e.target.reset();
            document.getElementById('fileUploadSection').style.display = 'block';
            document.getElementById('urlUploadSection').style.display = 'none';
            loadDocuments();
        } else {
            showToast(result.error || 'Failed to upload document', 'error');
        }
    } catch (error) {
        console.error('Upload error:', error);
        showToast('Failed to upload document', 'error');
    }
});

function toggleUploadMethod() {
    const method = document.querySelector('input[name="upload_method"]:checked').value;
    const fileSection = document.getElementById('fileUploadSection');
    const urlSection = document.getElementById('urlUploadSection');
    
    if (method === 'file') {
        fileSection.style.display = 'block';
        urlSection.style.display = 'none';
        document.querySelector('input[name="document_file"]').required = true;
        document.querySelector('input[name="document_url"]').required = false;
    } else {
        fileSection.style.display = 'none';
        urlSection.style.display = 'block';
        document.querySelector('input[name="document_file"]').required = false;
        document.querySelector('input[name="document_url"]').required = true;
    }
}

// Campaigns
async function loadCampaigns() {
    try {
        const response = await fetch(`${API_BASE}/ngo-campaigns.php`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        const container = document.getElementById('campaignsList');
        
        if (data.campaigns && data.campaigns.length > 0) {
            container.innerHTML = data.campaigns.map(campaign => `
                <div class="campaign-card glass" style="padding: 1.5rem; margin-bottom: 1rem;">
                    <h3>${campaign.title}</h3>
                    <p>${campaign.description}</p>
                    <div class="progress-bar" style="margin: 1rem 0;">
                        <div class="progress-fill" style="width: ${(campaign.raised_amount / campaign.goal_amount) * 100}%"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>₹${parseFloat(campaign.raised_amount || 0).toLocaleString('en-IN')} raised</span>
                        <span>Goal: ₹${parseFloat(campaign.goal_amount).toLocaleString('en-IN')}</span>
                    </div>
                    <span class="status-badge status-${campaign.status}" style="margin-top: 1rem; display: inline-block;">${campaign.status}</span>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p style="color: #6B7280;">No campaigns created yet</p>';
        }
    } catch (error) {
        console.error('Failed to load campaigns', error);
    }
}

function showCreateCampaign() {
    if (!currentNGO || currentNGO.status !== 'approved') {
        showToast('Your NGO must be approved before creating campaigns', 'error');
        return;
    }
    document.getElementById('campaignModal').classList.add('active');
}

document.getElementById('campaignForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch(`${API_BASE}/ngo-campaigns.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Campaign created successfully!', 'success');
            closeModal('campaignModal');
            e.target.reset();
            loadCampaigns();
        } else {
            showToast(result.error || 'Failed to create campaign', 'error');
        }
    } catch (error) {
        showToast('Failed to create campaign', 'error');
    }
});

// Donations
async function loadDonations() {
    try {
        const response = await fetch(`${API_BASE}/ngo-donations.php`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        const tbody = document.getElementById('donationsTable');
        
        if (data.donations && data.donations.length > 0) {
            tbody.innerHTML = data.donations.map(donation => `
                <tr>
                    <td>${donation.donor_name || 'Anonymous'}</td>
                    <td>₹${parseFloat(donation.amount).toLocaleString('en-IN')}</td>
                    <td>${donation.transaction_id}</td>
                    <td><span class="status-badge status-${donation.verification_status}">${donation.verification_status}</span></td>
                    <td>${new Date(donation.created_at).toLocaleDateString()}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No donations received yet</td></tr>';
        }
    } catch (error) {
        console.error('Failed to load donations', error);
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function logout() {
    localStorage.clear();
    window.location.href = '../index.html';
}

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
    setTimeout(() => toast.remove(), 3000);
}
