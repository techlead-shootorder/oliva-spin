<?php
require_once 'config.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['success' => false, 'error' => 'Only GET requests allowed'], 405);
}

$type = $_GET['type'] ?? '';

try {
    switch ($type) {
        case 'dashboard':
            // Get dashboard statistics
            $totalSpinsStmt = $pdo->query("SELECT COUNT(*) FROM spins");
            $totalSpins = $totalSpinsStmt->fetchColumn();
            
            $totalCouponsStmt = $pdo->query("SELECT COUNT(*) FROM coupons WHERE is_active = 1");
            $totalCoupons = $totalCouponsStmt->fetchColumn();
            
            $uniqueUsersStmt = $pdo->query("SELECT COUNT(DISTINCT recorded_id) FROM spins");
            $uniqueUsers = $uniqueUsersStmt->fetchColumn();
            
            // Get recent activity
            $recentSpinsStmt = $pdo->query("
                SELECT recorded_id, result, timestamp, ip_address 
                FROM spins 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $recentSpins = $recentSpinsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendJsonResponse([
                'success' => true,
                'data' => [
                    'totalSpins' => $totalSpins,
                    'totalCoupons' => $totalCoupons,
                    'uniqueUsers' => $uniqueUsers,
                    'recentSpins' => $recentSpins
                ]
            ]);
            break;
            
        case 'coupons':
            // Get all coupons
            $couponsStmt = $pdo->query("
                SELECT id, text, code, probability, color, is_active 
                FROM coupons 
                ORDER BY created_at DESC
            ");
            $coupons = $couponsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendJsonResponse([
                'success' => true,
                'data' => $coupons
            ]);
            break;
            
        case 'spins':
            // Get all spins with pagination
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));
            $offset = ($page - 1) * $limit;
            
            $spinsStmt = $pdo->prepare("
                SELECT recorded_id, result, timestamp, ip_address, created_at 
                FROM spins 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $spinsStmt->execute([$limit, $offset]);
            $spins = $spinsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countStmt = $pdo->query("SELECT COUNT(*) FROM spins");
            $totalSpins = $countStmt->fetchColumn();
            
            sendJsonResponse([
                'success' => true,
                'data' => [
                    'spins' => $spins,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $totalSpins,
                        'pages' => ceil($totalSpins / $limit)
                    ]
                ]
            ]);
            break;
            
        case 'settings':
            // Get all settings
            $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settingsResult = $settingsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($settingsResult as $setting) {
                $settings[$setting['setting_key']] = $setting['setting_value'];
            }
            
            sendJsonResponse([
                'success' => true,
                'data' => $settings
            ]);
            break;
            
        case 'wheel-config':
            // Get wheel configuration (coupons + settings)
            $couponsStmt = $pdo->query("
                SELECT text, code, probability, color 
                FROM coupons 
                WHERE is_active = 1 AND probability > 0
                ORDER BY probability DESC
            ");
            $coupons = $couponsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settingsResult = $settingsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($settingsResult as $setting) {
                $settings[$setting['setting_key']] = $setting['setting_value'];
            }
            
            sendJsonResponse([
                'success' => true,
                'data' => [
                    'coupons' => $coupons,
                    'settings' => $settings
                ]
            ]);
            break;
            
        default:
            sendJsonResponse(['success' => false, 'error' => 'Invalid type parameter'], 400);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Database error in get-data.php: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log("General error in get-data.php: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => 'An error occurred'], 500);
}
?>