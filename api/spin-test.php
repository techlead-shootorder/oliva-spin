<?php
// Simple test version to isolate the problem
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    echo json_encode([
        'test' => 'basic_response',
        'success' => true,
        'message' => 'API is responding'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Exception in basic test',
        'message' => $e->getMessage()
    ]);
}
?>