<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

require_once 'api/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Coupon Codes - Oasis Spin</title>
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
            border-radius: 8px;
            padding: 6px 12px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        
        .success { color: #059669; font-weight: bold; }
        .error { color: #dc2626; font-weight: bold; }
        .warning { color: #d97706; font-weight: bold; }
        .info { color: #2563eb; font-weight: bold; }
        
        .coupon-color-preview {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .code-chip {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 2px;
            box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);
            font-family: monospace;
            font-size: 12px;
        }
        
        .used-code {
            background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
            opacity: 0.7;
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
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">🎫 Manage Discount Codes</h1>
                        <p class="text-gray-600">Add and manage multiple coupon codes for each discount tier</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-4">
                        <button onclick="manageTokens()" class="btn-primary">
                            <span>🔐 Manage Tokens</span>
                        </button>
                        <button onclick="removeAllCoupons()" class="btn-danger">
                            <span>🗑️ Remove All Coupons</span>
                        </button>
                        <a href="admin.php" class="btn-secondary">
                            <span>← Back to Admin</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Discount Tiers -->
        <div class="max-w-6xl mx-auto space-y-8">
            <div id="discountTiers">
                <!-- Discount tiers will be loaded here -->
            </div>
        </div>

        <!-- Add Codes Modal -->
        <div id="addCodesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="glass-card p-8 max-w-2xl w-full">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold">Add Coupon Codes</h3>
                        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">✕</button>
                    </div>
                    
                    <form id="addCodesForm">
                        <input type="hidden" id="selectedTierId">
                        
                        <div class="mb-4">
                            <div id="selectedTierInfo" class="text-lg font-semibold text-blue-600 mb-2">
                                <!-- Selected tier info will be shown here -->
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Add Coupon Codes (one per line)
                            </label>
                            <textarea id="couponCodesInput" class="input-field" rows="8" 
                                placeholder="Enter codes one per line:&#10;XDG L8I DN2&#10;2I4 ERY QQK&#10;SEW H6E 07Z&#10;ABC DEF GHI&#10;..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">
                                Each line should contain one coupon code. Spaces are allowed.
                            </p>
                        </div>
                        
                        <div class="flex space-x-4">
                            <button type="submit" class="btn-primary">
                                💾 Add Codes
                            </button>
                            <button type="button" onclick="closeModal()" class="btn-secondary">
                                ❌ Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Token Management Modal -->
        <div id="tokenModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="glass-card p-8 max-w-4xl w-full">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold">🔐 Token Management</h3>
                        <button onclick="closeTokenModal()" class="text-gray-500 hover:text-gray-700">✕</button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Current Tokens -->
                        <div>
                            <h4 class="font-semibold mb-4">Current Tokens</h4>
                            <div id="tokensList" class="space-y-2 max-h-96 overflow-y-auto border rounded-lg p-4 bg-gray-50">
                                <!-- Tokens will be loaded here -->
                            </div>
                            <div class="mt-4 flex space-x-2">
                                <button onclick="generateTokens()" class="btn-primary">
                                    ➕ Generate New Tokens
                                </button>
                                <button onclick="clearAllTokens()" class="btn-danger">
                                    🗑️ Clear All Tokens
                                </button>
                            </div>
                        </div>
                        
                        <!-- Token Generator -->
                        <div>
                            <h4 class="font-semibold mb-4">Generate Tokens</h4>
                            <form id="tokenGeneratorForm">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Number of Tokens
                                    </label>
                                    <input type="number" id="tokenCount" class="input-field" min="1" max="1000" value="10">
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Token Length
                                    </label>
                                    <select id="tokenLength" class="input-field">
                                        <option value="8">8 characters</option>
                                        <option value="16" selected>16 characters</option>
                                        <option value="32">32 characters</option>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Token Type
                                    </label>
                                    <select id="tokenType" class="input-field">
                                        <option value="alphanumeric">Alphanumeric</option>
                                        <option value="numeric">Numeric Only</option>
                                        <option value="alphabetic">Alphabetic Only</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn-primary w-full">
                                    🎲 Generate Tokens
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let discountTiers = [];

        // Load discount tiers on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadDiscountTiers();
        });

        // Load discount tiers
        function loadDiscountTiers() {
            console.log('Loading discount tiers...');
            fetch('api/admin-data.php?action=coupons')
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('API Response:', data);
                    if (data && data.success && (data.data || data.coupons)) {
                        // Handle both data.data and data.coupons response formats
                        discountTiers = data.data || data.coupons;
                        console.log('Loaded discount tiers:', discountTiers);
                        displayDiscountTiers();
                    } else {
                        console.error('API returned error or no data:', data);
                        showError('Failed to load discount tiers. ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading discount tiers:', error);
                    showError('Error loading discount tiers: ' + error.message);
                });
        }

        // Show error message
        function showError(message) {
            const container = document.getElementById('discountTiers');
            container.innerHTML = `
                <div class="glass-card p-6 text-center">
                    <div class="text-red-600 text-lg font-semibold mb-4">❌ ${message}</div>
                    <div class="space-y-2 text-gray-600">
                        <p>Possible solutions:</p>
                        <ul class="text-left max-w-md mx-auto">
                            <li>• Make sure you're logged in as admin</li>
                            <li>• Create discount tiers first in Admin Panel</li>
                            <li>• Check if api/admin-data.php exists</li>
                        </ul>
                    </div>
                    <div class="mt-6 space-x-4">
                        <a href="admin.php" class="btn-secondary">🔧 Go to Admin Panel</a>
                        <button onclick="loadDiscountTiers()" class="btn-primary">🔄 Try Again</button>
                    </div>
                </div>
            `;
        }

        // Display discount tiers with their codes
        function displayDiscountTiers() {
            const container = document.getElementById('discountTiers');
            container.innerHTML = '';

            if (!discountTiers || discountTiers.length === 0) {
                container.innerHTML = `
                    <div class="glass-card p-6 text-center">
                        <div class="text-gray-600 text-lg font-semibold mb-4">📭 No Discount Tiers Found</div>
                        <p class="text-gray-500 mb-6">You need to create discount tiers first before adding coupon codes.</p>
                        <a href="admin.php" class="btn-primary">➕ Create Discount Tiers</a>
                    </div>
                `;
                return;
            }

            discountTiers.forEach(tier => {
                const div = document.createElement('div');
                div.className = 'glass-card p-6';
                
                div.innerHTML = `
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center space-x-4">
                            <div class="coupon-color-preview" style="background-color: ${tier.color}"></div>
                            <div>
                                <h3 class="text-xl font-bold">${tier.discount_text}</h3>
                                <div class="text-sm text-gray-600">Value: ₹${parseInt(tier.discount_value).toLocaleString()}</div>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="addCodes(${tier.id}, '${tier.discount_text}')" class="btn-primary">
                                ➕ Add Codes
                            </button>
                            <button onclick="loadCodes(${tier.id})" class="btn-secondary">
                                🔄 Refresh
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-semibold">Coupon Codes:</h4>
                            <div id="codeStats${tier.id}" class="text-sm text-gray-600">
                                Loading...
                            </div>
                        </div>
                        <div id="codesList${tier.id}" class="min-h-[100px] border-2 border-dashed border-gray-300 rounded-lg p-4 bg-gray-50">
                            <p class="text-gray-500 text-center">Loading codes...</p>
                        </div>
                    </div>
                `;
                
                container.appendChild(div);
                
                // Load codes for this tier
                loadCodes(tier.id);
            });
        }

        // Load codes for a specific tier
        function loadCodes(tierId) {
            fetch(`api/get-tier-codes.php?tierId=${tierId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayCodes(tierId, data.codes);
                        updateCodeStats(tierId, data.stats);
                    } else {
                        document.getElementById(`codesList${tierId}`).innerHTML = 
                            '<p class="text-gray-500 text-center">No codes added yet</p>';
                        document.getElementById(`codeStats${tierId}`).textContent = '0 codes';
                    }
                })
                .catch(error => {
                    console.error('Error loading codes:', error);
                    document.getElementById(`codesList${tierId}`).innerHTML = 
                        '<p class="text-red-500 text-center">Error loading codes</p>';
                });
        }

        // Display codes for a tier
        function displayCodes(tierId, codes) {
            const container = document.getElementById(`codesList${tierId}`);
            
            if (codes.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center">No codes added yet</p>';
                return;
            }
            
            container.innerHTML = '';
            codes.forEach(code => {
                const codeSpan = document.createElement('span');
                codeSpan.className = `code-chip ${code.is_used ? 'used-code' : ''}`;
                codeSpan.innerHTML = `
                    ${code.coupon_code}
                    ${code.is_used ? '✓' : ''}
                    <button onclick="removeCode(${code.id}, ${tierId})" class="text-white hover:text-red-200 ml-1">✕</button>
                `;
                container.appendChild(codeSpan);
            });
        }

        // Update code statistics
        function updateCodeStats(tierId, stats) {
            const statsElement = document.getElementById(`codeStats${tierId}`);
            statsElement.textContent = `${stats.available}/${stats.total} available`;
            
            if (stats.available === 0 && stats.total > 0) {
                statsElement.className = 'text-sm text-red-600 font-semibold';
                statsElement.textContent += ' ⚠️ All used!';
            } else if (stats.available < 5 && stats.total > 0) {
                statsElement.className = 'text-sm text-orange-600 font-semibold';
                statsElement.textContent += ' ⚠️ Low stock!';
            } else {
                statsElement.className = 'text-sm text-gray-600';
            }
        }

        // Open add codes modal
        function addCodes(tierId, tierName) {
            document.getElementById('selectedTierId').value = tierId;
            document.getElementById('selectedTierInfo').textContent = `Adding codes for: ${tierName}`;
            document.getElementById('couponCodesInput').value = '';
            document.getElementById('addCodesModal').classList.remove('hidden');
        }

        // Close modal
        function closeModal() {
            document.getElementById('addCodesModal').classList.add('hidden');
        }

        // Handle add codes form submission
        document.getElementById('addCodesForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const tierId = document.getElementById('selectedTierId').value;
            const codesText = document.getElementById('couponCodesInput').value.trim();
            
            if (!codesText) {
                alert('Please enter at least one coupon code');
                return;
            }
            
            // Split codes by line and clean them
            const codes = codesText.split('\n')
                .map(code => code.trim())
                .filter(code => code.length > 0);
            
            if (codes.length === 0) {
                alert('No valid codes found');
                return;
            }
            
            // Submit codes
            submitCodes(tierId, codes);
        });

        // Submit codes to server
        function submitCodes(tierId, codes) {
            fetch('api/add-tier-codes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tierId: tierId,
                    codes: codes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully added ${data.added} codes!`);
                    closeModal();
                    loadCodes(tierId);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error adding codes: ' + error.message);
            });
        }

        // Remove a specific code
        function removeCode(codeId, tierId) {
            if (!confirm('Are you sure you want to remove this code?')) {
                return;
            }
            
            fetch('api/remove-tier-code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ codeId: codeId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadCodes(tierId);
                } else {
                    alert('Error removing code: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }

        // Token Management Functions
        function manageTokens() {
            document.getElementById('tokenModal').classList.remove('hidden');
            loadTokens();
        }

        function closeTokenModal() {
            document.getElementById('tokenModal').classList.add('hidden');
        }

        function loadTokens() {
            fetch('api/token-management.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTokens(data.tokens);
                    } else {
                        document.getElementById('tokensList').innerHTML = '<p class="text-red-500">Error loading tokens</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('tokensList').innerHTML = '<p class="text-red-500">Error loading tokens</p>';
                });
        }

        function displayTokens(tokens) {
            const container = document.getElementById('tokensList');
            
            if (tokens.length === 0) {
                container.innerHTML = '<p class="text-gray-500">No tokens available</p>';
                return;
            }
            
            container.innerHTML = '';
            tokens.forEach(token => {
                const tokenDiv = document.createElement('div');
                tokenDiv.className = 'flex justify-between items-center p-2 bg-white rounded border';
                tokenDiv.innerHTML = `
                    <span class="font-mono text-sm">${token.token}</span>
                    <div class="flex space-x-1">
                        <button onclick="copyToken('${token.token}')" class="text-blue-500 hover:text-blue-700 text-xs">📋</button>
                        <button onclick="removeToken('${token.token}')" class="text-red-500 hover:text-red-700 text-xs">✕</button>
                    </div>
                `;
                container.appendChild(tokenDiv);
            });
        }

        function generateTokens() {
            const count = document.getElementById('tokenCount').value;
            const length = document.getElementById('tokenLength').value;
            const type = document.getElementById('tokenType').value;
            
            fetch('api/token-management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'generate',
                    count: count,
                    length: length,
                    type: type
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Generated ${data.count} tokens successfully!`);
                    loadTokens();
                } else {
                    alert('Error generating tokens: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }

        function clearAllTokens() {
            if (!confirm('Are you sure you want to clear all tokens? This action cannot be undone.')) {
                return;
            }
            
            fetch('api/token-management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'clear_all'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('All tokens cleared successfully!');
                    loadTokens();
                } else {
                    alert('Error clearing tokens: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }

        function copyToken(token) {
            navigator.clipboard.writeText(token).then(() => {
                alert('Token copied to clipboard!');
            }).catch(err => {
                alert('Failed to copy token');
            });
        }

        function removeToken(token) {
            if (!confirm('Are you sure you want to remove this token?')) {
                return;
            }
            
            fetch('api/token-management.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove',
                    token: token
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadTokens();
                } else {
                    alert('Error removing token: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }

        // Token Generator Form Handler
        document.getElementById('tokenGeneratorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            generateTokens();
        });

        // Remove All Coupons Function
        function removeAllCoupons() {
            if (!confirm('Are you sure you want to remove ALL coupons? This will delete all discount tiers and their codes. This action cannot be undone.')) {
                return;
            }
            
            if (!confirm('This will permanently delete all coupons and their associated codes. Are you absolutely sure?')) {
                return;
            }
            
            fetch('api/admin-data.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove_all_coupons'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('All coupons removed successfully!');
                    loadDiscountTiers();
                } else {
                    alert('Error removing coupons: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }
    </script>
</body>
</html>