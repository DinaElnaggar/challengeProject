<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verified</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: #0f172a; color: #e2e8f0; }
        .wrap { min-height: 100%; display: grid; place-items: center; padding: 24px; }
        .card { max-width: 560px; width: 100%; background: #111827; border: 1px solid #1f2937; border-radius: 12px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,.35); }
        .title { font-size: 22px; font-weight: 600; margin: 0 0 8px; color: #a7f3d0; }
        .msg { margin: 0 0 16px; color: #cbd5e1; }
        .hint { font-size: 14px; color: #94a3b8; }
        .btn { display: inline-block; margin-top: 8px; background: #10b981; color: #052e2b; text-decoration: none; padding: 10px 14px; border-radius: 8px; font-weight: 600; }
    </style>
    <script>
        // If this page was opened from a SPA, try to notify it (optional, harmless otherwise)
        try { window.opener && window.opener.postMessage({ type: 'email-verified' }, '*'); } catch (e) {}
    </script>
    </head>
<body>
<div class="wrap">
    <div class="card">
        <h1 class="title">Email Verified</h1>
        <p class="msg">Your email has been verified successfully.</p>
        <p class="hint">You can now return to the application and log in.</p>
        <a class="btn" href="/">Go to Home</a>
    </div>
  </div>
</body>
</html>

