<?php
/**
 * Fonctions sécurisées pour la gestion des uploads d'images
 */

/**
 * Vérifie et traite l'upload d'une image de couverture
 * 
 * @param array $fileData Les données du fichier ($_FILES['cover'])
 * @return array Tableau associatif [success, message, filepath]
 */
function processImageUpload($fileData) {
    // Initialisation du résultat
    $result = [
        'success' => false,
        'message' => '',
        'filepath' => null
    ];
    
    // Vérification des erreurs d'upload
    if ($fileData['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par PHP.',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée par le formulaire.',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé.',
            UPLOAD_ERR_NO_TMP_DIR => 'Le dossier temporaire est manquant.',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque.',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload.'
        ];
        $result['message'] = isset($errors[$fileData['error']]) ? 
            $errors[$fileData['error']] : 'Erreur inconnue lors de l\'upload.';
        return $result;
    }

    // Limite de taille (5MB)
    $maxFileSize = 5 * 1024 * 1024; // 5 MB en octets
    if ($fileData['size'] > $maxFileSize) {
        $result['message'] = 'Le fichier est trop volumineux. Taille maximale: 5 MB.';
        return $result;
    }
    
    // Vérification du type MIME
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $fileMimeType = $finfo->file($fileData['tmp_name']);
    
    if (!in_array($fileMimeType, $allowedMimeTypes)) {
        $result['message'] = 'Type de fichier non autorisé. Formats acceptés: JPEG, PNG, GIF, WEBP.';
        return $result;
    }
    
    // Vérification de l'extension
    $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extension, $allowedExtensions)) {
        $result['message'] = 'Extension de fichier non autorisée. Extensions acceptées: jpg, jpeg, png, gif, webp.';
        return $result;
    }
    
    // Validation supplémentaire pour s'assurer qu'il s'agit bien d'une image
    if (!getimagesize($fileData['tmp_name'])) {
        $result['message'] = 'Le fichier ne semble pas être une image valide.';
        return $result;
    }
    
    // Génération d'un nom de fichier unique
    $uniqueFilename = uniqid('cover_', true) . '.' . $extension;
    $uploadDir = __DIR__ . '/../uploads/covers/';
    $uploadPath = $uploadDir . $uniqueFilename;
    
    // Création du répertoire d'upload s'il n'existe pas
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $result['message'] = 'Impossible de créer le répertoire d\'upload.';
            return $result;
        }
    }
    
    // Déplacer le fichier vers l'emplacement final
    if (move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
        // Succès
        $result['success'] = true;
        $result['message'] = 'Le fichier a été uploadé avec succès.';
        $result['filepath'] = 'uploads/covers/' . $uniqueFilename;
        
        // Définir les permissions correctes sur le fichier
        chmod($uploadPath, 0644);
    } else {
        $result['message'] = 'Une erreur est survenue lors du déplacement du fichier.';
    }
    
    return $result;
}
?>