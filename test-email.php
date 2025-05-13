<?php
require_once 'send-email.php';

// Test data
$testData = [
    'firstName' => 'John',
    'lastName' => 'Doe',
    'email' => 'test@example.com',
    'phone' => '+256123456789',
    'message' => 'This is a test message to verify the email system is working properly. Please ignore this message as it is just a test.'
];

// Try to send the test email
$result = sendContactEmail(
    $testData['firstName'],
    $testData['lastName'],
    $testData['email'],
    $testData['phone'],
    $testData['message']
);

// Output the result
if ($result['success']) {
    echo "Test email sent successfully! Please check nyabikonisecschool@gmail.com for the test message.";
} else {
    echo "Failed to send test email: " . $result['message'];
}
?> 