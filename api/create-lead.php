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
    echo json_encode(['success' => false, 'error' => 'Missing required fields: mobile and couponCode']);
    exit;
}

$mobile = trim($input['mobile']);
$couponCode = trim($input['couponCode']);
$prize = isset($input['prize']) ? trim($input['prize']) : '';
$utmSource = isset($input['utm_source']) ? trim($input['utm_source']) : '';
$utmMedium = isset($input['utm_medium']) ? trim($input['utm_medium']) : '';
$utmCampaign = isset($input['utm_campaign']) ? trim($input['utm_campaign']) : '';
$utmTerm = isset($input['utm_term']) ? trim($input['utm_term']) : '';
$utmContent = isset($input['utm_content']) ? trim($input['utm_content']) : '';

if (empty($mobile) || empty($couponCode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Mobile and coupon code cannot be empty']);
    exit;
}

// Zoho CRM Configuration
$zohoConfig = [
    'refresh_token' => '1000.87d46a9452ef0ebff0dda387600c2c02.2de6340e1dc6dbbe7678dbcf750d32d9',
    'client_id' => '1000.ZMJPNTCY7CC8E2PNSL0PYC3DBHD9MM',
    'client_secret' => '38bbe77d6992b2b99127bf297761a0f469d5647ab5',
    'base_url' => 'https://www.zohoapis.in/crm/v2.1'
];

/**
 * Get access token from Zoho using refresh token
 */
function getZohoAccessToken($config) {
    $tokenUrl = "https://accounts.zoho.in/oauth/v2/token";
    $tokenData = [
        'refresh_token' => $config['refresh_token'],
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'grant_type' => 'refresh_token'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $tokenUrl . '?' . http_build_query($tokenData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        curl_close($ch);
        throw new Exception('cURL Error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to get access token. HTTP Code: ' . $httpCode);
    }
    
    $tokenResponse = json_decode($response, true);
    
    if (!isset($tokenResponse['access_token'])) {
        throw new Exception('Access token not found in response: ' . $response);
    }
    
    return $tokenResponse['access_token'];
}

/**
 * Search for existing lead by mobile number
 */
function searchLeadByMobile($accessToken, $mobile, $baseUrl) {
    $searchUrl = $baseUrl . "/Leads/search?criteria=((Mobile:equals:" . urlencode($mobile) . "))";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $searchUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Zoho-oauthtoken ' . $accessToken,
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('cURL Error in search: ' . $error);
    }
    
    curl_close($ch);
    
    if ($httpCode === 204) {
        // No leads found
        return null;
    }
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to search leads. HTTP Code: ' . $httpCode . ', Response: ' . $response);
    }
    
    $searchResponse = json_decode($response, true);
    
    if (isset($searchResponse['data']) && count($searchResponse['data']) > 0) {
        return $searchResponse['data'][0]; // Return first matching lead
    }
    
    return null;
}

/**
 * Create a new lead in Zoho CRM
 */
function createLeadInZoho($accessToken, $leadData, $baseUrl) {
    $createUrl = $baseUrl . "/Leads";
    
    $postData = [
        'data' => [$leadData],
        'trigger' => ['approval', 'workflow', 'blueprint']
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $createUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Authorization: Zoho-oauthtoken ' . $accessToken,
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('cURL Error in create: ' . $error);
    }
    
    curl_close($ch);
    
    if ($httpCode !== 201) {
        throw new Exception('Failed to create lead. HTTP Code: ' . $httpCode . ', Response: ' . $response);
    }
    
    return json_decode($response, true);
}

/**
 * Update existing lead in Zoho CRM
 */
function updateLeadInZoho($accessToken, $leadId, $updateData, $baseUrl) {
    $updateUrl = $baseUrl . "/Leads/" . $leadId;
    
    $postData = [
        'data' => [$updateData],
        'trigger' => ['approval', 'workflow', 'blueprint']
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $updateUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Authorization: Zoho-oauthtoken ' . $accessToken,
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('cURL Error in update: ' . $error);
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to update lead. HTTP Code: ' . $httpCode . ', Response: ' . $response);
    }
    
    return json_decode($response, true);
}

try {
    // Get access token
    $accessToken = getZohoAccessToken($zohoConfig);
    
    // Search for existing lead
    $existingLead = searchLeadByMobile($accessToken, $mobile, $zohoConfig['base_url']);
    
    // Prepare lead data
    $leadData = [
        'Mobile' => $mobile,
        'Phone' => $mobile,
        'Lead_Source' => 'Spin Wheel Campaign',
        'Description' => 'Lead generated from Oliva Spin Wheel. Prize won: ' . $prize . '. Coupon Code: ' . $couponCode
    ];
    
    // Add UTM parameters if available
    if (!empty($utmSource)) $leadData['UTM_Source'] = $utmSource;
    if (!empty($utmMedium)) $leadData['UTM_Medium'] = $utmMedium;
    if (!empty($utmCampaign)) $leadData['UTM_Campaign'] = $utmCampaign;
    if (!empty($utmTerm)) $leadData['UTM_Term'] = $utmTerm;
    if (!empty($utmContent)) $leadData['UTM_Content'] = $utmContent;
    
    // Add custom fields for spin wheel data
    $leadData['Spin_Wheel_Prize'] = $prize;
    $leadData['Spin_Wheel_Coupon'] = $couponCode;
    $leadData['Spin_Wheel_Date'] = date('Y-m-d H:i:s');
    
    if ($existingLead) {
        // Update existing lead
        $leadData['id'] = $existingLead['id'];
        $result = updateLeadInZoho($accessToken, $existingLead['id'], $leadData, $zohoConfig['base_url']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Lead updated successfully',
            'action' => 'updated',
            'lead_id' => $existingLead['id'],
            'result' => $result
        ]);
    } else {
        // Create new lead - need Last_Name for creation
        $leadData['Last_Name'] = 'Spin Wheel Lead';
        $leadData['First_Name'] = 'Festival of Youth';
        
        $result = createLeadInZoho($accessToken, $leadData, $zohoConfig['base_url']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Lead created successfully',
            'action' => 'created',
            'result' => $result
        ]);
    }
    
} catch (Exception $e) {
    error_log('Zoho Lead Creation Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to process lead: ' . $e->getMessage()
    ]);
}
?>