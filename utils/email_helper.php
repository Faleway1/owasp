<?php

function envoyerMail($to, $subject, $messageHTML, $messageTEXT, $from = null) {
    if ($from === null) {
        $from = 'noreply@mediatek.mael-cv.me';
    }

    $boundary = md5(time());

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $headers .= "From: MediaTek <$from>\r\n";
    $headers .= "Reply-To: contact@mediatek.mael-cv.me\r\n";
    $headers .= "X-Mailer: MediaTekMailer/1.0\r\n";

    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= $messageTEXT."\r\n\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $messageHTML."\r\n";
    $body .= "--$boundary--";

    if (!mail($to, $subject, $body, $headers)) {
        error_log("Échec d'envoi d'email à $to");
        return false;
    }
    return true;
}

function creerJeton() {
    return bin2hex(random_bytes(32));
}

function envoiMailVerif($to, $token) {
    $subject = "Vérification de votre adresse email - MediaTek";
    $verificationUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/verify_email.php?email=' . urlencode($to) . '&token=' . $token;

    $message = "/* contenu HTML (inchangé) */";
    $messageTEXT = "Veuillez cliquer sur le lien suivant pour vérifier votre adresse email: $verificationUrl";

    return envoyerMail($to, $subject, $message, $messageTEXT);
}

function envoiMailResetMdp($to, $token) {
    $subject = "Réinitialisation de votre mot de passe - MediaTek";
    $resetUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/reset_password.php?email=' . urlencode($to) . '&token=' . $token;

    $message = "/* contenu HTML (inchangé) */";
    $messageTEXT = "Veuillez cliquer sur le lien suivant pour réinitialiser votre mot de passe: $resetUrl";

    return envoyerMail($to, $subject, $message, $messageTEXT);
}

function envoiCode2FA($to, $code) {
    $subject = "Code de vérification pour votre connexion - MediaTek";

    $message = "/* contenu HTML (inchangé) */";
    $messageTEXT = "Voici votre code de vérification pour vous connecter à MediaTek: $code";

    return envoyerMail($to, $subject, $message, $messageTEXT);
}

?>