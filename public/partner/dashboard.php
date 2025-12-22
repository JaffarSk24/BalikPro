<?php
require_once __DIR__ . '/../../autoload.php';
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner Dashboard - Balík PRO</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .dashboard {
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .dashboard-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 1rem 0;
        }
        
        .dashboard-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dashboard-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2563eb;
        }
        
        .dashboard-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .user-role {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .logout-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .logout-btn:hover {
            background: #b91c1c;
        }
        
        .dashboard-content {
            padding: 2rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }
        
        .stat-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-title {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #1f2937;
        }
        
        .stat-value.success {
            color: #059669;
        }
        
        .stat-value.warning {
            color: #d97706;
        }
        
        .stat-value.error {
            color: #dc2626;
        }
        
        .section {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .section-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .section-content {
            padding: 2rem;
        }
        
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            background: white;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-redeemed {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-expired {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-revoked {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .type-main {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .type-bonus {
            background: #fef3c7;
            color: #92400e;
        }
        
        .pagination {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-top: 2rem;
        }
        
        .pagination-info {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .pagination-controls {
            display: flex;
            gap: 0.5rem;
        }
        
        .pagination-btn {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .pagination-btn:hover:not(:disabled) {
            background: #f3f4f6;
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-btn.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        
        .loading {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .empty-state svg {
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .export-btn {
            background: #059669;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .export-btn:hover {
            background: #047857;
        }
        
        @media (max-width: 768px) {
            .dashboard-nav {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .filters {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="container">
                <div class="dashboard-nav">
                    <div class="dashboard-logo">Balík PRO - Partner</div>
                    <div class="dashboard-user">
                        <div class="user-info">
                            <div class="user-name" id="user-name">Loading...</div>
                            <div class="user-role">Partner</div>
                        </div>
                        <button class="logout-btn" onclick="logout()">Odhlásiť sa</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="dashboard-content">
            <div class="container">
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-title">Vydané kupóny</div>
                        <div class="stat-value" id="stat-issued">-</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">Aktívne kupóny</div>
                        <div class="stat-value warning" id="stat-active">-</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">Aktivované kupóny</div>
                        <div class="stat-value success" id="stat-redeemed">-</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">K výplate</div>
                        <div class="stat-value" id="stat-amount">-</div>
                    </div>
                </div>

                <!-- Chart Section -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Prehľad aktivácií (posledných 30 dní)</h2>
                    </div>
                    <div class="section-content">
                        <canvas id="redemptionChart" style="width: 100%; height: 300px;"></canvas>
                    </div>
                </div>

                <!-- Coupons Section -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Kupóny</h2>
                        <button class="export-btn" onclick="exportCoupons()">
                            Export CSV
                        </button>
                    </div>
                    <div class="section-content">
                        <!-- Filters -->
                        <div class="filters">
                            <select id="status-filter" class="filter-select" onchange="loadCoupons()">
                                <option value="">Všetky stavy</option>
                                <option value="active">Aktívne</option>
                                <option value="redeemed">Aktivované</option>
                                <option value="expired">Vypršané</option>
                                <option value="revoked">Zrušené</option>
                            </select>
                            
                            <select id="month-filter" class="filter-select" onchange="loadCoupons()">
                                <option value="">Všetky mesiace</option>
                            </select>
                        </div>

                        <!-- Loading State -->
                        <div id="coupons-loading" class="loading">
                            <div class="spinner"></div>
                            <p>Načítavam kupóny...</p>
                        </div>

                        <!-- Coupons Table -->
                        <div id="coupons-table" class="table-responsive" style="display: none;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Objednávka</th>
                                        <th>Služba</th>
                                        <th>Zákazník</th>
                                        <th>Typ</th>
                                        <th>Status</th>
                                        <th>Vytvorené</th>
                                        <th>Aktivované</th>
                                    </tr>
                                </thead>
                                <tbody id="coupons-tbody">
                                    <!-- Coupons will be loaded here -->
                                </tbody>
                            </table>
                            
                            <div class="pagination">
                                <div class="pagination-info" id="pagination-info">
                                    <!-- Pagination info -->
                                </div>
                                <div class="pagination-controls" id="pagination-controls">
                                    <!-- Pagination buttons -->
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div id="coupons-empty" class="empty-state" style="display: none;">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                            <h3>Žiadne kupóny</h3>
                            <p>V tejto kategórii sa nenašli žiadne kupóny.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let partnerId = null;
        let partnerToken = null;
        let partnerInfo = null;
        let currentPage = 1;
        let chartInstance = null;

        // Initialize dashboard
        window.addEventListener('DOMContentLoaded', function() {
            // Check authentication
            partnerToken = localStorage.getItem('partner_token');
            const storedInfo = localStorage.getItem('partner_info');
            
            if (!partnerToken || !storedInfo) {
                window.location.href = '/partner/';
                return;
            }
            
            try {
                partnerInfo = JSON.parse(storedInfo);
                partnerId = partnerInfo.id;
                
                document.getElementById('user-name').textContent = partnerInfo.name;
                
                loadDashboard();
                generateMonthFilter();
            } catch (error) {
                console.error('Parse partner info error:', error);
                logout();
            }
        });

        async function loadDashboard() {
            try {
                // Load stats
                const statsResponse = await fetch(`/api/partner/${partnerId}/dashboard`, {
                    headers: {
                        'Authorization': 'Bearer ' + partnerToken
                    }
                });

                if (statsResponse.ok) {
                    const statsData = await statsResponse.json();
                    if (statsData.success) {
                        updateStats(statsData.data);
                        renderChart(statsData.data.charts.by_day);
                    }
                }

                // Load coupons
                loadCoupons();

            } catch (error) {
                console.error('Dashboard load error:', error);
                showAlert('Chyba pri načítaní dát', 'error');
            }
        }

        function updateStats(stats) {
            document.getElementById('stat-issued').textContent = stats.totals.issued || 0;
            document.getElementById('stat-active').textContent = stats.totals.active || 0;
            document.getElementById('stat-redeemed').textContent = stats.totals.redeemed || 0;
            document.getElementById('stat-amount').textContent = formatPrice(stats.totals.amount_due || 0);
        }

        function renderChart(data) {
            const ctx = document.getElementById('redemptionChart').getContext('2d');
            
            // Prepare data: Fill missing dates if needed, but for MVP just plot what we have
            const labels = data.map(item => formatDateShort(item.date));
            const values = data.map(item => item.redeemed);

            if (chartInstance) {
                chartInstance.destroy();
            }

            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Aktivované kupóny',
                        data: values,
                        backgroundColor: 'rgba(37, 99, 235, 0.5)',
                        borderColor: 'rgba(37, 99, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
        
        function formatDateShort(dateString) {
            return new Date(dateString).toLocaleDateString('sk-SK', { day: 'numeric', month: 'short' });
        }

        async function loadCoupons(page = 1) {
            const loading = document.getElementById('coupons-loading');
            const table = document.getElementById('coupons-table');
            const empty = document.getElementById('coupons-empty');
            
            // Show loading
            loading.style.display = 'block';
            table.style.display = 'none';
            empty.style.display = 'none';

            try {
                const status = document.getElementById('status-filter').value;
                const month = document.getElementById('month-filter').value;
                
                const params = new URLSearchParams({
                    page: page,
                    per_page: 20
                });
                
                if (status) params.append('status', status);
                if (month) params.append('month', month);

                const response = await fetch(`/api/partner/${partnerId}/coupons?${params}`, {
                    headers: {
                        'Authorization': 'Bearer ' + partnerToken
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load coupons');
                }

                const data = await response.json();
                
                if (data.success) {
                    renderCoupons(data.data);
                } else {
                    throw new Error(data.message || 'Unknown error');
                }

            } catch (error) {
                console.error('Load coupons error:', error);
                showAlert('Chyba pri načítaní kupónov', 'error');
                empty.style.display = 'block';
            } finally {
                loading.style.display = 'none';
            }
        }

        function renderCoupons(data) {
            const table = document.getElementById('coupons-table');
            const empty = document.getElementById('coupons-empty');
            const tbody = document.getElementById('coupons-tbody');
            
            if (!data.items || data.items.length === 0) {
                empty.style.display = 'block';
                return;
            }

            tbody.innerHTML = data.items.map(coupon => `
                <tr>
                    <td>${coupon.id}</td>
                    <td>${escapeHtml(coupon.order_number)}</td>
                    <td>${escapeHtml(coupon.service_title)}</td>
                    <td>${escapeHtml(coupon.customer_name)}</td>
                    <td><span class="type-badge type-${coupon.type}">${coupon.type === 'main' ? 'Hlavná' : 'Bonus'}</span></td>
                    <td><span class="status-badge status-${coupon.status}">${getStatusLabel(coupon.status)}</span></td>
                    <td>${formatDate(coupon.created_at)}</td>
                    <td>${coupon.redeemed_at ? formatDate(coupon.redeemed_at) : '-'}</td>
                </tr>
            `).join('');

            updatePagination(data);
            table.style.display = 'block';
        }

        function updatePagination(data) {
            const info = document.getElementById('pagination-info');
            const controls = document.getElementById('pagination-controls');
            
            const start = (data.page - 1) * data.per_page + 1;
            const end = Math.min(data.page * data.per_page, data.total);
            
            info.textContent = `Zobrazené ${start}-${end} z ${data.total}`;
            
            const totalPages = Math.ceil(data.total / data.per_page);
            
            let buttons = '';
            
            // Previous button
            buttons += `<button class="pagination-btn" ${data.page <= 1 ? 'disabled' : ''} onclick="changePage(${data.page - 1})">Predchádzajúca</button>`;
            
            // Page numbers
            for (let i = Math.max(1, data.page - 2); i <= Math.min(totalPages, data.page + 2); i++) {
                buttons += `<button class="pagination-btn ${i === data.page ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
            }
            
            // Next button
            buttons += `<button class="pagination-btn" ${data.page >= totalPages ? 'disabled' : ''} onclick="changePage(${data.page + 1})">Nasledujúca</button>`;
            
            controls.innerHTML = buttons;
        }

        function changePage(page) {
            currentPage = page;
            loadCoupons(page);
        }

        async function exportCoupons() {
            try {
                const status = document.getElementById('status-filter').value;
                const month = document.getElementById('month-filter').value;
                
                const params = new URLSearchParams({
                    export: 'csv',
                    format: 'csv'
                });
                
                if (status) params.append('status', status);
                if (month) params.append('month', month);

                const url = `/api/partner/${partnerId}/coupons?${params}`;
                
                // Create a temporary link and trigger download
                const link = document.createElement('a');
                link.href = url;
                link.style.display = 'none';
                
                // Set authorization header by adding it to the request
                const response = await fetch(url, {
                    headers: {
                        'Authorization': 'Bearer ' + partnerToken
                    }
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const blobUrl = URL.createObjectURL(blob);
                    
                    link.href = blobUrl;
                    link.download = 'coupons_export.csv';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    URL.revokeObjectURL(blobUrl);
                } else {
                    throw new Error('Export failed');
                }

            } catch (error) {
                console.error('Export error:', error);
                showAlert('Chyba pri exporte', 'error');
            }
        }

        function generateMonthFilter() {
            const select = document.getElementById('month-filter');
            const currentDate = new Date();
            
            for (let i = 0; i < 12; i++) {
                const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
                const monthYear = date.toLocaleDateString('sk-SK', { month: 'short', year: 'numeric' });
                
                const option = document.createElement('option');
                option.value = monthYear;
                option.textContent = monthYear;
                select.appendChild(option);
            }
        }

        function logout() {
            localStorage.removeItem('partner_token');
            localStorage.removeItem('partner_info');
            window.location.href = '/partner/';
        }

        // Utility functions
        function formatPrice(amount) {
            return new Intl.NumberFormat('sk-SK', {
                style: 'currency',
                currency: 'EUR'
            }).format(amount);
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('sk-SK', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function getStatusLabel(status) {
            const labels = {
                'active': 'Aktívny',
                'redeemed': 'Aktivovaný',
                'expired': 'Vypršaný',
                'revoked': 'Zrušený'
            };
            return labels[status] || status;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function showAlert(message, type = 'info') {
            const alert = document.createElement('div');
            alert.style.cssText = `
                position: fixed;
                top: 1rem;
                right: 1rem;
                z-index: 9999;
                max-width: 320px;
                padding: 1rem;
                border-radius: 0.5rem;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                background: ${type === 'error' ? '#fee2e2' : '#d1fae5'};
                color: ${type === 'error' ? '#991b1b' : '#065f46'};
                border: 1px solid ${type === 'error' ? '#fecaca' : '#a7f3d0'};
            `;
            
            alert.textContent = message;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 5000);
        }
    </script>
</body>
</html>
