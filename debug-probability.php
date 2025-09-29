<?php
session_start();

// Check if user is logged in (simple check)
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
    <title>Probability Debug - Oasis Spin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.1);
        }
        
        .prob-bar {
            height: 20px;
            background: linear-gradient(to right, #667eea, #764ba2);
            border-radius: 3px;
            transition: width 0.3s ease;
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
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    <div class="min-h-screen p-4">
        <!-- Header -->
        <div class="max-w-6xl mx-auto mb-8">
            <div class="glass-card p-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">🎯 Probability System Debug</h1>
                        <p class="text-gray-600">Real-time probability testing and verification</p>
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-4">
                        <a href="admin.php" class="btn-primary">🔧 Admin Panel</a>
                        <button onclick="runTests()" class="btn-primary">🎲 Run Tests</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Week Info -->
        <div class="max-w-6xl mx-auto mb-8">
            <div class="glass-card p-6">
                <h2 class="text-2xl font-bold mb-4">📅 Current Week Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-indigo-100 p-4 rounded-lg">
                        <div class="text-lg font-semibold text-indigo-800">Current Week</div>
                        <div class="text-3xl font-bold text-indigo-600" id="currentWeek"><?php echo getCurrentWeek(); ?></div>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-lg">
                        <div class="text-lg font-semibold text-blue-800">Active Coupons</div>
                        <div class="text-3xl font-bold text-blue-600" id="activeCoupons"><?php echo count(getCouponProbabilities()); ?></div>
                    </div>
                    <div class="bg-green-100 p-4 rounded-lg">
                        <div class="text-lg font-semibold text-green-800">Total Probability</div>
                        <div class="text-3xl font-bold text-green-600" id="totalProb">
                            <?php 
                            $coupons = getCouponProbabilities();
                            $total = array_sum(array_column($coupons, 'probability'));
                            echo $total . '%';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Probability Distribution -->
        <div class="max-w-6xl mx-auto mb-8">
            <div class="glass-card p-6">
                <h2 class="text-2xl font-bold mb-4">📊 Current Week Probability Distribution</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-gray-200 bg-gray-50">
                                <th class="text-left p-3 font-semibold">Discount</th>
                                <th class="text-left p-3 font-semibold">Code</th>
                                <th class="text-left p-3 font-semibold">Probability</th>
                                <th class="text-left p-3 font-semibold">Visual Distribution</th>
                                <th class="text-left p-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $coupons = getCouponProbabilities();
                            foreach ($coupons as $coupon):
                                $barWidth = ($coupon['probability'] / 100) * 300; // Scale for visual
                            ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="p-3">
                                    <div class="font-medium" style="color: <?php echo $coupon['color']; ?>">
                                        <?php echo htmlspecialchars($coupon['discount_text']); ?>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">
                                        <?php echo htmlspecialchars($coupon['coupon_code']); ?>
                                    </code>
                                </td>
                                <td class="p-3">
                                    <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full text-xs font-medium">
                                        <?php echo $coupon['probability']; ?>%
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="prob-bar" style="width: <?php echo $barWidth; ?>px; background-color: <?php echo $coupon['color']; ?>"></div>
                                </td>
                                <td class="p-3">
                                    <span class="text-green-600">✅ Active</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Live Testing -->
        <div class="max-w-6xl mx-auto mb-8">
            <div class="glass-card p-6">
                <h2 class="text-2xl font-bold mb-4">🎮 Live Probability Testing</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Test Controls</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Number of Tests</label>
                                <select id="testCount" class="w-full p-2 border border-gray-300 rounded-lg">
                                    <option value="10">10 tests</option>
                                    <option value="50">50 tests</option>
                                    <option value="100" selected>100 tests</option>
                                    <option value="500">500 tests</option>
                                    <option value="1000">1000 tests</option>
                                </select>
                            </div>
                            <button onclick="runProbabilityTest()" class="btn-primary w-full">
                                🎲 Run Probability Test
                            </button>
                            <button onclick="testSingleSpin()" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-lg w-full transition-colors">
                                🎯 Test Single Spin
                            </button>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Test Results</h3>
                        <div id="testResults" class="bg-gray-50 p-4 rounded-lg min-h-32">
                            <p class="text-gray-500 text-center">Run a test to see results...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function runTests() {
            document.getElementById('testResults').innerHTML = '<div class="text-center">🔄 Running comprehensive tests...</div>';
            
            try {
                const response = await fetch('test-probabilities.php');
                const html = await response.text();
                
                // Extract just the test results (this is a simple approach)
                window.open('test-probabilities.php', '_blank');
            } catch (error) {
                document.getElementById('testResults').innerHTML = '<div class="text-red-600">❌ Error running tests: ' + error.message + '</div>';
            }
        }

        async function runProbabilityTest() {
            const testCount = document.getElementById('testCount').value;
            document.getElementById('testResults').innerHTML = '<div class="text-center">🔄 Running ' + testCount + ' probability tests...</div>';
            
            try {
                const response = await fetch('api/debug-probability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'test_probability',
                        count: parseInt(testCount)
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let html = '<div class="space-y-2">';
                    html += '<div class="font-semibold">Test Results (' + testCount + ' spins):</div>';
                    
                    for (const [couponId, result] of Object.entries(data.results)) {
                        const percentage = ((result.count / testCount) * 100).toFixed(1);
                        const difference = (percentage - result.expected).toFixed(1);
                        const diffColor = Math.abs(difference) <= 3 ? 'text-green-600' : 'text-orange-600';
                        
                        html += '<div class="flex justify-between items-center text-sm">';
                        html += '<span>' + result.name + ':</span>';
                        html += '<span class="' + diffColor + '">' + result.count + ' (' + percentage + '%) vs ' + result.expected + '%</span>';
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    document.getElementById('testResults').innerHTML = html;
                } else {
                    document.getElementById('testResults').innerHTML = '<div class="text-red-600">❌ ' + data.error + '</div>';
                }
            } catch (error) {
                document.getElementById('testResults').innerHTML = '<div class="text-red-600">❌ Error: ' + error.message + '</div>';
            }
        }

        async function testSingleSpin() {
            document.getElementById('testResults').innerHTML = '<div class="text-center">🎲 Testing single spin...</div>';
            
            try {
                const response = await fetch('api/debug-probability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'single_spin'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const result = data.result;
                    let html = '<div class="text-center">';
                    html += '<div class="text-2xl mb-2">🎉</div>';
                    html += '<div class="font-bold text-lg" style="color: ' + result.color + '">' + result.text + '</div>';
                    html += '<div class="text-sm text-gray-600 mt-2">Code: ' + result.code + '</div>';
                    html += '<div class="text-sm text-gray-600">Probability: ' + result.probability + '%</div>';
                    html += '<div class="text-sm text-gray-600">Current Week: ' + data.currentWeek + '</div>';
                    html += '</div>';
                    
                    document.getElementById('testResults').innerHTML = html;
                } else {
                    document.getElementById('testResults').innerHTML = '<div class="text-red-600">❌ ' + data.error + '</div>';
                }
            } catch (error) {
                document.getElementById('testResults').innerHTML = '<div class="text-red-600">❌ Error: ' + error.message + '</div>';
            }
        }

        // Auto-refresh current week info every 30 seconds
        setInterval(function() {
            fetch('api/debug-probability.php?action=current_week')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('currentWeek').textContent = data.currentWeek;
                    }
                })
                .catch(error => console.error('Error updating week:', error));
        }, 30000);
    </script>
</body>
</html>