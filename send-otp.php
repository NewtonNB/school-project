<?php
session_start();

// Function to generate a 6-digit OTP
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to send SMS using Twilio (you'll need to sign up for a Twilio account)
function sendSMS($to, $message) {
    // Your Twilio Account SID and Auth Token
    $account_sid = 'YOUR_TWILIO_ACCOUNT_SID';
    $auth_token = 'YOUR_TWILIO_AUTH_TOKEN';
    
    // Your Twilio phone number
    $twilio_number = 'YOUR_TWILIO_PHONE_NUMBER';
    
    // Initialize Twilio client
    $client = new Twilio\Rest\Client($account_sid, $auth_token);
    
    try {
        // Send SMS
        $message = $client->messages->create(
            $to,
            [
                'from' => $twilio_number,
                'body' => $message
            ]
        );
        return true;
    } catch (Exception $e) {
        error_log("SMS sending failed: " . $e->getMessage());
        return false;
    }
}

// Handle the incoming request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    
    // Validate phone number
    if (empty($phone) || !preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
        exit;
    }
    
    // Generate OTP
    $otp = generateOTP();
    
    // Store OTP in session
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_time'] = time();
    $_SESSION['phone'] = $phone;
    
    // Prepare SMS message
    $message = "Your OTP for Nyabikoni Secondary School contact form is: " . $otp . ". Valid for 5 minutes.";
    
    // Send SMS
    if (sendSMS($phone, $message)) {
        echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
