<?php
// Ultra-simple debug version to isolate the 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Log everything to help debug
error_log("=== SPIN DEBUG START ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Step 1: Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log("Not POST method");
        http_response_code(405);
        echo json_encode(['error' => 'Only POST method allowed', 'debug' => 'method_check']);
        exit;
    }
    
    // Step 2: Try to get input
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput);
    
    if (empty($rawInput)) {
        error_log("Empty input");
        http_response_code(400);
        echo json_encode(['error' => 'No input received', 'debug' => 'empty_input']);
        exit;
    }
    
    // Step 3: Try to decode JSON
    $input = json_decode($rawInput, true);
    error_log("Decoded input: " . print_r($input, true));
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON', 'debug' => 'json_decode', 'json_error' => json_last_error_msg()]);
        exit;
    }
    
    // Step 4: Check recordedId
    if (!isset($input['recordedId'])) {
        error_log("No recordedId in input");
        http_response_code(400);
        echo json_encode(['error' => 'recordedId missing', 'debug' => 'missing_recordedId']);
        exit;
    }
    
    $recordedId = trim($input['recordedId']);
    error_log("RecordedId: " . $recordedId);
    
    if (empty($recordedId)) {
        error_log("Empty recordedId");
        http_response_code(400);
        echo json_encode(['error' => 'recordedId empty', 'debug' => 'empty_recordedId']);
        exit;
    }
    
    // Step 5: Try to include config
    if (!file_exists('config.php')) {
        error_log("config.php not found");
        http_response_code(500);
        echo json_encode(['error' => 'config.php not found', 'debug' => 'config_missing']);
        exit;
    }
    
    try {
        require_once 'config.php';
        error_log("Config loaded successfully");
    } catch (Exception $e) {
        error_log("Config load error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Config load failed', 'debug' => 'config_load_error', 'message' => $e->getMessage()]);
        exit;
    }
    
    // Step 6: Check database connection
    if (!isset($pdo)) {
        error_log("PDO not set");
        http_response_code(500);
        echo json_encode(['error' => 'Database not available', 'debug' => 'no_pdo']);
        exit;
    }
    
    try {
        $pdo->query("SELECT 1");
        error_log("Database connection OK");
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed', 'debug' => 'db_connection_error', 'message' => $e->getMessage()]);
        exit;
    }
    
    // Step 7: Return success if we get this far
    error_log("All checks passed, returning success");
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'debug' => 'all_checks_passed',
        'recordedId' => $recordedId,
        'message' => 'Debug successful - ready for full implementation'
    ]);
    
} catch (Exception $e) {
    error_log("Caught exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Exception caught', 'debug' => 'exception', 'message' => $e->getMessage()]);
} catch (Error $e) {
    error_log("Caught error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Error caught', 'debug' => 'error', 'message' => $e->getMessage()]);
}

error_log("=== SPIN DEBUG END ===");
?>