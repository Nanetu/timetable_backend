<?php

require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';
require_once 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // SMTP configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';           // or smtp.office365.com
    $mail->SMTPAuth = true;
    $mail->Username = 'your_email@gmail.com'; // your Gmail
    $mail->Password = 'your_app_password';    // App password
    $mail->SMTPSecure = 'tls';                // or 'ssl'
    $mail->Port = 587;                        // or 465 for SSL

    // Recipients
    $mail->setFrom('your_email@gmail.com', 'Timetable System');
    $mail->addAddress('recipient@example.com', 'Student User');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = '<strong>Hello</strong>, this is a test email from your PHP timetable system.';

    $mail->send();
    echo 'Email sent successfully.';
} catch (Exception $e) {
    echo "Email failed: {$mail->ErrorInfo}";
}

?>