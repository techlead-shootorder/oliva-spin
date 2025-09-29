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
    <title>Bulk Upload Coupons - Oasis Spin</title>
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
        
        .preview-table {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        
        .coupon-color-preview {
            width: 30px;
            height: 30px;
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
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">📤 Bulk Upload Coupons</h1>
                        <p class="text-gray-600">Upload multiple coupons at once using CSV or manual entry</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-4">
                        <a href="admin.php" class="btn-secondary">
                            <span>← Back to Admin</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Methods -->
        <div class="max-w-6xl mx-auto space-y-8">
            <!-- Method 1: CSV Upload -->
            <div class="glass-card p-6">
                <h2 class="text-xl font-bold mb-4">📄 Method 1: CSV Upload</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold mb-3">Upload CSV File</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Upload a CSV file with columns: discount_text, coupon_code, discount_value, week1_probability, week2_probability, week3_probability, week4_probability, color
                        </p>
                        <form id="csvUploadForm" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <input type="file" id="csvFile" name="csvFile" accept=".csv" class="input-field">
                            </div>
                            <button type="submit" class="btn-primary">📤 Upload & Preview</button>
                        </form>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-3">CSV Format Example</h3>
                        <div class="bg-gray-100 p-4 rounded-lg text-sm font-mono">
                            <div class="mb-2">discount_text,coupon_code,discount_value,week1_probability,week2_probability,week3_probability,week4_probability,color</div>
                            <div class="text-blue-600">₹10,000 Discount,OASIS10K,10000,25,30,20,15,#FF6B6B</div>
                            <div class="text-green-600">₹15,000 Discount,OASIS15K,15000,25,25,25,25,#4ECDC4</div>
                            <div class="text-purple-600">Free IVF Treatment,OASISIVF,0,5,5,10,15,#DDA0DD</div>
                        </div>
                        <a href="#" onclick="downloadTemplate()" class="text-blue-600 hover:text-blue-800 text-sm underline mt-2 inline-block">
                            📥 Download CSV Template
                        </a>
                    </div>
                </div>
            </div>

            <!-- Method 2: Manual Entry -->
            <div class="glass-card p-6">
                <h2 class="text-xl font-bold mb-4">✏️ Method 2: Manual Entry</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <p class="text-gray-600">Add multiple coupons manually using the form below</p>
                        <button onclick="addCouponRow()" class="btn-primary">➕ Add Coupon</button>
                    </div>
                    
                    <form id="manualUploadForm">
                        <div id="couponRows" class="space-y-4">
                            <!-- Coupon rows will be added here -->
                        </div>
                        <div class="mt-6 flex space-x-4">
                            <button type="submit" class="btn-primary">💾 Save All Coupons</button>
                            <button type="button" onclick="clearAll()" class="btn-secondary">🗑️ Clear All</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Section -->
            <div id="previewSection" class="glass-card p-6" style="display: none;">
                <h2 class="text-xl font-bold mb-4">👀 Preview Coupons</h2>
                <div class="preview-table">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="text-left p-3 font-semibold">Discount</th>
                                <th class="text-left p-3 font-semibold">Code</th>
                                <th class="text-left p-3 font-semibold">Value</th>
                                <th class="text-left p-3 font-semibold">Week 1%</th>
                                <th class="text-left p-3 font-semibold">Week 2%</th>
                                <th class="text-left p-3 font-semibold">Week 3%</th>
                                <th class="text-left p-3 font-semibold">Week 4%</th>
                                <th class="text-left p-3 font-semibold">Color</th>
                            </tr>
                        </thead>
                        <tbody id="previewTableBody">
                            <!-- Preview rows will be added here -->
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex space-x-4">
                    <button onclick="confirmUpload()" class="btn-primary">✅ Confirm & Upload</button>
                    <button onclick="cancelUpload()" class="btn-secondary">❌ Cancel</button>
                </div>
            </div>

            <!-- Results Section -->
            <div id="resultsSection" class="glass-card p-6" style="display: none;">
                <h2 class="text-xl font-bold mb-4">📊 Upload Results</h2>
                <div id="uploadResults">
                    <!-- Results will be displayed here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        let previewData = [];
        let couponRowCount = 0;

        // CSV Upload Handler
        document.getElementById('csvUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById('csvFile');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Please select a CSV file');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const csv = e.target.result;
                parseCSV(csv);
            };
            reader.readAsText(file);
        });

        // Parse CSV Data
        function parseCSV(csv) {
            const lines = csv.trim().split('\n');
            const headers = lines[0].split(',');
            const data = [];
            
            for (let i = 1; i < lines.length; i++) {
                const values = lines[i].split(',');
                const row = {};
                
                headers.forEach((header, index) => {
                    row[header.trim()] = values[index] ? values[index].trim() : '';
                });
                
                data.push(row);
            }
            
            previewData = data;
            showPreview();
        }

        // Show Preview
        function showPreview() {
            const previewSection = document.getElementById('previewSection');
            const previewTableBody = document.getElementById('previewTableBody');
            
            previewTableBody.innerHTML = '';
            
            previewData.forEach((row, index) => {
                const tr = document.createElement('tr');
                tr.className = 'border-b border-gray-200 hover:bg-gray-50';
                
                tr.innerHTML = `
                    <td class="p-3">${row.discount_text || ''}</td>
                    <td class="p-3 font-mono text-sm">${row.coupon_code || ''}</td>
                    <td class="p-3">₹${row.discount_value || '0'}</td>
                    <td class="p-3">${row.week1_probability || '0'}%</td>
                    <td class="p-3">${row.week2_probability || '0'}%</td>
                    <td class="p-3">${row.week3_probability || '0'}%</td>
                    <td class="p-3">${row.week4_probability || '0'}%</td>
                    <td class="p-3">
                        <div class="coupon-color-preview" style="background-color: ${row.color || '#cccccc'}"></div>
                    </td>
                `;
                
                previewTableBody.appendChild(tr);
            });
            
            previewSection.style.display = 'block';
        }

        // Add Coupon Row for Manual Entry
        function addCouponRow() {
            couponRowCount++;
            const couponRows = document.getElementById('couponRows');
            
            const row = document.createElement('div');
            row.className = 'border border-gray-200 rounded-lg p-4 bg-white';
            row.id = `couponRow${couponRowCount}`;
            
            row.innerHTML = `
                <div class="flex justify-between items-center mb-4">
                    <h4 class="font-semibold">Coupon #${couponRowCount}</h4>
                    <button type="button" onclick="removeCouponRow(${couponRowCount})" class="text-red-600 hover:text-red-800">🗑️ Remove</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Text</label>
                        <input type="text" name="discount_text_${couponRowCount}" class="input-field" placeholder="₹10,000 Discount" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Coupon Code</label>
                        <input type="text" name="coupon_code_${couponRowCount}" class="input-field" placeholder="OASIS10K" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Value</label>
                        <input type="number" name="discount_value_${couponRowCount}" class="input-field" placeholder="10000" min="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                        <input type="color" name="color_${couponRowCount}" class="input-field h-12" value="#FF6B6B">
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Week 1 %</label>
                        <input type="number" name="week1_probability_${couponRowCount}" class="input-field" placeholder="25" min="0" max="100" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Week 2 %</label>
                        <input type="number" name="week2_probability_${couponRowCount}" class="input-field" placeholder="25" min="0" max="100" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Week 3 %</label>
                        <input type="number" name="week3_probability_${couponRowCount}" class="input-field" placeholder="25" min="0" max="100" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Week 4 %</label>
                        <input type="number" name="week4_probability_${couponRowCount}" class="input-field" placeholder="25" min="0" max="100" required>
                    </div>
                </div>
            `;
            
            couponRows.appendChild(row);
        }

        // Remove Coupon Row
        function removeCouponRow(id) {
            const row = document.getElementById(`couponRow${id}`);
            if (row) {
                row.remove();
            }
        }

        // Clear All Rows
        function clearAll() {
            document.getElementById('couponRows').innerHTML = '';
            couponRowCount = 0;
        }

        // Manual Upload Handler
        document.getElementById('manualUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = [];
            
            for (let i = 1; i <= couponRowCount; i++) {
                const row = document.getElementById(`couponRow${i}`);
                if (row) {
                    data.push({
                        discount_text: formData.get(`discount_text_${i}`),
                        coupon_code: formData.get(`coupon_code_${i}`),
                        discount_value: formData.get(`discount_value_${i}`),
                        week1_probability: formData.get(`week1_probability_${i}`),
                        week2_probability: formData.get(`week2_probability_${i}`),
                        week3_probability: formData.get(`week3_probability_${i}`),
                        week4_probability: formData.get(`week4_probability_${i}`),
                        color: formData.get(`color_${i}`)
                    });
                }
            }
            
            previewData = data;
            showPreview();
        });

        // Confirm Upload
        function confirmUpload() {
            fetch('api/bulk-upload-coupons.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'upload',
                    coupons: previewData
                })
            })
            .then(response => response.json())
            .then(data => {
                showResults(data);
            })
            .catch(error => {
                console.error('Error:', error);
                showResults({success: false, message: 'Upload failed: ' + error.message});
            });
        }

        // Cancel Upload
        function cancelUpload() {
            document.getElementById('previewSection').style.display = 'none';
            previewData = [];
        }

        // Show Results
        function showResults(data) {
            const resultsSection = document.getElementById('resultsSection');
            const uploadResults = document.getElementById('uploadResults');
            
            if (data.success) {
                uploadResults.innerHTML = `
                    <div class="success">
                        <h3 class="text-lg font-semibold mb-2">✅ Upload Successful!</h3>
                        <p>Successfully uploaded ${data.count} coupons.</p>
                        <div class="mt-4">
                            <a href="admin.php" class="btn-primary">📊 View in Admin Panel</a>
                        </div>
                    </div>
                `;
            } else {
                uploadResults.innerHTML = `
                    <div class="error">
                        <h3 class="text-lg font-semibold mb-2">❌ Upload Failed!</h3>
                        <p>${data.message || 'Unknown error occurred'}</p>
                        ${data.errors ? '<ul class="mt-2 text-sm"><li>' + data.errors.join('</li><li>') + '</li></ul>' : ''}
                    </div>
                `;
            }
            
            resultsSection.style.display = 'block';
            document.getElementById('previewSection').style.display = 'none';
        }

        // Download CSV Template
        function downloadTemplate() {
            const csvContent = "data:text/csv;charset=utf-8," + 
                "discount_text,coupon_code,discount_value,week1_probability,week2_probability,week3_probability,week4_probability,color\n" +
                "₹10,000 Discount,OASIS10K,10000,25,30,20,15,#FF6B6B\n" +
                "₹15,000 Discount,OASIS15K,15000,25,25,25,25,#4ECDC4\n" +
                "₹20,000 Discount,OASIS20K,20000,20,20,25,30,#45B7D1\n" +
                "₹50,000 Discount,OASIS50K,50000,15,15,20,25,#96CEB4\n" +
                "₹1 Lakh Discount,OASIS1L,100000,10,10,10,15,#FFEAA7\n" +
                "Free IVF Treatment,OASISIVF,0,5,5,10,15,#DDA0DD";
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "coupon_template.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Initialize with one coupon row
        addCouponRow();
    </script>
</body>
</html>