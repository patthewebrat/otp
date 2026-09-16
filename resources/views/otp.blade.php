<!-- resources/views/otp.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        {{ request()->is('v') ? 'View Password' : 'Securely Share a Password | OTP Tool' }}
    </title>

    <!-- Open Graph Tags -->
    <meta property="og:title" content="{{ request()->is('v') ? 'View Password' : 'Securely Share a Password | OTP Tool' }}">
    <meta property="og:description" content="{{ request()->is('v') ? 'A password has been securely shared with you. View it now before it self-destructs. Your data is protected with AES-GCM-256 encryption.' : 'Securely generate and share one-time passwords that self-destruct after use. Your data stays private with AES-GCM-256 encryption.' }}">
    <meta property="og:image" content="{{ url('/images/brand/apple-touch-icon.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="OTP Tool">

    <link rel="icon" type="image/svg+xml" href="/images/brand/favicon.svg">
    <link rel="icon" type="image/png" sizes="96x96" href="/images/brand/favicon-96x96.png">
    <link rel="shortcut icon" href="/images/brand/favicon.ico">
    <link rel="apple-touch-icon" href="/images/brand/apple-touch-icon.png">

    @vite(['resources/css/reset.css', 'resources/scss/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" integrity="sha512-iBBXm8fW90+nuLcSKlbmrPcLa0OT92xO1BIsZ+ywDWZCvqsWgccV3gFoRBv0z+8dLJgyAHIhR35VZc2oM/gI1w==" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<div id="app"></div>

<footer>

    <p><strong>How it works:</strong> This app uses AES-GCM-256 based client-side encryption to securely generate and share one-time passwords that self-destruct after use. Neither the unencrypted password or the encryption key are ever sent to our servers. The encrypted password is the only piece of information stored on our servers.</p>
    <p><strong>About:</strong> <a href="https://indulge.digital/blog/i-built-password-sharing-tool-ai-and-here%E2%80%99s-what-i-learnt" target="_blank">Learn about why this tool exists</a>.</p>
    <p><strong>Source:</strong> This tool is open source. <a href="https://github.com/patthewebrat/otp" target="_blank" rel="noopener">View the code on GitHub</a>.</p>
    <p><strong>Disclaimer:</strong> While we strive to ensure security, we do not guarantee that passwords cannot be intercepted or misused. Use at your own risk.</p>
    <p><strong>Privacy:</strong> This site does not use cookies, analytics, or tracking tools beyond essential server logs (IP address, URL, timestamp). Password data entered into the form is encrypted before transmission and storage on our servers. The data is only used to display the password to those you choose to share the link with, and for no other purpose. All stored passwords are deleted in full from our servers once they have been viewed, or once they have expired.</p>
    <p class="copyright">&copy; {{ date('Y') }} <a href="https://indulge.digital" target="_new">Indulge Media Ltd</a></p>
</footer>

</body>
</html>
