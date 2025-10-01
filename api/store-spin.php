<?php
require_once 'config.php';

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
    sendJsonResponse(['success' => false, 'error' => 'Only POST requests allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!$input || !isset($input['recordedId']) || !isset($input['result'])) {
    sendJsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
}

$recordedId = trim($input['recordedId']);
$result = trim($input['result']);
$timestamp = isset($input['timestamp']) ? (int)$input['timestamp'] : time() * 1000;
$ipAddress = getClientIP();

// Validate data
if (empty($recordedId) || empty($result)) {
    sendJsonResponse(['success' => false, 'error' => 'Invalid data provided'], 400);
}

try {
    // Check if tracking is enabled
    $settingStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'enable_tracking'");
    $settingStmt->execute();
    $enableTracking = $settingStmt->fetchColumn();
    
    if (!$enableTracking || $enableTracking !== '1') {
        sendJsonResponse(['success' => false, 'error' => 'Spin tracking is disabled'], 403);
    }
    
    // Check max spins limit
    $maxSpinsStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'max_spins'");
    $maxSpinsStmt->execute();
    $maxSpins = (int)$maxSpinsStmt->fetchColumn();
    
    if ($maxSpins > 0) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM spins WHERE recorded_id = ?");
        $countStmt->execute([$recordedId]);
        $currentSpins = $countStmt->fetchColumn();
        
        if ($currentSpins >= $maxSpins) {
            sendJsonResponse(['success' => false, 'error' => 'Maximum spins exceeded'], 429);
        }
    }
    
    // Insert spin record
    $insertStmt = $pdo->prepare("
        INSERT INTO spins (recorded_id, result, timestamp, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    
    $insertStmt->execute([$recordedId, $result, $timestamp, $ipAddress]);
    $spinId = $pdo->lastInsertId();

    // Zoho Integration
    $zoho = new Zoho();
    $zohoResult = $zoho->createLead($input);

    // SMS Integration
    $smsData = [
        'mobile' => $input['mobile'],
        'couponCode' => $input['CouponCode']
    ];

    $ch_sms = curl_init('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/send-sms.php');
    curl_setopt($ch_sms, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_sms, CURLOPT_POST, true);
    curl_setopt($ch_sms, CURLOPT_POSTFIELDS, json_encode($smsData));
    curl_setopt($ch_sms, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

    $smsResult = curl_exec($ch_sms);
    curl_close($ch_sms);
    
    // Return success response
    sendJsonResponse([
        'success' => true,
        'message' => 'Spin recorded successfully',
        'data' => [
            'id' => $spinId,
            'recordedId' => $recordedId,
            'result' => $result,
            'timestamp' => $timestamp,
            'ipAddress' => $ipAddress
        ],
        'zoho_status' => $zohoResult,
        'sms_status' => $smsResult
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in store-spin.php: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log("General error in store-spin.php: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => 'An error occurred'], 500);
}

Class Zoho {
    public function getAccessToken(){
        $url = 'https://accounts.zoho.in/oauth/v2/token';
        $fields = array(
            'refresh_token' => urlencode("1000.87d46a9452ef0ebff0dda387600c2c02.2de6340e1dc6dbbe7678dbcf750d32d9"),
            'client_id' => urlencode("1000.ZMJPNTCY7CC8E2PNSL0PYC3DBHD9MM"),
            'client_secret' => urlencode("38bbe77d6992b2b99127bf297761a0f469d5647ab5"),
            'grant_type' => urlencode("refresh_token")
        );
        
        $postString = '';
        foreach($fields as $key=>$value) { $postString .= $key.'='.$value.'&'; }
        rtrim($postString, '&');

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $postString);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        $result = curl_exec($ch);
        curl_close($ch);

        if($result != false){
            $result = json_decode($result);
            return $result->access_token;
        } else {
            return $result;
        }
    }

    public function createLead($data){
        $token = $this->getAccessToken();

        if($token != false){
            $url = 'https://www.zohoapis.in/crm/v2/Leads';
            
            $headers = array(
                'Authorization: Zoho-oauthtoken '.$token
            );

            $postFields = '{
                "data": [
                    {
                        "Last_Name": "'.$data['Name'].'",
                        "Phone_1": "'.$data['mobile'].'",
                        "City": "'.$data['city'].'",
                        "Concerns": "'.$data['concern'].'",
                        "utm_source": "'.$data['$lsource'].'",
                        "utm_medium": "'.$data['medium'].'",
                        "utm_campaign": "'.$data['campaign'].'",
                        "Lead_Source":  "'.$data['$lsource'].'",
                        "Browser":  "'.$data['browser'].'",
                        "IP_Address":  "'.$data['ip_address'].'",
                        "OS": "'.$data['os'].'",
                        "Device": "'.$data['device_type'].'",
                        "Sub_Source": "Email",
                        "url": "'.$data['web_url'].'",
                        "prev_url": "'.$data['prev_url'].'",
                        "trigger":["workflow","blueprint","approval"]
                    }
                ]
            }';

            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch,CURLOPT_POST, 1);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
            $result = curl_exec($ch);
            $result = json_decode($result);
            curl_close($ch);

            return $result;
        }
        return null;
    }

    
}
?>
?>