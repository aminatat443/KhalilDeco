<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Google — Khalil Déco</title>
</head>
<body style="margin:0; display:flex; align-items:center; justify-content:center; height:100vh; font-family: sans-serif; background:#fff; color:#252525;">
    <p>Connexion en cours…</p>
    <script>
        (function () {
            var payload = {
                khalilGoogleAuth: true,
                success: {{ $success ? 'true' : 'false' }},
                redirect: {!! json_encode($redirect) !!},
                message: {!! json_encode($message) !!}
            };
            if (window.opener) {
                window.opener.postMessage(payload, window.location.origin);
                window.close();
            } else {
                window.location.href = payload.redirect;
            }
        })();
    </script>
</body>
</html>
