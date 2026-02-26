// Admin Dashboard JavaScript

const API_BASE = '../api';
let currentNGO = null;

document.addEventListener('DOMContentLoaded', () => {
    checkAdminAuth();
    loadDashboardData();
});

function checkAdminAuth() {
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user'));
    
    if (!token || !user || user.role !== 'admin') {
        window.location.href = '../index.html';
        return;
    }
    
    document.getElementById('adminName').textContent = user.name;
}

async function loadDashboardData() {
    await Promise.all([
        loadKPIs(),
        loadPendingNGOs(),
        loadPendingDonations(),
        loadFraudFlags(),
        loadAuditLogs(),
        loadAnalytics()
    ]);
}

async function loadKPIs() {
    try {
        const response = await fetch(`${API_BASE}/admin-stats.php`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        
        document.getElementById('totalNGOs').textContent = data.total_ngos;
        document.getElementById('pendingApprovals').textContent = data.pending_approvals;
        document.getElementById('totalDonations').textContent = `₹${data.total_donations.toLocaleString()}`;
        document.getElementById('fraudFlags').textContent = data.fraud_flags;
    } catch (error) {
        console.error('Failed to load KPIs');
    }
}

async function loadPendingNGOs() {
    try {
        const response = await fetch(`${API_BASE}/admin-ngos.php?status=pending,under_review,incomplete`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const text = await response.text();
        console.log('NGOs raw response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse NGOs JSON:', e);
            console.error('Response text:', text);
            document.getElementById('ngoApprovalsTable').innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Error parsing response</td></tr>';
            return;
        }
        
        if (data.error) {
            console.error('API Error:', data.error);
            document.getElementById('ngoApprovalsTable').innerHTML = `<tr><td colspan="6" style="text-align: center; color: red;">${data.error}</td></tr>`;
            return;
        }
        
        const tbody = document.getElementById('ngoApprovalsTable');
        
        if (data.ngos && data.ngos.length > 0) {
            tbody.innerHTML = data.ngos.map(ngo => `
                <tr>
                    <td>${ngo.ngo_name}</td>
                    <td>${ngo.registration_number}</td>
                    <td>
                        <div class="trust-score">
                            <span class="score-value">${ngo.trust_score || 0}</span>
                            <div class="score-bar">
                                <div class="score-fill" style="width: ${ngo.trust_score || 0}%"></div>
                            </div>
                        </div>
                    </td>
                    <td>${ngo.approval_count || 0}/1</td>
                    <td><span class="status-badge status-${ngo.status || 'incomplete'}">${ngo.status || 'incomplete'}</span></td>
                    <td>
                        <button class="action-btn action-btn-primary" onclick="openApprovalModal('${ngo.id}')">
                            Review
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No pending NGO approvals</td></tr>';
        }
    } catch (error) {
        console.error('Failed to load NGOs', error);
        document.getElementById('ngoApprovalsTable').innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Error loading NGOs</td></tr>';
    }
}

async function loadPendingDonations() {
    try {
        const response = await fetch(`${API_BASE}/admin-donations.php?status=pending_verification`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        const tbody = document.getElementById('donationsTable');
        
        if (data.error) {
            console.error('API Error:', data.error);
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: red;">${data.error}</td></tr>`;
            return;
        }
        
        if (data.donations && data.donations.length > 0) {
            tbody.innerHTML = data.donations.map(donation => `
                <tr>
                    <td>${donation.donor_name || 'Anonymous'}</td>
                    <td>${donation.ngo_name}</td>
                    <td>₹${parseFloat(donation.amount).toLocaleString('en-IN')}</td>
                    <td>${donation.transaction_id}</td>
                    <td>${new Date(donation.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="action-btn action-btn-primary" onclick="verifyDonation('${donation.id}', 'approve')">
                            Approve
                        </button>
                        <button class="action-btn action-btn-danger" onclick="verifyDonation('${donation.id}', 'reject')">
                            Reject
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No pending donations</td></tr>';
        }
    } catch (error) {
        console.error('Failed to load donations', error);
        document.getElementById('donationsTable').innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Error loading donations</td></tr>';
    }
}

async function openApprovalModal(ngoId) {
    try {
        const response = await fetch(`${API_BASE}/ngo-details.php?id=${ngoId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const ngo = await response.json();
        currentNGO = ngo;
        
        // Fetch documents
        const docsResponse = await fetch(`${API_BASE}/admin-ngo-documents.php?ngo_id=${ngoId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        const docsData = await docsResponse.json();
        
        let documentsHtml = '<h4 style="margin-top: 1.5rem;">Uploaded Documents:</h4>';
        if (docsData.documents && docsData.documents.length > 0) {
            documentsHtml += '<div style="margin-top: 1rem;">';
            docsData.documents.forEach(doc => {
                const viewUrl = doc.file_path.startsWith('uploads/') ? '../' + doc.file_path : doc.file_path;
                documentsHtml += `
                    <div style="padding: 0.75rem; background: #F3F4F6; border-radius: 8px; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>${doc.document_type.replace(/_/g, ' ').toUpperCase()}</strong>
                            <br>
                            <small>${doc.verified ? '✅ Verified' : '⏳ Pending'}</small>
                        </div>
                        <a href="${viewUrl}" target="_blank" class="btn btn-outline" style="font-size: 0.875rem;">View Document</a>
                    </div>
                `;
            });
            documentsHtml += '</div>';
        } else {
            documentsHtml += '<p style="color: #6B7280;">No documents uploaded</p>';
        }
        
        document.getElementById('ngoDetails').innerHTML = `
            <div class="ngo-details">
                <p><strong>Name:</strong> ${ngo.ngo_name}</p>
                <p><strong>Registration:</strong> ${ngo.registration_number}</p>
                <p><strong>PAN:</strong> ${ngo.pan_number}</p>
                <p><strong>UPI ID:</strong> ${ngo.upi_id}</p>
                <p><strong>Description:</strong> ${ngo.description || 'N/A'}</p>
                <p><strong>Address:</strong> ${ngo.address || 'N/A'}, ${ngo.city || ''}, ${ngo.state || ''}</p>
                <p><strong>Website:</strong> ${ngo.website || 'N/A'}</p>
                <p><strong>Founded:</strong> ${ngo.founded_year || 'N/A'}</p>
                <p><strong>80G Certificate:</strong> ${ngo.has_80g ? 'Yes' : 'No'}</p>
                <p><strong>Trust Score:</strong> ${ngo.trust_score || 0}/100</p>
                ${documentsHtml}
            </div>
        `;
        
        document.getElementById('approvalModal').classList.add('active');
    } catch (error) {
        console.error('Error loading NGO details:', error);
        showToast('Failed to load NGO details', 'error');
    }
}

async function approveNGO() {
    await submitApproval('approve');
}

async function rejectNGO() {
    await submitApproval('reject');
}

async function submitApproval(action) {
    const comments = document.querySelector('#approvalForm textarea').value;
    
    try {
        const response = await fetch(`${API_BASE}/ngo-approval.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                ngo_id: currentNGO.id,
                action: action,
                comments: comments
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('approvalModal');
            loadPendingNGOs();
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('Approval failed', 'error');
    }
}

async function verifyDonation(donationId, action) {
    const reason = action === 'reject' ? prompt('Rejection reason:') : '';
    
    try {
        const response = await fetch(`${API_BASE}/verify-donation.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                donation_id: donationId,
                action: action,
                rejection_reason: reason
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            loadPendingDonations();
        } else {
            showToast(data.error, 'error');
        }
    } catch (error) {
        showToast('Verification failed', 'error');
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

async function loadFraudFlags() {
    try {
        const response = await fetch(`${API_BASE}/fraud-flags.php?status=active`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        const tbody = document.getElementById('fraudFlagsTable');
        
        if (data.flags && data.flags.length > 0) {
            tbody.innerHTML = data.flags.map(flag => `
                <tr>
                    <td>${flag.entity_type}</td>
                    <td>${flag.entity_name || 'N/A'}</td>
                    <td>${flag.flag_type}</td>
                    <td><span class="severity-badge severity-${flag.severity}">${flag.severity}</span></td>
                    <td>${flag.description}</td>
                    <td>${new Date(flag.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="action-btn action-btn-primary" onclick="resolveFraudFlag('${flag.id}')">
                            Resolve
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No active fraud flags</td></tr>';
        }
    } catch (error) {
        console.error('Failed to load fraud flags', error);
        document.getElementById('fraudFlagsTable').innerHTML = '<tr><td colspan="7" style="text-align: center;">Error loading fraud flags</td></tr>';
    }
}

async function loadAuditLogs() {
    try {
        const response = await fetch(`${API_BASE}/admin-stats.php?action=audit_logs&limit=50`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const data = await response.json();
        const tbody = document.getElementById('auditLogsTable');
        
        if (data.logs && data.logs.length > 0) {
            tbody.innerHTML = data.logs.map(log => `
                <tr>
                    <td>${log.admin_name || 'System'}</td>
                    <td>${log.action_type}</td>
                    <td>${log.entity_type}</td>
                    <td>${log.details || '-'}</td>
                    <td>${log.ip_address}</td>
                    <td>${new Date(log.created_at).toLocaleString()}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No audit logs available</td></tr>';
        }
    } catch (error) {
        console.error('Failed to load audit logs', error);
        document.getElementById('auditLogsTable').innerHTML = '<tr><td colspan="6" style="text-align: center;">Error loading audit logs</td></tr>';
    }
}

async function resolveFraudFlag(flagId) {
    const resolution = prompt('Enter resolution notes:');
    if (!resolution) return;
    
    try {
        const response = await fetch(`${API_BASE}/fraud-flags.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({
                action: 'resolve',
                flag_id: flagId,
                resolution_notes: resolution
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Fraud flag resolved', 'success');
            loadFraudFlags();
            loadKPIs();
        } else {
            showToast(data.error || 'Failed to resolve flag', 'error');
        }
    } catch (error) {
        showToast('Failed to resolve fraud flag', 'error');
    }
}

// Analytics and Charts
let charts = {};

async function loadAnalytics() {
    try {
        const response = await fetch(`${API_BASE}/analytics.php`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        
        const text = await response.text();
        console.log('Analytics raw response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse analytics JSON:', e);
            console.error('Response text:', text);
            return;
        }
        
        if (data.error) {
            console.error('Analytics error:', data.error);
            return;
        }
        
        console.log('Analytics data:', data);
        
        renderDonationsChart(data.donations_per_month || []);
        renderRevenueChart(data.revenue_by_year || []);
        renderApprovalsChart(data.approval_status || []);
        renderFraudChart(data.fraud_per_month || []);
        
    } catch (error) {
        console.error('Failed to load analytics', error);
    }
}

function renderDonationsChart(data) {
    const ctx = document.getElementById('donationsChart');
    
    if (!ctx) return;
    
    if (charts.donations) {
        charts.donations.destroy();
    }
    
    if (!data || data.length === 0) {
        ctx.parentElement.innerHTML = '<p style="text-align: center; color: #6B7280; padding: 2rem;">No donation data available</p>';
        return;
    }
    
    const months = data.map(d => {
        const [year, month] = d.month.split('-');
        return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    });
    const counts = data.map(d => parseInt(d.count));
    
    charts.donations = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Number of Donations',
                data: counts,
                borderColor: '#1E3A8A',
                backgroundColor: 'rgba(30, 58, 138, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

function renderRevenueChart(data) {
    const ctx = document.getElementById('revenueChart');
    
    if (!ctx) return;
    
    if (charts.revenue) {
        charts.revenue.destroy();
    }
    
    if (!data || data.length === 0) {
        ctx.parentElement.innerHTML = '<p style="text-align: center; color: #6B7280; padding: 2rem;">No revenue data available</p>';
        return;
    }
    
    const labels = data.map(d => {
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${monthNames[d.month - 1]} ${d.year}`;
    });
    const amounts = data.map(d => parseFloat(d.total));
    
    charts.revenue = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (₹)',
                data: amounts,
                backgroundColor: '#10B981',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₹' + context.parsed.y.toLocaleString('en-IN');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000) {
                                return '₹' + (value / 1000) + 'K';
                            }
                            return '₹' + value;
                        }
                    }
                }
            }
        }
    });
}

function renderApprovalsChart(data) {
    const ctx = document.getElementById('approvalsChart');
    
    if (!ctx) return;
    
    if (charts.approvals) {
        charts.approvals.destroy();
    }
    
    if (!data || data.length === 0) {
        ctx.parentElement.innerHTML = '<p style="text-align: center; color: #6B7280; padding: 2rem;">No approval data available</p>';
        return;
    }
    
    const statusColors = {
        'approved': '#10B981',
        'pending': '#F59E0B',
        'under_review': '#3B82F6',
        'rejected': '#EF4444',
        'suspended': '#6B7280'
    };
    
    const labels = data.map(d => d.status.replace('_', ' ').toUpperCase());
    const counts = data.map(d => parseInt(d.count));
    const colors = data.map(d => statusColors[d.status] || '#6B7280');
    
    charts.approvals = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 8,
                        font: {
                            size: 10
                        }
                    }
                }
            }
        }
    });
}

function renderFraudChart(data) {
    const ctx = document.getElementById('fraudChart');
    
    if (charts.fraud) {
        charts.fraud.destroy();
    }
    
    if (!data || data.length === 0) {
        ctx.parentElement.innerHTML = '<p style="text-align: center; color: #6B7280; padding: 2rem;">No fraud data available</p>';
        return;
    }
    
    // Group by month
    const monthlyData = {};
    data.forEach(item => {
        if (!monthlyData[item.month]) {
            monthlyData[item.month] = { low: 0, medium: 0, high: 0, critical: 0 };
        }
        monthlyData[item.month][item.severity] = parseInt(item.count);
    });
    
    const months = Object.keys(monthlyData).map(m => {
        const [year, month] = m.split('-');
        return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
    });
    
    charts.fraud = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Critical',
                    data: Object.values(monthlyData).map(d => d.critical || 0),
                    borderColor: '#DC2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'High',
                    data: Object.values(monthlyData).map(d => d.high || 0),
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Medium',
                    data: Object.values(monthlyData).map(d => d.medium || 0),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}
