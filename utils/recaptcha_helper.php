<?php
/**
 * Fonctions d'aide pour la vérification reCAPTCHA
 */

/**
 * Vérifie un token reCAPTCHA
 * 
 * @param string $token Token reCAPTCHA à vérifier
 * @param string $action Action attendue (login, register, etc.)
 * @param float $minScore Score minimal (entre 0 et 1, 1 étant le plus strict)
 * @return bool True si le token est valide et dépasse le score minimal
 */
function verifyRecaptcha($token, $action, $minScore = 0.5) {
    // Clé API sécurisée
    $apiKey = 'AIzaSyDH8ZblnFMGSHZPkLREilSO-kCHDq89lvM';
    $siteKey = '6LdDtPkqAAAAAL0juO4A49LHTI_NJ_ibCEiKaYwk';
    
    // Si on est en environnement de développement, on peut court-circuiter la vérification
    if (ENVIRONMENT !== 'production') {
        return true;
    }
    
    // Si le token est vide, échouer immédiatement
    if (empty($token)) {
        return false;
    }
    
    // Données à envoyer à l'API reCAPTCHA Enterprise au format JSON
    $payload = json_encode([
        'event' => [
            'token' => $token,
            'siteKey' => $siteKey,
            'expectedAction' => $action
        ]
    ]);
    
    // Initialiser cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://recaptchaenterprise.googleapis.com/v1/projects/owasp-1742406114279/assessments?key={$apiKey}");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Exécuter la requête
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Décoder la réponse JSON
    $result = json_decode($response, true);
    
    // Journaliser en cas d'erreur
    if ($httpCode !== 200) {
        error_log("Erreur reCAPTCHA ({$httpCode}): " . json_encode($result));
        return false;
    }
    
    // Vérifier le score et l'action
    if (!isset($result['score']) || !isset($result['tokenProperties'])) {
        error_log("Réponse reCAPTCHA invalide: " . json_encode($result));
        return false;
    }
    
    $tokenProperties = $result['tokenProperties'];
    $scoreValue = $result['score'];
    $actionValid = isset($tokenProperties['action']) && $tokenProperties['action'] === $action;
    $tokenValid = isset($tokenProperties['valid']) && $tokenProperties['valid'] === true;
    
    $isValid = $scoreValue >= $minScore && $actionValid && $tokenValid;
    
    // Journaliser les échecs de vérification en production
    if (!$isValid && ENVIRONMENT === 'production') {
        $reason = [];
        if ($scoreValue < $minScore) $reason[] = "score trop bas ({$scoreValue} < {$minScore})";
        if (!$actionValid) $reason[] = "action invalide";
        if (!$tokenValid) $reason[] = "token invalide";
        
        error_log("Échec de vérification reCAPTCHA: " . implode(", ", $reason));
    }
    
    return $isValid;
}