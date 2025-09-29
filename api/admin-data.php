<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    sendJsonResponse(['error' => 'Unauthorized'], 401);
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
            break;
        case 'PUT':
            handlePutRequest();
            break;
        case 'DELETE':
            handleDeleteRequest();
            break;
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log("Admin API error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}

function handleGetRequest() {
    global $pdo;
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'dashboard':
            getDashboardData();
            break;
        case 'coupons':
            getCouponsData();
            break;
        case 'spins':
            getSpinsData();
            break;
        case 'settings':
            getSettingsData();
            break;
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

function getDashboardData() {
    global $pdo;
    
    // Get total spins
    $totalSpinsStmt = $pdo->prepare("SELECT COUNT(*) FROM spins");
    $totalSpinsStmt->execute();
    $totalSpins = $totalSpinsStmt->fetchColumn();
    
    // Get active coupons
    $activeCouponsStmt = $pdo->prepare("SELECT COUNT(*) FROM coupons WHERE is_active = 1");
    $activeCouponsStmt->execute();
    $activeCoupons = $activeCouponsStmt->fetchColumn();
    
    // Get unique users
    $uniqueUsersStmt = $pdo->prepare("SELECT COUNT(DISTINCT recorded_id) FROM spins");
    $uniqueUsersStmt->execute();
    $uniqueUsers = $uniqueUsersStmt->fetchColumn();
    
    // Get recent activity
    $recentActivityStmt = $pdo->prepare("SELECT recorded_id, result, timestamp FROM spins ORDER BY timestamp DESC LIMIT 10");
    $recentActivityStmt->execute();
    $recentActivity = $recentActivityStmt->fetchAll();
    
    // Get weekly statistics
    $currentWeek = getCurrentWeek();
    $weeklyStatsStmt = $pdo->prepare("
        SELECT 
            c.discount_text,
            c.week{$currentWeek}_probability as probability,
            COUNT(s.id) as spin_count
        FROM coupons c
        LEFT JOIN spins s ON c.discount_text = s.result
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY c.week{$currentWeek}_probability DESC
    ");
    $weeklyStatsStmt->execute();
    $weeklyStats = $weeklyStatsStmt->fetchAll();
    
    sendJsonResponse([
        'success' => true,
        'data' => [
            'totalSpins' => $totalSpins,
            'activeCoupons' => $activeCoupons,
            'uniqueUsers' => $uniqueUsers,
            'recentActivity' => $recentActivity,
            'weeklyStats' => $weeklyStats,
            'currentWeek' => $currentWeek
        ]
    ]);
}

function getCouponsData() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM coupons ORDER BY created_at DESC");
    $stmt->execute();
    $coupons = $stmt->fetchAll();
    
    sendJsonResponse([
        'success' => true,
        'coupons' => $coupons
    ]);
}

function getSpinsData() {
    global $pdo;
    
    try {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        
        // Ensure positive values
        $page = max(1, $page);
        $limit = max(1, min(100, $limit)); // Cap at 100 to prevent abuse
        $offset = max(0, $offset);
        
        // Use named parameters for LIMIT/OFFSET as they work better with some MySQL configurations
        $stmt = $pdo->prepare("SELECT * FROM spins ORDER BY timestamp DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $spins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM spins");
        $countStmt->execute();
        $totalSpins = (int)$countStmt->fetchColumn();
        
        sendJsonResponse([
            'success' => true,
            'spins' => $spins,
            'total' => $totalSpins,
            'page' => $page,
            'limit' => $limit,
            'debug' => [
                'query_limit' => $limit,
                'query_offset' => $offset,
                'result_count' => count($spins)
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("getSpinsData error: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'error' => 'Failed to load spins data: ' . $e->getMessage(),
            'debug' => [
                'page' => $page ?? 'undefined',
                'limit' => $limit ?? 'undefined',
                'offset' => $offset ?? 'undefined'
            ]
        ], 500);
    }
}

function getSettingsData() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Get current week info
    $weekStmt = $pdo->prepare("SELECT * FROM weekly_settings ORDER BY id DESC LIMIT 1");
    $weekStmt->execute();
    $weekData = $weekStmt->fetch();
    
    sendJsonResponse([
        'success' => true,
        'settings' => $settings,
        'weekData' => $weekData
    ]);
}

function handlePostRequest() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'add_coupon':
            addCoupon($input);
            break;
        case 'update_week':
            updateWeek($input);
            break;
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

function addCoupon($input) {
    global $pdo;
    
    $required = ['discount_text', 'discount_value', 'coupon_code', 'week1_probability', 'week2_probability', 'week3_probability', 'week4_probability'];
    
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            sendJsonResponse(['error' => "Missing required field: $field"], 400);
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO coupons (discount_text, discount_value, coupon_code, week1_probability, week2_probability, week3_probability, week4_probability, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['discount_text'],
        $input['discount_value'],
        $input['coupon_code'],
        $input['week1_probability'],
        $input['week2_probability'],
        $input['week3_probability'],
        $input['week4_probability'],
        $input['color'] ?? '#667eea'
    ]);
    
    sendJsonResponse(['success' => true, 'message' => 'Coupon added successfully']);
}

function updateWeek($input) {
    global $pdo;
    
    $newWeek = $input['week'] ?? 1;
    
    if ($newWeek < 1 || $newWeek > 4) {
        sendJsonResponse(['error' => 'Invalid week number'], 400);
    }
    
    $stmt = $pdo->prepare("UPDATE weekly_settings SET current_week = ?, week_start_date = ? WHERE id = (SELECT id FROM (SELECT id FROM weekly_settings ORDER BY id DESC LIMIT 1) AS temp)");
    $stmt->execute([$newWeek, date('Y-m-d')]);
    
    sendJsonResponse(['success' => true, 'message' => 'Week updated successfully']);
}

function handlePutRequest() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'update_coupon':
            updateCoupon($input);
            break;
        case 'update_settings':
            updateSettings($input);
            break;
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

function updateCoupon($input) {
    global $pdo;
    
    $id = $input['id'] ?? 0;
    
    if (!$id) {
        sendJsonResponse(['error' => 'Coupon ID is required'], 400);
    }
    
    $stmt = $pdo->prepare("UPDATE coupons SET discount_text = ?, discount_value = ?, coupon_code = ?, week1_probability = ?, week2_probability = ?, week3_probability = ?, week4_probability = ?, color = ? WHERE id = ?");
    $stmt->execute([
        $input['discount_text'],
        $input['discount_value'],
        $input['coupon_code'],
        $input['week1_probability'],
        $input['week2_probability'],
        $input['week3_probability'],
        $input['week4_probability'],
        $input['color'] ?? '#667eea',
        $id
    ]);
    
    sendJsonResponse(['success' => true, 'message' => 'Coupon updated successfully']);
}

function updateSettings($input) {
    global $pdo;
    
    $settings = $input['settings'] ?? [];
    
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    
    sendJsonResponse(['success' => true, 'message' => 'Settings updated successfully']);
}

function handleDeleteRequest() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'delete_coupon':
            deleteCoupon($input);
            break;
        case 'remove_all_coupons':
            removeAllCoupons($input);
            break;
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

function deleteCoupon($input) {
    global $pdo;
    
    $id = $input['id'] ?? 0;
    
    if (!$id) {
        sendJsonResponse(['error' => 'Coupon ID is required'], 400);
    }
    
    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->execute([$id]);
    
    sendJsonResponse(['success' => true, 'message' => 'Coupon deleted successfully']);
}

function removeAllCoupons($input) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Delete all coupon codes first (to maintain referential integrity)
        $stmt = $pdo->prepare("DELETE FROM tier_coupon_codes");
        $stmt->execute();
        
        // Delete all coupons
        $stmt = $pdo->prepare("DELETE FROM coupons");
        $stmt->execute();
        
        $pdo->commit();
        
        sendJsonResponse(['success' => true, 'message' => 'All coupons removed successfully']);
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error removing all coupons: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to remove coupons: ' . $e->getMessage()], 500);
    }
}
?>