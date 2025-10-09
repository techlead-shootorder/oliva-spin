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

// Debug: Log the input to see what we're receiving
error_log('Create Lead Input: ' . json_encode($input));

// Validate input
if (!$input || !isset($input['mobile']) || !isset($input['couponCode'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields: mobile and couponCode']);
    exit;
}

$mobile = trim($input['mobile']);
$couponCode = trim($input['couponCode']);
$userName = isset($input['user_name']) ? trim($input['user_name']) : 
           (isset($input['userName']) ? trim($input['userName']) : 
           (isset($input['name']) ? trim($input['name']) : 
           (isset($input['fullname']) ? trim($input['fullname']) : 
           (isset($input['full_name']) ? trim($input['full_name']) : ''))));

// Debug: Log the extracted userName
error_log('Extracted userName: ' . var_export($userName, true));
$prize = isset($input['prize']) ? trim($input['prize']) : '';
$utmSource = isset($input['utm_source']) ? trim($input['utm_source']) : '';
$utmMedium = isset($input['utm_medium']) ? trim($input['utm_medium']) : '';
$utmCampaign = isset($input['utm_campaign']) ? trim($input['utm_campaign']) : '';
$utmTerm = isset($input['utm_term']) ? trim($input['utm_term']) : '';
$utmContent = isset($input['utm_content']) ? trim($input['utm_content']) : '';
$subSource = isset($input['sub_source']) ? trim($input['sub_source']) : 'Email';
$city = isset($input['city']) ? trim($input['city']) : '';
$browser = isset($input['browser']) ? trim($input['browser']) : '';
$os = isset($input['os']) ? trim($input['os']) : '';
$prevUrl = isset($input['prev_url']) ? trim($input['prev_url']) : '';

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
        'Lead_Source' => $utmSource ?: 'Oliva Spin Wheel',
        'Description' => 'Lead generated from Oliva Spin Wheel. Prize won: ' . $prize . '. Coupon Code: ' . $couponCode,
        'Language' => 'English'
    ];
    
    // Set lead name properly
    if (!empty($userName) && strtolower($userName) !== 'guest' && strlen(trim($userName)) > 1) {
        // Parse the full name into first and last name
        $nameParts = explode(' ', trim($userName), 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) && !empty($nameParts[1]) ? $nameParts[1] : 'User';
        
        $leadData['Lead_Name'] = $userName;
        $leadData['First_Name'] = $firstName;
        $leadData['Last_Name'] = $lastName;
    } else {
        $leadData['Lead_Name'] = 'Guest User';
        $leadData['First_Name'] = 'Guest';
        $leadData['Last_Name'] = 'User';
    }
    
    // Final safeguard: Ensure Last_Name is never empty
    if (empty($leadData['Last_Name']) || trim($leadData['Last_Name']) === '') {
        $leadData['Last_Name'] = 'User';
    }
    
    // Debug: Log the name data being sent to Zoho
    error_log('Name data being sent to Zoho CRM:');
    error_log('Lead_Name: ' . $leadData['Lead_Name']);
    error_log('First_Name: ' . $leadData['First_Name']);
    error_log('Last_Name: ' . $leadData['Last_Name']);
    
    // Add UTM parameters if available
    if (!empty($utmSource)) $leadData['utm_source'] = $utmSource;
    if (!empty($utmMedium)) $leadData['utm_medium'] = $utmMedium;
    if (!empty($utmCampaign)) $leadData['utm_campaign'] = $utmCampaign;
    if (!empty($utmTerm)) $leadData['utm_term'] = $utmTerm;
    if (!empty($utmContent)) $leadData['utm_content'] = $utmContent;
    
    // Add mandatory fields: Sub_Source and City
    $leadData['Sub_Source'] = $subSource; // Default: 'Email'
    if (!empty($city)) $leadData['City'] = $city;
    
    // Add browser and device info
    if (!empty($browser)) $leadData['Browser'] = $browser;
    if (!empty($os)) $leadData['OS'] = $os;
    if (!empty($prevUrl)) $leadData['prev_url'] = $prevUrl;
    
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
        // Create new lead - First_Name and Last_Name already set above
        
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