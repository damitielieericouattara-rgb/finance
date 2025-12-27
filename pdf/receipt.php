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
    
    // Générer code de sécurité unique (8 caractères)
    $securityCode = strtoupper(substr(md5($transaction['receipt_number'] . $transaction['id']), 0, 8));
    
} catch (Exception $e) {
    error_log("Erreur receipt.php: " . $e->getMessage());
    die('Erreur lors du chargement de la transaction');
}

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
            position: relative;
        }
        
        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: bold;
            color: rgba(5, 150, 105, 0.05);
            z-index: 0;
            pointer-events: none;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            z-index: 1;
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
        
        .print-warning {
            background: #fee2e2;
            border: 2px dashed #dc2626;
            color: #991b1b;
            padding: 15px;
            margin: 20px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
        }
        
        .receipt-body {
            padding: 40px;
            position: relative;
            z-index: 1;
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
            border: 3px solid #059669;
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
            font-size: 48px;
            font-weight: bold;
            color: #059669;
        }
        
        /* CODE DE SÉCURITÉ */
        .security-code-box {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }
        
        .security-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .security-code {
            font-size: 42px;
            font-weight: bold;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 8px;
            border: 2px dashed rgba(255,255,255,0.5);
        }
        
        .security-note {
            margin-top: 15px;
            font-size: 12px;
            opacity: 0.9;
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
        
        .withdrawal-instructions {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .withdrawal-instructions h3 {
            color: #92400e;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .withdrawal-instructions ul {
            list-style: none;
            padding: 0;
        }
        
        .withdrawal-instructions li {
            padding: 8px 0;
            color: #78350f;
            padding-left: 25px;
            position: relative;
        }
        
        .withdrawal-instructions li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #f59e0b;
            font-weight: bold;
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
        
        .security-badge {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin: 10px 5px;
        }
        
        .print-button {
            background: #059669;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin: 20px 0;
            box-shadow: 0 4px 8px rgba(5, 150, 105, 0.3);
        }
        
        .print-button:hover {
            background: #047857;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(5, 150, 105, 0.4);
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
            
            .print-button, .print-warning {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Watermark -->
        <div class="watermark">VALIDÉ</div>
        
        <!-- En-tête -->
        <div class="receipt-header">
            <h1>🧾 REÇU DE TRANSACTION</h1>
            <div class="receipt-number">
                N° <?php echo htmlspecialchars($transaction['receipt_number']); ?>
            </div>
        </div>
        
        <!-- AVERTISSEMENT IMPRESSION OBLIGATOIRE -->
        <div class="print-warning">
            ⚠️ CE REÇU DOIT ÊTRE IMPRIMÉ POUR RÉCUPÉRER LES FONDS À LA CAISSE ⚠️
        </div>
        
        <!-- Corps du reçu -->
        <div class="receipt-body">
            <!-- Bouton d'impression -->
            <div style="text-align: center;">
                <button onclick="window.print()" class="print-button">
                    🖨️ IMPRIMER LE REÇU MAINTENANT
                </button>
            </div>
            
            <!-- CODE DE SÉCURITÉ -->
            <div class="security-code-box">
                <div class="security-label">🔐 Code de Sécurité Unique</div>
                <div class="security-code"><?php echo $securityCode; ?></div>
                <div class="security-note">
                    Présentez ce code à la caissière avec votre pièce d'identité
                </div>
            </div>
            
            <!-- Informations principales -->
            <div class="info-section">
                <div class="info-row">
                    <div class="info-label">Date d'émission :</div>
                    <div class="info-value"><?php echo formatDateTime($transaction['validated_at'], 'd/m/Y à H:i'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Bénéficiaire :</div>
                    <div class="info-value"><strong><?php echo htmlspecialchars($transaction['user_name']); ?></strong></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Email :</div>
                    <div class="info-value"><?php echo htmlspecialchars($transaction['user_email']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Type de transaction :</div>
                    <div class="info-value">
                        <span style="display: inline-block; padding: 4px 12px; border-radius: 4px; font-weight: bold; <?php echo $transaction['type'] === 'entree' ? 'background: #dbeafe; color: #1e40af;' : 'background: #fee2e2; color: #991b1b;'; ?>">
                            <?php echo $transaction['type'] === 'entree' ? '📈 Entrée' : '📉 Sortie'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Validé par :</div>
                    <div class="info-value"><?php echo htmlspecialchars($transaction['validator_name']); ?></div>
                </div>
            </div>
            
            <!-- Montant en évidence -->
            <div class="amount-box">
                <div class="amount-label">💰 Montant à retirer</div>
                <div class="amount-value"><?php echo formatAmount($transaction['amount']); ?></div>
            </div>
            
            <!-- Motif -->
            <div class="description-box">
                <div class="description-title">📝 Motif de la transaction</div>
                <div class="description-text"><?php echo nl2br(htmlspecialchars($transaction['description'])); ?></div>
            </div>
            
            <!-- Instructions de retrait -->
            <div class="withdrawal-instructions">
                <h3>📋 Conditions de retrait</h3>
                <ul>
                    <li>Présentez ce reçu IMPRIMÉ à la caisse</li>
                    <li>Présentez une pièce d'identité valide</li>
                    <li>Communiquez le code de sécurité: <strong><?php echo $securityCode; ?></strong></li>
                    <li>Seul le bénéficiaire peut effectuer le retrait</li>
                    <li>Le retrait ne peut être effectué qu'une seule fois</li>
                </ul>
            </div>
            
            <!-- Badges de sécurité -->
            <div style="text-align: center; margin: 20px 0;">
                <span class="security-badge">✓ Transaction Validée</span>
                <span class="security-badge">🔒 Sécurisé</span>
                <span class="security-badge">📄 Document Officiel</span>
            </div>
        </div>
        
        <!-- Pied de page -->
        <div class="footer">
            <p><strong><?php echo SITE_NAME; ?></strong></p>
            <p>Document généré automatiquement le <?php echo date('d/m/Y à H:i:s'); ?></p>
            <p>Ce reçu certifie que la transaction a été validée et enregistrée dans le système.</p>
            <p style="margin-top: 15px; font-size: 12px; color: #9ca3af;">
                N° Transaction : #<?php echo $transaction['id']; ?> | 
                N° Reçu : <?php echo htmlspecialchars($transaction['receipt_number']); ?> |
                Code sécurité: <?php echo $securityCode; ?>
            </p>
            <p style="margin-top: 10px; font-size: 11px; color: #6b7280; font-style: italic;">
                Document confidentiel et nominatif - Ne peut être utilisé que par le bénéficiaire
            </p>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="background: #059669; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold;">
            🖨️ Imprimer ce reçu
        </button>
        <br><br>
        <a href="javascript:history.back()" style="color: #059669; text-decoration: none; font-size: 14px;">
            ← Retour à la liste des transactions
        </a>
    </div>
</body>
</html>