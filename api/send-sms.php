<?php
// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Only POST requests allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!$input || !isset($input['mobile']) || !isset($input['couponCode'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$mobile = trim($input['mobile']);
$couponCode = trim($input['couponCode']);

if (empty($mobile) || empty($couponCode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid data provided']);
    exit;
}

// Add India country code if not present
if (!preg_match('/^(\+91|91)/', $mobile)) {
    $mobile = '91' . $mobile;
} else if (preg_match('/^\+91/', $mobile)) {
    $mobile = substr($mobile, 1); // Remove + sign, keep 91
}

$authKey = "274549AP7UXja2C6u55cc7fa62";
$senderId = "OLIVA";
// The appointment phone number can be changed here
$appointmentPhoneNumber = "9205481482";

// Use template-based SMS for DLT compliance
$url = 'https://control.msg91.com/api/v5/flow/';

$postData = array(
    'template_id' => '1107175922859923122',
    'short_url' => '0',
    'recipients' => array(
        array(
            'mobiles' => $mobile,
            'var1' => $couponCode,
            'var2' => $appointmentPhoneNumber
        )
    )
);

$headers = array(
    'authkey: ' . $authKey,
    'content-type: application/JSON'
);

$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($postData),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0
));

$output = curl_exec($ch);

if(curl_errno($ch)){
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'cURL Error: ' . curl_error($ch)]);
} else {
    // Flow API returns JSON response
    $response = json_decode($output, true);
    
    if ($response && isset($response['type']) && $response['type'] === 'success') {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'SMS sent successfully', 'response' => $response]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'MSG91 Error: ' . $output]);
    }
}

curl_close($ch);
?>