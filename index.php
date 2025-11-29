<?php
// -----------------------------------------------------
// CONFIGURATION DU CLOUD
// -----------------------------------------------------
$CODE_ACCES_SECURITE = "160902"; // Votre code d'accès
$DOSSIER_STOCKAGE = "fichiers/"; // Le dossier où sont stockés les fichiers

// Vérifier si la session est déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------
// LOGIQUE D'AUTHENTIFICATION
// -----------------------------------------------------
$estConnecte = isset($_SESSION['cloud_auth']) && $_SESSION['cloud_auth'] === true;

if (!$estConnecte && isset($_POST['code_saisi'])) {
    if ($_POST['code_saisi'] === $CODE_ACCES_SECURITE) {
        $_SESSION['cloud_auth'] = true;
        // Redirection après succès pour éviter le re-soumission du formulaire
        header("Location: index.php");
        exit();
    } else {
        $messageErreur = "Code d'accès incorrect.";
    }
}

// -----------------------------------------------------
// LOGIQUE DE TÉLÉVERSEMENT (UPLOAD)
// -----------------------------------------------------
if ($estConnecte && isset($_FILES['fichier_upload'])) {
    $fichier = $_FILES['fichier_upload'];

    if ($fichier['error'] === UPLOAD_ERR_OK) {
        // Sécuriser le nom du fichier (supprimer les chemins potentiellement dangereux)
        $nomFichier = basename($fichier['name']);
        $cheminCible = $DOSSIER_STOCKAGE . $nomFichier;

        // Déplacer le fichier téléchargé vers le dossier de stockage
        if (move_uploaded_file($fichier['tmp_name'], $cheminCible)) {
            $messageSucces = "Le fichier **" . htmlspecialchars($nomFichier) . "** a été téléchargé avec succès.";
        } else {
            $messageErreur = "Erreur lors du déplacement du fichier sur le serveur. Vérifiez les permissions du dossier 'fichiers/'.";
        }
    } elseif ($fichier['error'] !== UPLOAD_ERR_NO_FILE) {
        $messageErreur = "Erreur d'upload (Code: " . $fichier['error'] . ")";
    }
}

// -----------------------------------------------------
// DÉBUT DE L'AFFICHAGE HTML
// -----------------------------------------------------
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Vrai Cloud PHP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-size: cover;
            transition: background-image 0.5s ease-in-out;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            max-width: 800px;
            width: 90%;
        }
        .message-success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .message-error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }

        <?php if ($estConnecte): ?>
        body {
            /* L'image doit être dans le même dossier et nommée maman_je_taime.jpg */
            background-image: url('maman_je_taime.jpg'); 
            background-position: center;
        }
        <?php endif; ?>
    </style>
</head>
<body>

    <?php if (!$estConnecte): ?>
        <div class="container" style="text-align: center; max-width: 400px;">
            <h2>🔒 Accès au Cloud Privé</h2>
            <p>Veuillez entrer le code d'accès pour continuer.</p>
            
            <?php if (isset($messageErreur)): ?>
                <div class="message-error"><?= $messageErreur ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <input type="password" name="code_saisi" placeholder="Code d'accès (<?= $CODE_ACCES_SECURITE ?>)" required 
                       style="padding: 10px; margin: 15px 0; width: 80%; border: 1px solid #ccc; border-radius: 5px;">
                <button type="submit" 
                        style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">Accéder</button>
            </form>
        </div>

    <?php else: ?>
        <div class="container">
            <h2>☁️ Mes Fichiers Personnels</h2>

            <?php if (isset($messageSucces)): ?>
                <div class="message-success"><?= $messageSucces ?></div>
            <?php endif; ?>
            <?php if (isset($messageErreur) && !empty($messageErreur)): ?>
                <div class="message-error"><?= $messageErreur ?></div>
            <?php endif; ?>

            <h3>📥 Téléverser un Fichier</h3>
            <form action="index.php" method="POST" enctype="multipart/form-data" 
                  style="border: 1px dashed #ccc; padding: 15px; margin-bottom: 20px;">
                <input type="file" name="fichier_upload" required>
                <button type="submit" 
                        style="margin-top: 10px; padding: 8px 15px; background-color: #28a745; color: white; border: none; border-radius: 5px;">Téléverser le Fichier</button>
            </form>

            <h3>📜 Liste des Fichiers Stockés</h3>
            <ul style="list-style: none; padding: 0;">
            <?php
            if (is_dir($DOSSIER_STOCKAGE)) {
                $fichiers = array_diff(scandir($DOSSIER_STOCKAGE), array('.', '..'));
                if (empty($fichiers)) {
                    echo "<li>Aucun fichier trouvé.</li>";
                }
                foreach ($fichiers as $fichier) {
                    $cheminComplet = $DOSSIER_STOCKAGE . $fichier;
                    $taille = round(filesize($cheminComplet) / 1024, 2); 
                    
                    $icone = '📄';
                    if (str_ends_with($fichier, '.mp3')) $icone = '🎵';
                    if (str_ends_with($fichier, '.jpg') || str_ends_with($fichier, '.png')) $icone = '🖼️';

                    echo "<li>$icone <a href=\"$cheminComplet\" download>". htmlspecialchars($fichier) ."</a> ($taille KB)</li>";
                }
            } else {
                 echo "<li class='message-error'>Erreur : Le dossier de stockage **" . $DOSSIER_STOCKAGE . "** est introuvable.</li>";
            }
            ?>
            </ul>
        </div>
        
    <?php endif; ?>

</body>
</html>
