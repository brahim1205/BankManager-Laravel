<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue chez Banque Example</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .credentials { background-color: white; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .warning { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bienvenue chez Banque Example</h1>
            <p>Votre compte a été créé avec succès</p>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $client->nom_complet }}</strong>,</p>

            <p>Félicitations ! Votre compte bancaire a été créé avec succès. Voici vos identifiants de connexion :</p>

            <div class="credentials">
                <h3>Vos identifiants :</h3>
                <p><strong>Email :</strong> {{ $client->email }}</p>
                <p><strong>Mot de passe temporaire :</strong> {{ $client->password }}</p>
                <p><strong>Numéro de compte :</strong> {{ $compte->numero }}</p>
            </div>

            <div class="warning">
                <p>⚠️ <strong>Important :</strong></p>
                <ul>
                    <li>Ce mot de passe est temporaire et doit être changé lors de votre première connexion.</li>
                    <li>Un code de vérification vous a été envoyé par SMS pour finaliser votre inscription.</li>
                    <li>Conservez ces informations en lieu sûr.</li>
                </ul>
            </div>

            <p>Pour accéder à votre espace client, rendez-vous sur <a href="{{ config('app.url') }}/login">notre plateforme</a>.</p>

            <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>

            <p>Cordialement,<br>
            L'équipe Banque Example</p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} Banque Example. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>