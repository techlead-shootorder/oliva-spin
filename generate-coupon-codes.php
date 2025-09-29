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
    <title>Generate Coupon Codes - Oasis Spin</title>
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
        
        .success { color: #059669; font-weight: bold; }
        .error { color: #dc2626; font-weight: bold; }
        .warning { color: #d97706; font-weight: bold; }
        .info { color: #2563eb; font-weight: bold; }
        
        .coupon-color-preview {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
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
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">🎫 Generate Coupon Codes</h1>
                        <p class="text-gray-600">Generate multiple unique coupon codes for each discount tier</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-4">
                        <a href="admin.php" class="btn-secondary">
                            <span>← Back to Admin</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Discount Tiers -->
        <div class="max-w-6xl mx-auto mb-8">
            <div class="glass-card p-6">
                <h2 class="text-xl font-bold mb-4">🎁 Current Discount Tiers</h2>
                <div id="discountTiers" class="space-y-4">
                    <!-- Discount tiers will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Generate Codes Section -->
        <div class="max-w-6xl mx-auto mb-8">
            <div class="glass-card p-6">
                <h2 class="text-xl font-bold mb-4">🔢 Generate Unique Codes</h2>
                <form id="generateForm" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Discount Tier</label>
                            <select id="discountTier" class="input-field" required>
                                <option value="">Select a discount tier...</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Codes to Generate</label>
                            <input type="number" id="codeCount" class="input-field" min="1" max="10000" value="100" required>
                            <p class="text-xs text-gray-500 mt-1">Recommended: 100-1000 codes per tier</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Code Suffix Length</label>
                            <select id="suffixLength" class="input-field">
                                <option value="6">6 characters (ABC123)</option>
                                <option value="8">8 characters (ABCD1234)</option>
                                <option value="10">10 characters (ABCDE12345)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Code Pattern</label>
                            <select id="codePattern" class="input-field">
                                <option value="mixed">Mixed (ABC123)</option>
                                <option value="letters">Letters only (ABCDEF)</option>
                                <option value="numbers">Numbers only (123456)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="submit" class="btn-primary">🚀 Generate Codes</button>
                        <button type="button" onclick="previewCodes()" class="btn-secondary">👀 Preview Sample</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Progress Section -->
        <div id="progressSection" class="max-w-6xl mx-auto mb-8" style="display: none;">
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold mb-4">⚡ Generation Progress</h3>
                <div class="progress-bar mb-2">
                    <div id="progressFill" class="progress-fill" style="width: 0%"></div>
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <span id="progressText">Generating codes...</span>
                    <span id="progressPercent">0%</span>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div id="resultsSection" class="max-w-6xl mx-auto mb-8" style="display: none;">
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold mb-4">📊 Generation Results</h3>
                <div id="resultsContent">
                    <!-- Results will be displayed here -->
                </div>
            </div>
        </div>

        <!-- Code Management Section -->
        <div class="max-w-6xl mx-auto mb-8">
            <div class="glass-card p-6">
                <h2 class="text-xl font-bold mb-4">📋 Code Management</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">📈 Statistics</h4>
                        <div id="codeStats" class="space-y-2 text-sm">
                            <!-- Statistics will be loaded here -->
                        </div>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">🔧 Actions</h4>
                        <div class="space-y-2">
                            <button onclick="refreshStats()" class="btn-secondary text-sm w-full">🔄 Refresh Stats</button>
                            <button onclick="downloadCodes()" class="btn-primary text-sm w-full">📥 Download All Codes</button>
                            <button onclick="clearUsedCodes()" class="btn-secondary text-sm w-full">🗑️ Clear Used Codes</button>
                        </div>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold mb-2">⚠️ Warnings</h4>
                        <div id="warningsContent" class="space-y-2 text-sm">
                            <!-- Warnings will be loaded here -->
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
            loadCodeStats();
        });

        // Load discount tiers
        function loadDiscountTiers() {
            fetch('api/admin-data.php?action=coupons')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        discountTiers = data.data;
                        displayDiscountTiers();
                        populateDiscountSelect();
                    }
                })
                .catch(error => {
                    console.error('Error loading discount tiers:', error);
                });
        }

        // Display discount tiers
        function displayDiscountTiers() {
            const container = document.getElementById('discountTiers');
            container.innerHTML = '';

            discountTiers.forEach(tier => {
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-4 border border-gray-200 rounded-lg bg-white';
                div.innerHTML = `
                    <div class="flex items-center space-x-4">
                        <div class="coupon-color-preview" style="background-color: ${tier.color}"></div>
                        <div>
                            <div class="font-semibold">${tier.discount_text}</div>
                            <div class="text-sm text-gray-600">Base Code: ${tier.coupon_code}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-mono" id="count-${tier.id}">Loading...</div>
                        <div class="text-xs text-gray-500">Generated Codes</div>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        // Populate discount select
        function populateDiscountSelect() {
            const select = document.getElementById('discountTier');
            select.innerHTML = '<option value="">Select a discount tier...</option>';

            discountTiers.forEach(tier => {
                const option = document.createElement('option');
                option.value = tier.id;
                option.textContent = `${tier.discount_text} (${tier.coupon_code})`;
                select.appendChild(option);
            });
        }

        // Generate codes form handler
        document.getElementById('generateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                discountTierId: document.getElementById('discountTier').value,
                codeCount: parseInt(document.getElementById('codeCount').value),
                suffixLength: parseInt(document.getElementById('suffixLength').value),
                codePattern: document.getElementById('codePattern').value
            };

            if (!formData.discountTierId) {
                alert('Please select a discount tier');
                return;
            }

            if (formData.codeCount < 1 || formData.codeCount > 10000) {
                alert('Code count must be between 1 and 10,000');
                return;
            }

            generateCodes(formData);
        });

        // Generate codes
        function generateCodes(formData) {
            const progressSection = document.getElementById('progressSection');
            const resultsSection = document.getElementById('resultsSection');
            
            progressSection.style.display = 'block';
            resultsSection.style.display = 'none';

            fetch('api/generate-coupon-codes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                progressSection.style.display = 'none';
                showResults(data);
                if (data.success) {
                    loadCodeStats();
                    loadDiscountTiers();
                }
            })
            .catch(error => {
                progressSection.style.display = 'none';
                showResults({
                    success: false,
                    message: 'Generation failed: ' + error.message
                });
            });
        }

        // Show results
        function showResults(data) {
            const resultsSection = document.getElementById('resultsSection');
            const resultsContent = document.getElementById('resultsContent');
            
            if (data.success) {
                resultsContent.innerHTML = `
                    <div class="success">
                        <h4 class="text-lg font-semibold mb-2">✅ Generation Successful!</h4>
                        <p class="mb-4">Successfully generated ${data.generated} unique coupon codes.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-sm text-green-700">
                                    <strong>Generated:</strong> ${data.generated}<br>
                                    <strong>Pattern:</strong> ${data.pattern}<br>
                                    <strong>Suffix Length:</strong> ${data.suffix_length}
                                </div>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-sm text-blue-700">
                                    <strong>Sample Codes:</strong><br>
                                    ${data.sample_codes ? data.sample_codes.join('<br>') : 'N/A'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                resultsContent.innerHTML = `
                    <div class="error">
                        <h4 class="text-lg font-semibold mb-2">❌ Generation Failed!</h4>
                        <p>${data.message || 'Unknown error occurred'}</p>
                    </div>
                `;
            }
            
            resultsSection.style.display = 'block';
        }

        // Load code statistics
        function loadCodeStats() {
            fetch('api/coupon-code-stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayCodeStats(data.stats);
                        displayWarnings(data.warnings);
                        updateTierCounts(data.tier_counts);
                    }
                })
                .catch(error => {
                    console.error('Error loading code stats:', error);
                });
        }

        // Display code statistics
        function displayCodeStats(stats) {
            const container = document.getElementById('codeStats');
            container.innerHTML = `
                <div><strong>Total Codes:</strong> ${stats.total_codes}</div>
                <div><strong>Available:</strong> ${stats.available_codes}</div>
                <div><strong>Used:</strong> ${stats.used_codes}</div>
                <div><strong>Usage Rate:</strong> ${stats.usage_rate}%</div>
            `;
        }

        // Display warnings
        function displayWarnings(warnings) {
            const container = document.getElementById('warningsContent');
            container.innerHTML = '';

            warnings.forEach(warning => {
                const div = document.createElement('div');
                div.className = 'text-orange-600 text-sm';
                div.textContent = warning;
                container.appendChild(div);
            });

            if (warnings.length === 0) {
                container.innerHTML = '<div class="text-green-600 text-sm">✅ No warnings</div>';
            }
        }

        // Update tier counts
        function updateTierCounts(tierCounts) {
            tierCounts.forEach(tier => {
                const countElement = document.getElementById(`count-${tier.tier_id}`);
                if (countElement) {
                    countElement.textContent = `${tier.available}/${tier.total}`;
                }
            });
        }

        // Preview codes
        function previewCodes() {
            const suffixLength = parseInt(document.getElementById('suffixLength').value);
            const codePattern = document.getElementById('codePattern').value;
            
            // Generate sample codes
            const samples = [];
            for (let i = 0; i < 5; i++) {
                samples.push(generateSampleCode(suffixLength, codePattern));
            }
            
            alert('Sample codes:\n' + samples.join('\n'));
        }

        // Generate sample code
        function generateSampleCode(length, pattern) {
            const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const numbers = '0123456789';
            let chars = '';
            
            switch (pattern) {
                case 'letters':
                    chars = letters;
                    break;
                case 'numbers':
                    chars = numbers;
                    break;
                case 'mixed':
                default:
                    chars = letters + numbers;
                    break;
            }
            
            let result = '';
            for (let i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            
            return 'OASISXX-' + result;
        }

        // Refresh statistics
        function refreshStats() {
            loadCodeStats();
            loadDiscountTiers();
        }

        // Download codes
        function downloadCodes() {
            window.open('api/download-coupon-codes.php', '_blank');
        }

        // Clear used codes
        function clearUsedCodes() {
            if (confirm('Are you sure you want to clear all used codes? This action cannot be undone.')) {
                fetch('api/clear-used-codes.php', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) {
                            loadCodeStats();
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                    });
            }
        }
    </script>
</body>
</html>