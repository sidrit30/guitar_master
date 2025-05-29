<?php

require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
function send_mail($to, $username, $password): void
{
    try{
        $mail = new PHPMailer();
        $mail->Debugoutput = "error_log";

//Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = 'guitar.master.tabs@gmail.com';                     //SMTP username
        $mail->Password   = 'inrd yyam gnmw xfzc';                               //SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
        $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

//Recipients
        $mail->setFrom('guitar.master.tabs@gmail.com', 'Guitar Master');
        $mail->addAddress($to);     //Add a recipient


//Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = 'Reset Password';
        $mail->Body    =
"Hello! Your password has been reset! " . PHP_EOL ."username: " . $username . PHP_EOL ."password: ".$password;



        $mail->send();
    } catch (Exception $e) {

    }
}

function ban_mail($to): void
{
    try{
        $mail = new PHPMailer();
        $mail->Debugoutput = "error_log";

//Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = 'guitar.master.tabs@gmail.com';                     //SMTP username
        $mail->Password   = 'inrd yyam gnmw xfzc';                               //SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
        $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

//Recipients
        $mail->setFrom('guitar.master.tabs@gmail.com', 'Guitar Master');
        $mail->addAddress($to);     //Add a recipient


//Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = 'Banned account!';
        $mail->Body    =
            "Your account has been banned for misbehaviour";



        $mail->send();
    } catch (Exception $e) {

    }
}