<!-- resources/views/app.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Securely Share Passwords and Files | OTP Tool</title>

    <!-- Open Graph Tags -->
    <meta property="og:title" content="Securely Share Passwords and Files | OTP Tool">
    <meta property="og:description" content="Securely share passwords and files with end-to-end encryption. Content self-destructs after viewing. Your data is protected with AES-GCM-256 encryption.">
    <meta property="og:image" content="https://indulge.digital/sites/all/themes/indulgev4/images/logo-light.svg">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="OTP Tool">

    @vite(['resources/css/reset.css', 'resources/scss/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<nav class="main-nav" id="main-nav" style="display: none;">
    <div class="nav-container">
        <a href="/" class="nav-item" id="password-link">Password Sharing</a>
        <a href="/f" class="nav-item" id="file-link">File Sharing</a>
    </div>
</nav>

<div id="app"></div>

<footer>
    <p><a href="https://indulge.digital" target="_new">
        <img src="https://indulge.digital/sites/all/themes/indulgev4/images/logo-light.svg" alt="Indulge Media Ltd Logo" title="Indulge"/>
    </a></p>
    <p><strong>How it works:</strong> This app uses AES-GCM-256 based client-side encryption to securely share one-time passwords 
    @if($file_upload_allowed)
        and Files 
    @endif
    that self-destruct after use. Neither the unencrypted content nor the encryption key are ever sent to our servers. Only encrypted data is stored on our secure servers.</p>
    @if($whitelist_configured && $file_upload_allowed)
    <p><strong>Access Control:</strong> File upload functionality is restricted to authorized IP addresses. Your current IP has been granted access to upload files.</p>
    @endif
    <p><strong>About:</strong> <a href="https://indulge.digital/blog/i-built-password-sharing-tool-ai-and-here%E2%80%99s-what-i-learnt" target="_blank">Learn about why this tool exists</a>.</p>
    <p><strong>Disclaimer:</strong> While we strive to ensure security, we do not guarantee that content cannot be intercepted or misused. Use at your own risk.</p>
    <p><strong>Privacy:</strong> This site does not use cookies, analytics, or tracking tools beyond essential server logs (IP address, URL, timestamp). All content is encrypted before transmission and storage on our servers. The data is only used to provide the content to those you choose to share the link with, and for no other purpose. All stored content is deleted in full from our servers once it has been viewed, or once it has expired.</p>
</footer>

</body>
</html>