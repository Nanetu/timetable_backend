<?php

require_once 'PHPMailer-master/src/PHPMailer.php';
require_once 'PHPMailer-master/src/SMTP.php';
require_once 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail ($from, $to, $name, $subject, $school, $body){

    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nanetumushongoma@gmail.com';
        $mail->Password = 'eqgm xima ohhz ljtv';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
    
        // Recipients
        //$mail->setFrom($from, $school);
        $mail->addAddress($to, $name);
    
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
    
        $mail->send();
        
        return ['success' => true, 'message' => 'Email sent successfully to ' . $to];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Email failed: {$mail->ErrorInfo}"];
    }

}

?>