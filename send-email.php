<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

function sendContactEmail($firstName, $lastName, $email, $phone, $message, $attachment = null) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;                      // Enable verbose debug output
        $mail->isSMTP();                                         // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                    // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                // Enable SMTP authentication
        $mail->Username   = 'tukamuhebwanewton@gmail.com';       // SMTP username
        $mail->Password   = 'qeeuyrvmzserzdfe';                  // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;      // Enable TLS encryption
        $mail->Port       = 587;                                 // TCP port to connect to

        // Recipients
        $mail->setFrom('tukamuhebwanewton@gmail.com', 'Nyabikoni Secondary School Contact Form');
        $mail->addAddress('nyabikonisecschool@gmail.com', 'Nyabikoni Secondary School');
        $mail->addReplyTo($email, $firstName . ' ' . $lastName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Contact Form Submission from $firstName $lastName";
        
        // Email body
        $emailBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { padding: 20px; }
                    .header { background-color: #268b17; color: white; padding: 10px; }
                    .content { padding: 20px; }
                    .footer { background-color: #f4f4f4; padding: 10px; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>New Contact Form Submission</h2>
                    </div>
                    <div class='content'>
                        <p><strong>Name:</strong> $firstName $lastName</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Phone:</strong> $phone</p>
                        <p><strong>Message:</strong></p>
                        <p>" . nl2br(htmlspecialchars($message)) . "</p>
                    </div>
                    <div class='footer'>
                        <p>This email was sent from the Nyabikoni Secondary School contact form.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->Body = $emailBody;
        $mail->AltBody = "Name: $firstName $lastName\nEmail: $email\nPhone: $phone\nMessage: $message";

        // Add attachment if present
        if ($attachment && file_exists($attachment)) {
            $mail->addAttachment($attachment);
        }

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => "Failed to send email. Mailer Error: {$mail->ErrorInfo}"];
    }
}
?> 