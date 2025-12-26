<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

requireLogin();

$transactionId = intval($_GET['id'] ?? 0);

if ($transactionId <= 0) {
    die('ID de transaction invalide');
}

try {
    $transaction = getTransactionById($transactionId);
    
    if (!$transaction) {
        die('Transaction non trouvée');
    }
    
    if ($transaction['status'] !== 'validee') {
        die('Cette transaction n\'a pas encore été validée');
    }
    
    // Vérifier les permissions
    if (!isAdmin() && $transaction['user_id'] != $_SESSION['user_id']) {
        die('Accès non autorisé');
    }
    
} catch (Exception $e) {
    error_log("Erreur receipt.php: " . $e->getMessage());
    die('Erreur lors du chargement de la transaction');
}

// En-têtes pour PDF (affichage HTML pour l'instant)
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu - <?php echo $transaction['receipt_number']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .receipt-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .receipt-number {
            font-size: 18px;
            font-weight: bold;
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 10px;
        }
        
        .receipt-body {
            padding: 40px;
        }
        
        .info-section {
            margin-bottom: 30px;
        }
        
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-label {
            font-weight: bold;
            color: #6b7280;
            width: 200px;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #111827;
            flex: 1;
        }
        
        .amount-box {
            background: #f0fdf4;
            border: 2px solid #059669;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        
        .amount-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .amount-value {
            font-size: 42px;
            font-weight: bold;
            color: #059669;
        }
        
        .description-box {
            background: #f9fafb;
            border-left: 4px solid #059669;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .description-title {
            font-weight: bold;
            color: #374151;
            margin-bottom: 10px;
        }
        
        .description-text {
            color: #6b7280;
            line-height: 1.6;
        }
        
        .footer {
            background: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 2px solid #e5e7eb;
        }
        
        .footer p {
            color: #6b7280;
            font-size: 14px;
            margin: 5px 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .status-validee {
            background: #d1fae5;
            color: #065f46;
        }
        
        .type-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .type-entree {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .type-sortie {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .print-button {
            background: #059669;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 20px 0;
        }
        
        .print-button:hover {
            background: #047857;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt-container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- En-tête -->
        <div class="receipt-header">
            <h1>🧾 REÇU DE TRANSACTION</h1>
            <div class="receipt-number">
                N° <?php echo htmlspecialchars($transaction['receipt_number']); ?>
            </div>
        </div>
        
        <!-- Corps du reçu -->
        <div class="receipt-body">
            <!-- Bouton d'impression -->
            <div style="text-align: center;">
                <button onclick="window.print()" class="print-button">
                    🖨️ Imprimer le reçu
                </button>
            </div>
            
            <!-- Informations principales -->
            <div class="info-section">
                <div class="info-row">
                    <div class="info-label">Date d'émission :</div>
                    <div class="info-value"><?php echo formatDateTime($transaction['validated_at'], 'd/m/Y à H:i'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Date de création :</div>
                    <div class="info-value"><?php echo formatDateTime($transaction['created_at'], 'd/m/Y à H:i'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Utilisateur :</div>
                    <div class="info-value"><?php echo htmlspecialchars($transaction['user_name']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Email :</div>
                    <div class="info-value"><?php echo htmlspecialchars($transaction['user_email']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Type de transaction :</div>
                    <div class="info-value">
                        <span class="type-badge <?php echo $transaction['type'] === 'entree' ? 'type-entree' : 'type-sortie'; ?>">
                            <?php echo $transaction['type'] === 'entree' ? '📈 Entrée' : '📉 Sortie'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Statut :</div>
                    <div class="info-value">
                        <span class="status-badge status-validee">✅ Validée</span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Validé par :</div>
                    <div class="info-value"><?php echo htmlspecialchars($transaction['validator_name']); ?></div>
                </div>
                
                <?php if (!empty($transaction['required_date'])): ?>
                <div class="info-row">
                    <div class="info-label">Date requise :</div>
                    <div class="info-value"><?php echo formatDate($transaction['required_date']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Montant en évidence -->
            <div class="amount-box">
                <div class="amount-label">Montant de la transaction</div>
                <div class="amount-value"><?php echo formatAmount($transaction['amount']); ?></div>
            </div>
            
            <!-- Description -->
            <div class="description-box">
                <div class="description-title">📝 Description / Motif :</div>
                <div class="description-text"><?php echo nl2br(htmlspecialchars($transaction['description'])); ?></div>
            </div>
            
            <?php if (!empty($transaction['admin_comment'])): ?>
            <div class="description-box" style="border-left-color: #3b82f6;">
                <div class="description-title">💬 Commentaire administrateur :</div>
                <div class="description-text"><?php echo nl2br(htmlspecialchars($transaction['admin_comment'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Pied de page -->
        <div class="footer">
            <p><strong><?php echo SITE_NAME; ?></strong></p>
            <p>Document généré automatiquement le <?php echo date('d/m/Y à H:i:s'); ?></p>
            <p>Ce reçu certifie que la transaction a été validée et enregistrée dans le système.</p>
            <p style="margin-top: 15px; font-size: 12px; color: #9ca3af;">
                N° Transaction : #<?php echo $transaction['id']; ?> | 
                N° Reçu : <?php echo htmlspecialchars($transaction['receipt_number']); ?>
            </p>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="javascript:history.back()" style="color: #059669; text-decoration: none; font-size: 14px;">
            ← Retour à la liste des transactions
        </a>
    </div>
    <script src="../assets/js/theme.js"></script>
</body>
</html>