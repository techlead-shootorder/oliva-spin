<?php
// Temporary minimal working spin endpoint
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['recordedId'])) {
        http_response_code(400);
        echo json_encode(['error' => 'recordedId is required']);
        exit;
    }
    
    $recordedId = trim($input['recordedId']);
    
    if (empty($recordedId)) {
        http_response_code(400);
        echo json_encode(['error' => 'recordedId cannot be empty']);
        exit;
    }
    
    // Basic validation
    if (!preg_match('/^[a-zA-Z0-9]{1,50}$/', $recordedId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid recordedId format']);
        exit;
    }
    
    // Try to load config
    if (file_exists('config.php')) {
        require_once 'config.php';
    }
    
    // Hardcoded winning options for now (will work even if database fails)
    $winningOptions = [
        [
            'text' => '₹10,000 Discount',
            'value' => 10000,
            'code' => 'OASIS10K-' . strtoupper(substr(md5($recordedId . time()), 0, 6)),
            'color' => '#FF6B6B'
        ],
        [
            'text' => '₹15,000 Discount',
            'value' => 15000,
            'code' => 'OASIS15K-' . strtoupper(substr(md5($recordedId . time()), 0, 6)),
            'color' => '#4ECDC4'
        ],
        [
            'text' => '₹20,000 Discount',
            'value' => 20000,
            'code' => 'OASIS20K-' . strtoupper(substr(md5($recordedId . time()), 0, 6)),
            'color' => '#45B7D1'
        ],
        [
            'text' => '₹50,000 Discount',
            'value' => 50000,
            'code' => 'OASIS50K-' . strtoupper(substr(md5($recordedId . time()), 0, 6)),
            'color' => '#96CEB4'
        ],
        [
            'text' => '₹1 Lakh Discount',
            'value' => 100000,
            'code' => 'OASIS1L-' . strtoupper(substr(md5($recordedId . time()), 0, 6)),
            'color' => '#FFEAA7'
        ],
        [
            'text' => 'Free IVF Treatment',
            'value' => 0,
            'code' => 'OASISIVF-' . strtoupper(substr(md5($recordedId . time()), 0, 6)),
            'color' => '#DDA0DD'
        ]
    ];
    
    // Simple probability-based selection
    $weights = [25, 25, 20, 15, 10, 5]; // Probabilities for each option
    $totalWeight = array_sum($weights);
    $random = mt_rand(1, $totalWeight);
    
    $currentWeight = 0;
    $selectedIndex = 0;
    
    for ($i = 0; $i < count($weights); $i++) {
        $currentWeight += $weights[$i];
        if ($random <= $currentWeight) {
            $selectedIndex = $i;
            break;
        }
    }
    
    $winner = $winningOptions[$selectedIndex];
    
    // Try to store in database if available
    if (isset($pdo)) {
        try {
            // Check if user already spun
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM spins WHERE recorded_id = ?");
            $checkStmt->execute([$recordedId]);
            $existingSpins = $checkStmt->fetchColumn();
            
            if ($existingSpins > 0) {
                http_response_code(403);
                echo json_encode(['error' => 'You have already used your spin for this campaign']);
                exit;
            }
            
            // Store the spin
            $insertStmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, coupon_code, timestamp, ip_address) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->execute([
                $recordedId,
                $winner['text'],
                $winner['code'],
                time() * 1000,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
        } catch (Exception $e) {
            // Continue even if database fails
            error_log("Database error: " . $e->getMessage());
        }
    }
    
    // Return success
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'result' => $winner,
        'currentWeek' => 1
    ]);
    
} catch (Exception $e) {
    error_log("Spin error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>