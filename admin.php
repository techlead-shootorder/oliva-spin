<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Logout handling
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Oasis Spin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.1);
        }
        
        .input-field {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .input-field:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
            border-radius: 12px;
            padding: 8px 16px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(239, 68, 68, 0.4);
        }
        
        .coupon-item {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }
        
        .coupon-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 20px;
            color: white;
            text-align: center;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
        }
        
        .tab-button {
            padding: 12px 24px;
            border: none;
            background: transparent;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            border-bottom-color: #667eea;
            color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            margin: 5% auto;
            padding: 0;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 24px 24px 16px 24px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .close:hover {
            color: #000;
            background: rgba(0, 0, 0, 0.1);
        }
        
        .coupon-color-preview {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    <div class="min-h-screen p-4">
        <!-- Header -->
        <div class="max-w-6xl mx-auto mb-8">
            <div class="glass-card p-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Oasis Spin Admin Panel</h1>
                        <p class="text-gray-600">Manage your spinning wheel configuration and track performance</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-4">
                        <a href="index.php?recordedId=demo" class="btn-secondary">
                            <span>View Wheel</span>
                        </a>
                        <a href="?logout=1" class="btn-danger">
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="max-w-6xl mx-auto mb-8">
            <div class="glass-card">
                <div class="flex flex-wrap border-b border-gray-200">
                    <button class="tab-button active" onclick="showTab('dashboard')">Dashboard</button>
                    <button class="tab-button" onclick="showTab('coupons')">Discounts</button>
                    <button class="tab-button" onclick="showTab('spins')">Spin History</button>
                    <button class="tab-button" onclick="showTab('settings')">Settings</button>
                </div>
                
                <!-- Dashboard Tab -->
                <div id="dashboard" class="tab-content active p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="stats-card">
                            <div class="text-3xl font-bold mb-1" id="totalSpins">0</div>
                            <div class="text-sm opacity-90">Total Spins</div>
                        </div>
                        <div class="stats-card">
                            <div class="text-3xl font-bold mb-1" id="totalCoupons">0</div>
                            <div class="text-sm opacity-90">Active Discounts</div>
                        </div>
                        <div class="stats-card">
                            <div class="text-3xl font-bold mb-1" id="uniqueUsers">0</div>
                            <div class="text-sm opacity-90">Unique Users</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-card p-6">
                            <h3 class="text-xl font-bold mb-4">Recent Activity</h3>
                            <div id="recentActivity" class="space-y-3">
                                <!-- Recent activity will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="glass-card p-6">
                            <h3 class="text-xl font-bold mb-4">Weekly Statistics</h3>
                            <div id="weeklyStats" class="space-y-3">
                                <!-- Weekly stats will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Discounts Tab -->
                <div id="coupons" class="tab-content p-6">
                    <div class="glass-card p-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-xl font-bold">Discount Management</h3>
                                <div class="text-sm text-gray-600 mt-1">
                                    Current Week: <span id="currentWeekDisplay" class="font-bold text-indigo-600">1</span>
                                    <button onclick="updateWeek()" class="ml-2 text-indigo-600 hover:text-indigo-800 underline">Change Week</button>
                                </div>
                            </div>
                            <div class="flex space-x-3">
                                <a href="manage-coupon-codes.php" class="btn-secondary">
                                    Manage Codes
                                </a>
                                <a href="bulk-upload-coupons.php" class="btn-secondary">
                                    Bulk Upload
                                </a>
                                <button onclick="openAddCouponModal()" class="btn-primary">
                                    Add New Discount
                                </button>
                            </div>
                        </div>
                        
                        <!-- Discounts Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b-2 border-gray-200 bg-gray-50">
                                        <th class="text-left p-3 font-semibold">Discount</th>
                                        <th class="text-left p-3 font-semibold">Code</th>
                                        <th class="text-left p-3 font-semibold">Value</th>
                                        <th class="text-left p-3 font-semibold">Current Week %</th>
                                        <th class="text-left p-3 font-semibold">All Weeks</th>
                                        <th class="text-left p-3 font-semibold">Color</th>
                                        <th class="text-left p-3 font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="couponsTableBody">
                                    <!-- Discounts will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Spins Tab -->
                <div id="spins" class="tab-content p-6">
                    <div class="glass-card p-6">
                        <h3 class="text-xl font-bold mb-4">Spin History</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left p-3">Recorded ID</th>
                                        <th class="text-left p-3">Result</th>
                                        <th class="text-left p-3">Unique Coupon Code</th>
                                        <th class="text-left p-3">Timestamp</th>
                                        <th class="text-left p-3">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody id="spinsTableBody">
                                    <!-- Spins will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Tab -->
                <div id="settings" class="tab-content p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="glass-card p-6">
                            <h3 class="text-xl font-bold mb-4">Wheel Settings</h3>
                            <form id="settingsForm" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Wheel Title</label>
                                    <input type="text" id="wheelTitle" class="input-field" value="Spin the Wheel">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Wheel Description</label>
                                    <textarea id="wheelDescription" class="input-field" rows="3">Tap the center button to spin and win!</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Max Spins per User</label>
                                    <input type="number" id="maxSpins" class="input-field" min="1" value="1">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Enable Spin Tracking</label>
                                    <input type="checkbox" id="enableTracking" checked>
                                </div>
                                <button type="submit" class="btn-primary w-full">Save Settings</button>
                            </form>
                        </div>
                        
                        <div class="glass-card p-6">
                            <h3 class="text-xl font-bold mb-4">Admin Access</h3>
                            <form id="passwordForm" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                                    <input type="password" id="currentPassword" class="input-field" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                                    <input type="password" id="newPassword" class="input-field" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                                    <input type="password" id="confirmPassword" class="input-field" required>
                                </div>
                                <button type="submit" class="btn-primary w-full">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Discount Modal -->
    <div id="addCouponModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="text-2xl font-bold text-gray-800">Add New Discount</h2>
                <button class="close" onclick="closeAddCouponModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="couponForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Discount Text</label>
                            <input type="text" id="discountText" class="input-field" placeholder="e.g., 10K Discount" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Discount Value</label>
                            <input type="number" id="discountValue" class="input-field" placeholder="e.g., 10001" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Base Discount Code</label>
                        <input type="text" id="couponCode" class="input-field" placeholder="e.g., OASIS10K" required>
                        <small class="text-gray-500 text-xs mt-1">Identifier for this discount tier. You'll add specific codes later in "Manage Codes".</small>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Week 1 (%)</label>
                            <input type="number" id="week1Probability" class="input-field" min="0" max="100" step="0.01" placeholder="65.00" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Week 2 (%)</label>
                            <input type="number" id="week2Probability" class="input-field" min="0" max="100" step="0.01" placeholder="65.00" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Week 3 (%)</label>
                            <input type="number" id="week3Probability" class="input-field" min="0" max="100" step="0.01" placeholder="65.00" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Week 4 (%)</label>
                            <input type="number" id="week4Probability" class="input-field" min="0" max="100" step="0.01" placeholder="65.00" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                        <input type="color" id="couponColor" class="input-field" value="#667eea">
                    </div>
                    <div class="flex gap-4 pt-4">
                        <button type="button" onclick="closeAddCouponModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 btn-primary">
                            Add Discount
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Discount Modal -->
    <div id="editCouponModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="text-2xl font-bold text-gray-800">Edit Discount</h2>
                <button class="close" onclick="closeEditCouponModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editCouponForm" class="space-y-4">
                    <input type="hidden" id="editCouponId">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Discount Text</label>
                            <input type="text" id="editDiscountText" class="input-field" placeholder="e.g., 10K Discount" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Discount Value</label>
                            <input type="number" id="editDiscountValue" class="input-field" placeholder="e.g., 10001" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Base Discount Code</label>
                        <input type="text" id="editCouponCode" class="input-field" placeholder="e.g., OASIS10K" required>
                        <small class="text-gray-500 text-xs mt-1">Identifier for this discount tier. You'll add specific codes later in "Manage Codes".</small>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Week 1 (%)</label>
                            <input type="number" id="editWeek1Probability" class="input-field" min="0" max="100" step="0.01" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Week 2 (%)</label>
                            <input type="number" id="editWeek2Probability" class="input-field" min="0" max="100" step="0.01" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Week 3 (%)</label>
                            <input type="number" id="editWeek3Probability" class="input-field" min="0" max="100" step="0.01" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Week 4 (%)</label>
                            <input type="number" id="editWeek4Probability" class="input-field" min="0" max="100" step="0.01" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                        <input type="color" id="editCouponColor" class="input-field">
                    </div>
                    <div class="flex gap-4 pt-4">
                        <button type="button" onclick="closeEditCouponModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                            Update Discount
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Global data variables
        let coupons = [];
        let spins = [];
        let dashboardData = {};
        let currentWeek = 1;
        let settings = {
            wheelTitle: "🎯 Oasis Spin Wheel",
            wheelDescription: "Spin once and win amazing discounts on IVF treatments!",
            maxSpins: 1,
            enableTracking: true
        };
        
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
            
            // Load data for the selected tab
            if (tabName === 'dashboard') {
                loadDashboard();
            } else if (tabName === 'coupons') {
                loadCoupons();
            } else if (tabName === 'spins') {
                loadSpins();
            } else if (tabName === 'settings') {
                loadSettings();
            }
        }
        
        // Load dashboard data
        async function loadDashboard() {
            try {
                const response = await fetch('api/admin-data.php?action=dashboard');
                const data = await response.json();
                
                if (data.success) {
                    dashboardData = data.data;
                    
                    document.getElementById('totalSpins').textContent = dashboardData.totalSpins;
                    document.getElementById('totalCoupons').textContent = dashboardData.activeCoupons;
                    document.getElementById('uniqueUsers').textContent = dashboardData.uniqueUsers;
                    document.getElementById('currentWeekDisplay').textContent = dashboardData.currentWeek;
                    currentWeek = dashboardData.currentWeek;
                    
                    // Load recent activity
                    const recentActivity = document.getElementById('recentActivity');
                    recentActivity.innerHTML = '';
                    
                    if (dashboardData.recentActivity.length > 0) {
                        dashboardData.recentActivity.forEach(activity => {
                            const activityItem = document.createElement('div');
                            activityItem.className = 'flex justify-between items-center p-3 bg-gray-50 rounded-lg';
                            activityItem.innerHTML = `
                                <div>
                                    <span class="font-medium">User ${activity.recorded_id}</span>
                                    <span class="text-gray-600">won ${activity.result}</span>
                                </div>
                                <div class="text-sm text-gray-500">${formatDate(activity.timestamp)}</div>
                            `;
                            recentActivity.appendChild(activityItem);
                        });
                    } else {
                        recentActivity.innerHTML = '<div class="text-center text-gray-500 py-8">No activity yet</div>';
                    }
                    
                    // Load weekly statistics
                    const weeklyStats = document.getElementById('weeklyStats');
                    weeklyStats.innerHTML = '';
                    
                    if (dashboardData.weeklyStats && dashboardData.weeklyStats.length > 0) {
                        dashboardData.weeklyStats.forEach(stat => {
                            const statItem = document.createElement('div');
                            statItem.className = 'flex justify-between items-center p-3 bg-gray-50 rounded-lg';
                            statItem.innerHTML = `
                                <div>
                                    <div class="font-medium">${stat.discount_text}</div>
                                    <div class="text-sm text-gray-600">Probability: ${stat.probability}%</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-indigo-600">${stat.spin_count || 0}</div>
                                    <div class="text-xs text-gray-500">Spins</div>
                                </div>
                            `;
                            weeklyStats.appendChild(statItem);
                        });
                    } else {
                        weeklyStats.innerHTML = '<div class="text-center text-gray-500 py-8">No statistics yet</div>';
                    }
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }
        
        // Load coupons
        async function loadCoupons() {
            try {
                const response = await fetch('api/admin-data.php?action=coupons');
                const data = await response.json();
                
                if (data.success) {
                    coupons = data.coupons;
                    
                    const couponsTableBody = document.getElementById('couponsTableBody');
                    couponsTableBody.innerHTML = '';
                    
                    if (coupons.length > 0) {
                        coupons.forEach(coupon => {
                            const currentProb = coupon[`week${currentWeek}_probability`];
                            const row = document.createElement('tr');
                            row.className = 'border-b border-gray-100 hover:bg-gray-50 transition-colors';
                            row.innerHTML = `
                                <td class="p-3">
                                    <div class="font-medium" style="color: ${coupon.color}">${coupon.discount_text}</div>
                                </td>
                                <td class="p-3">
                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">${coupon.coupon_code}</code>
                                </td>
                                <td class="p-3 font-mono text-sm">${coupon.discount_value.toLocaleString()}</td>
                                <td class="p-3">
                                    <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-xs font-medium">${currentProb}%</span>
                                </td>
                                <td class="p-3 text-xs text-gray-600">
                                    W1: ${coupon.week1_probability}% | W2: ${coupon.week2_probability}%<br>
                                    W3: ${coupon.week3_probability}% | W4: ${coupon.week4_probability}%
                                </td>
                                <td class="p-3">
                                    <div class="coupon-color-preview" style="background-color: ${coupon.color}"></div>
                                </td>
                                <td class="p-3">
                                    <div class="flex gap-2">
                                        <button onclick="editCoupon(${coupon.id})" class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded transition-colors">
                                            Edit
                                        </button>
                                        <button onclick="deleteCoupon(${coupon.id})" class="btn-danger text-xs px-3 py-1">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            `;
                            couponsTableBody.appendChild(row);
                        });
                    } else {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td colspan="7" class="p-8 text-center text-gray-500">No coupons found. Click "Add New Coupon" to create one.</td>';
                        couponsTableBody.appendChild(row);
                    }
                }
            } catch (error) {
                console.error('Error loading coupons:', error);
            }
        }
        
        // Load spins
        async function loadSpins() {
            console.log('Loading spins data...');
            
            try {
                const response = await fetch('api/admin-data.php?action=spins');
                console.log('Spins API response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Spins data received:', data);
                
                if (data.success) {
                    spins = data.spins || [];
                    console.log('Number of spins:', spins.length);
                    
                    const spinsTableBody = document.getElementById('spinsTableBody');
                    spinsTableBody.innerHTML = '';
                    
                    if (spins.length > 0) {
                        spins.forEach((spin, index) => {
                            console.log(`Processing spin ${index}:`, spin);
                            const row = document.createElement('tr');
                            row.className = 'border-b border-gray-100 hover:bg-gray-50 transition-colors';
                            row.innerHTML = `
                                <td class="p-3 font-mono text-sm">${spin.recorded_id || 'N/A'}</td>
                                <td class="p-3">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                        ${spin.result || 'N/A'}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <code class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold">
                                        ${spin.coupon_code || 'N/A'}
                                    </code>
                                </td>
                                <td class="p-3 text-sm text-gray-600">${formatDate(spin.timestamp)}</td>
                                <td class="p-3 font-mono text-xs text-gray-500">${spin.ip_address || 'N/A'}</td>
                            `;
                            spinsTableBody.appendChild(row);
                        });
                        
                        // Add total count info
                        if (data.total && data.total > spins.length) {
                            const infoRow = document.createElement('tr');
                            infoRow.innerHTML = `<td colspan="4" class="p-3 text-center text-sm text-gray-500">Showing ${spins.length} of ${data.total} total spins</td>`;
                            spinsTableBody.appendChild(infoRow);
                        }
                    } else {
                        const row = document.createElement('tr');
                        row.innerHTML = '<td colspan="5" class="p-8 text-center text-gray-500">📊 No spins recorded yet. <br><small class="text-xs">Spins will appear here when users start using the wheel.</small></td>';
                        spinsTableBody.appendChild(row);
                    }
                } else {
                    console.error('API returned error:', data.error);
                    const spinsTableBody = document.getElementById('spinsTableBody');
                    spinsTableBody.innerHTML = '<td colspan="5" class="p-8 text-center text-red-500">❌ Error loading spins: ' + (data.error || 'Unknown error') + '</td>';
                }
            } catch (error) {
                console.error('Error loading spins:', error);
                const spinsTableBody = document.getElementById('spinsTableBody');
                spinsTableBody.innerHTML = '<td colspan="5" class="p-8 text-center text-red-500">❌ Failed to load spins data. Check console for details.</td>';
            }
        }
        
        // Load settings
        async function loadSettings() {
            try {
                const response = await fetch('api/admin-data.php?action=settings');
                const data = await response.json();
                
                if (data.success) {
                    settings = data.settings;
                    
                    document.getElementById('wheelTitle').value = settings.wheel_title || '🎯 Oasis Spin Wheel';
                    document.getElementById('wheelDescription').value = settings.wheel_description || 'Spin once and win amazing discounts on IVF treatments!';
                    document.getElementById('maxSpins').value = settings.max_spins || 1;
                    document.getElementById('enableTracking').checked = settings.enable_tracking === '1';
                    
                    if (data.weekData) {
                        currentWeek = data.weekData.current_week;
                        document.getElementById('currentWeekDisplay').textContent = currentWeek;
                    }
                }
            } catch (error) {
                console.error('Error loading settings:', error);
            }
        }
        
        // Add coupon
        document.getElementById('couponForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                action: 'add_coupon',
                discount_text: document.getElementById('discountText').value,
                discount_value: parseInt(document.getElementById('discountValue').value),
                coupon_code: document.getElementById('couponCode').value,
                week1_probability: parseFloat(document.getElementById('week1Probability').value),
                week2_probability: parseFloat(document.getElementById('week2Probability').value),
                week3_probability: parseFloat(document.getElementById('week3Probability').value),
                week4_probability: parseFloat(document.getElementById('week4Probability').value),
                color: document.getElementById('couponColor').value
            };
            
            try {
                const response = await fetch('api/admin-data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Clear form
                    document.getElementById('couponForm').reset();
                    document.getElementById('couponColor').value = '#667eea';
                    
                    // Reload coupons
                    loadCoupons();
                    
                    alert('Coupon added successfully!');
                } else {
                    alert('Error adding coupon: ' + data.error);
                }
            } catch (error) {
                console.error('Error adding coupon:', error);
                alert('Error adding coupon. Please try again.');
            }
        });
        
        // Edit coupon
        document.getElementById('editCouponForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                action: 'update_coupon',
                id: parseInt(document.getElementById('editCouponId').value),
                discount_text: document.getElementById('editDiscountText').value,
                discount_value: parseInt(document.getElementById('editDiscountValue').value),
                coupon_code: document.getElementById('editCouponCode').value,
                week1_probability: parseFloat(document.getElementById('editWeek1Probability').value),
                week2_probability: parseFloat(document.getElementById('editWeek2Probability').value),
                week3_probability: parseFloat(document.getElementById('editWeek3Probability').value),
                week4_probability: parseFloat(document.getElementById('editWeek4Probability').value),
                color: document.getElementById('editCouponColor').value
            };
            
            try {
                const response = await fetch('api/admin-data.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Reload coupons
                    loadCoupons();
                    
                    // Close modal
                    closeEditCouponModal();
                    
                    alert('Coupon updated successfully!');
                } else {
                    alert('Error updating coupon: ' + data.error);
                }
            } catch (error) {
                console.error('Error updating coupon:', error);
                alert('Error updating coupon. Please try again.');
            }
        });
        
        // Delete coupon
        async function deleteCoupon(id) {
            if (confirm('Are you sure you want to delete this coupon?')) {
                try {
                    const response = await fetch('api/admin-data.php', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete_coupon',
                            id: id
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        loadCoupons();
                        alert('Coupon deleted successfully!');
                    } else {
                        alert('Error deleting coupon: ' + data.error);
                    }
                } catch (error) {
                    console.error('Error deleting coupon:', error);
                    alert('Error deleting coupon. Please try again.');
                }
            }
        }
        
        // Update week
        async function updateWeek() {
            const newWeek = prompt('Enter new week number (1-4):', currentWeek);
            
            if (newWeek && newWeek >= 1 && newWeek <= 4) {
                try {
                    const response = await fetch('api/admin-data.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update_week',
                            week: parseInt(newWeek)
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        currentWeek = parseInt(newWeek);
                        document.getElementById('currentWeekDisplay').textContent = currentWeek;
                        loadCoupons(); // Reload to show updated probabilities
                        alert('Week updated successfully!');
                    } else {
                        alert('Error updating week: ' + data.error);
                    }
                } catch (error) {
                    console.error('Error updating week:', error);
                    alert('Error updating week. Please try again.');
                }
            }
        }
        
        // Save settings
        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const settingsData = {
                action: 'update_settings',
                settings: {
                    wheel_title: document.getElementById('wheelTitle').value,
                    wheel_description: document.getElementById('wheelDescription').value,
                    max_spins: document.getElementById('maxSpins').value,
                    enable_tracking: document.getElementById('enableTracking').checked ? '1' : '0'
                }
            };
            
            try {
                const response = await fetch('api/admin-data.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(settingsData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Settings saved successfully!');
                } else {
                    alert('Error saving settings: ' + data.error);
                }
            } catch (error) {
                console.error('Error saving settings:', error);
                alert('Error saving settings. Please try again.');
            }
        });
        
        // Change password
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                return;
            }
            
            // In production, you would validate the current password against the database
            if (currentPassword !== 'admin123') {
                alert('Current password is incorrect!');
                return;
            }
            
            // Clear form
            document.getElementById('passwordForm').reset();
            
            alert('Password changed successfully!');
        });
        
        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                // In production, you would clear the session
                window.location.href = 'login.php';
            }
        }
        
        // Utility function to format date
        function formatDate(timestamp) {
            // Handle both JS timestamp (milliseconds) and Unix timestamp (seconds)
            const date = new Date(timestamp > 1000000000000 ? timestamp : timestamp * 1000);
            return date.toLocaleString();
        }
        
        // Modal functions
        function openAddCouponModal() {
            document.getElementById('addCouponModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeAddCouponModal() {
            document.getElementById('addCouponModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            // Reset form
            document.getElementById('couponForm').reset();
            document.getElementById('couponColor').value = '#667eea';
        }
        
        function openEditCouponModal() {
            document.getElementById('editCouponModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeEditCouponModal() {
            document.getElementById('editCouponModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            // Reset form
            document.getElementById('editCouponForm').reset();
        }
        
        // Edit coupon function
        function editCoupon(id) {
            const coupon = coupons.find(c => c.id == id);
            if (!coupon) {
                alert('Coupon not found!');
                return;
            }
            
            // Populate edit form with current values
            document.getElementById('editCouponId').value = coupon.id;
            document.getElementById('editDiscountText').value = coupon.discount_text;
            document.getElementById('editDiscountValue').value = coupon.discount_value;
            document.getElementById('editCouponCode').value = coupon.coupon_code;
            document.getElementById('editWeek1Probability').value = coupon.week1_probability;
            document.getElementById('editWeek2Probability').value = coupon.week2_probability;
            document.getElementById('editWeek3Probability').value = coupon.week3_probability;
            document.getElementById('editWeek4Probability').value = coupon.week4_probability;
            document.getElementById('editCouponColor').value = coupon.color;
            
            openEditCouponModal();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addCouponModal');
            const editModal = document.getElementById('editCouponModal');
            
            if (event.target == addModal) {
                closeAddCouponModal();
            } else if (event.target == editModal) {
                closeEditCouponModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAddCouponModal();
                closeEditCouponModal();
            }
        });
        
        // Initialize dashboard on page load
        loadDashboard();
    </script>
</body>
</html>