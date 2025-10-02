<?php
//
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
$action = $_POST['action'];
if ($action == 'ajax_contact_form_mobile_otp') {
    require_once 'config.php'; // Include database configuration and connection

    $mobile = $_POST['mobile_number'];
    $userName = isset($_POST['user_name']) ? $_POST['user_name'] : '';

    // Check if the phone number already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_spins WHERE recorded_id = ?");
    $stmt->execute([$mobile]);
    $userExists = $stmt->fetchColumn();

    if ($userExists > 0) {
        // User exists, get their previous result and coupon code
        $stmt = $pdo->prepare("SELECT result, coupon_code FROM spins WHERE recorded_id = ? ORDER BY timestamp DESC LIMIT 1");
        $stmt->execute([$mobile]);
        $previousData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'type' => 'existing_user',
            'message' => 'You have already participated',
            'previous_result' => $previousData['result'] ?? 'Unknown',
            'previous_coupon_code' => $previousData['coupon_code'] ?? 'N/A',
            'recorded_id' => $mobile,
            'user_name' => $userName
        ]);
        die;
    }

    // User does not exist, proceed to send OTP
    $authkey = '274549AP7UXja2C6u55cc7fa62';
    $otp = rand(1231, 7879);
    $message = 'Your verification code is ' . $otp . ' -Oliva';
    $sender = 'OLIVA';
    $otp_length = '4';

    $url = 'https://api.msg91.com/api/sendotp.php?' . '&authkey=' . $authkey . '&message=' . urlencode($message) . '&sender=' . $sender . '&mobile=91' . $mobile . '&otp=' . $otp . '&email=&otp_expiry=&DLT_TE_ID=1107170963407507109';

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        header('Content-Type: application/json');
        echo json_encode(['type' => 'error', 'message' => 'cURL Error #: ' . $err]);
    } else {
        echo $response;
    }
    die;
}

if ($action == 'ajax_contact_form_mobile_resend_otp') {
//echo "string";
$mobile = $_POST['mobile_number'];
$authkey = '274549AP7UXja2C6u55cc7fa62';

//$url = "http://control.msg91.com/api/retryotp.php?authkey=&mobile=&retrytype=";
$url = 'http://control.msg91.com/api/retryotp.php?authkey=' . $authkey . '&mobile=91' . $mobile . '&retrytype=text';

$curl = curl_init();
curl_setopt_array($curl, array(
CURLOPT_URL => $url,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => "",
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 30,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => "POST",
CURLOPT_POSTFIELDS => "",
CURLOPT_SSL_VERIFYHOST => 0,
CURLOPT_SSL_VERIFYPEER => 0,
CURLOPT_HTTPHEADER => array(
"content-type: application/x-www-form-urlencoded"
),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
echo "cURL Error #:" . $err;
} else {
echo $response;
}
die;
}

if ($action == 'ajax_contact_form_mobile_verified_otp') {
//echo "string";
$mobile = $_POST['mobile_number'];
$mobile_otp = $_POST['mobile_otp'];
$authkey = '274549AP7UXja2C6u55cc7fa62';

//$url = "https://control.msg91.com/api/verifyRequestOTP.php?authkey=&mobile=&otp=";
$url = 'https://control.msg91.com/api/verifyRequestOTP.php?authkey=' . $authkey . '&mobile=91' . $mobile . '&otp=' . $mobile_otp;

$curl = curl_init();
curl_setopt_array($curl, array(
CURLOPT_URL => $url,
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => "",
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 30,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => "POST",
CURLOPT_POSTFIELDS => "",
CURLOPT_SSL_VERIFYHOST => 0,
CURLOPT_SSL_VERIFYPEER => 0,
CURLOPT_HTTPHEADER => array(
"content-type: application/x-www-form-urlencoded"
),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
echo "cURL Error #:" . $err;
} else {
echo $response;
}
die;
}
?>