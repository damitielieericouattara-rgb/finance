<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

$transactionId = intval($_GET['id'] ?? 0);
$transaction = getTransactionById($transactionId);

if (!$transaction || $transaction['status'] !== 'validee') {
    die('Transaction non trouvée ou non validée');
}

// Vérifier les permissions
if (!isAdmin() && $transaction['user_id'] != $_SESSION['user_id']) {
    die('Accès non autorisé');
}

// Ici, utiliser TCPDF ou similaire pour générer le PDF
// Pour l'instant, version HTML simple

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="recu_' . $transaction['receipt_number'] . '.pdf"');

// Version simplifiée HTML (à remplacer par TCPDF)
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .content { margin: 20px 0; }
        .footer { margin-top: 50px; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REÇU DE TRANSACTION</h1>
        <p>N° <?php echo $transaction['receipt_number']; ?></p>
    </div>
    
    <div class="content">
        <p><strong>Date:</strong> <?php echo formatDateTime($transaction['validated_at']); ?></p>
        <p><strong>Utilisateur:</strong> <?php echo htmlspecialchars($transaction['user_name']); ?></p>
        <p><strong>Type:</strong> <?php echo getTypeLabel($transaction['type']); ?></p>
        <p><strong>Montant:</strong> <?php echo formatAmount($transaction['amount']); ?></p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($transaction['description']); ?></p>
        <p><strong>Validé par:</strong> <?php echo htmlspecialchars($transaction['validator_name']); ?></p>
    </div>
    
    <div class="footer">
        <p>Document généré automatiquement par <?php echo SITE_NAME; ?></p>
    </div>
</body>
</html>