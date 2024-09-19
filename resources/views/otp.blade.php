<!-- resources/views/otp.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Securely Share a Password | Indulge</title>
    @vite(['resources/css/reset.css', 'resources/scss/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div id="app"></div>

<footer>

    <a href="https://indulge.digital" target="_new">
        <img src="https://indulge.digital/sites/all/themes/indulgev4/images/logo-light.svg" alt="Indulge Media Ltd Logo" title="Indulge"/>
    </a>
    <p><strong>How it works:</strong> This app uses AES-GCM-256 based client-side encryption to securely generate and share one-time passwords that self-destruct after use. Neither the unencrypted password or the encryption key are ever sent to our servers. The encrypted password is the only piece of information stored on our servers.</p>
    <p><strong>Disclaimer:</strong> While we strive to ensure security, we do not guarantee that passwords cannot be intercepted or misused. Use at your own risk.</p>

</footer>

</body>
</html>
