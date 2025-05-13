<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log errors
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'error.log');
}

// Function to verify reCAPTCHA
function verifyRecaptcha($recaptcha_response) {
    try {
        $secret_key = "YOUR_RECAPTCHA_SECRET_KEY";
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = array(
            'secret' => $secret_key,
            'response' => $recaptcha_response
        );

        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === FALSE) {
            logError("Failed to get reCAPTCHA response");
            return false;
        }

        $result = json_decode($response);
        return $result->success;
    } catch (Exception $e) {
        logError("reCAPTCHA verification error: " . $e->getMessage());
        return false;
    }
}

// Function to handle file upload
function handleFileUpload($file) {
    try {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                logError("Failed to create upload directory");
                return ['success' => false, 'message' => 'Failed to create upload directory'];
            }
        }

        $file_name = basename($file['name']);
        $target_file = $upload_dir . time() . '_' . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if file is a valid type
        $allowed_types = array('jpg', 'jpeg', 'png', 'pdf', 'docx');
        if (!in_array($file_type, $allowed_types)) {
            logError("Invalid file type: " . $file_type);
            return ['success' => false, 'message' => 'Invalid file type. Allowed types: jpg, jpeg, png, pdf, docx'];
        }

        // Check file size (5MB max)
        if ($file['size'] > 5000000) {
            logError("File too large: " . $file['size']);
            return ['success' => false, 'message' => 'File too large. Maximum size is 5MB'];
        }

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return ['success' => true, 'path' => $target_file];
        }

        logError("Failed to move uploaded file");
        return ['success' => false, 'message' => 'Failed to upload file'];
    } catch (Exception $e) {
        logError("File upload error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during file upload'];
    }
}

// Set headers for JSON response
header('Content-Type: application/json');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify reCAPTCHA
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        if (empty($recaptcha_response)) {
            echo json_encode(['success' => false, 'message' => 'reCAPTCHA response is missing']);
            exit;
        }

        if (!verifyRecaptcha($recaptcha_response)) {
            echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed']);
            exit;
        }

        // Verify OTP
        $submitted_otp = $_POST['otp'] ?? '';
        $stored_otp = $_SESSION['otp'] ?? '';
        $otp_time = $_SESSION['otp_time'] ?? 0;

        if (empty($submitted_otp) || empty($stored_otp)) {
            echo json_encode(['success' => false, 'message' => 'OTP verification failed']);
            exit;
        }

        // Check if OTP is expired (5 minutes)
        if (time() - $otp_time > 300) {
            echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one']);
            exit;
        }

        if ($submitted_otp !== $stored_otp) {
            echo json_encode(['success' => false, 'message' => 'Invalid OTP']);
            exit;
        }

        // Handle file upload if present
        $file_path = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleFileUpload($_FILES['attachment']);
            if (!$upload_result['success']) {
                echo json_encode($upload_result);
                exit;
            }
            $file_path = $upload_result['path'];
        }

        // Get and sanitize form data
        $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
        $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $message = filter_input(INPUT_POST, 'formMessage', FILTER_SANITIZE_STRING);

        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            exit;
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit;
        }

        // Prepare email content
        require_once 'send-email.php';
        $email_result = sendContactEmail($firstName, $lastName, $email, $phone, $message, $file_path);

        if ($email_result['success']) {
            // Clear session data
            unset($_SESSION['otp']);
            unset($_SESSION['otp_time']);
            unset($_SESSION['phone']);
            
            echo json_encode(['success' => true, 'message' => 'Form submitted successfully']);
        } else {
            logError("Failed to send email: " . $email_result['message']);
            echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
        }
    } catch (Exception $e) {
        logError("Form submission error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
