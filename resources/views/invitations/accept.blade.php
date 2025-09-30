<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation</title>
    <link rel="stylesheet" href="/build/assets/app.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', Arial, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; padding: 2rem; }
        .card { max-width: 640px; margin: 0 auto; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; }
        h1 { font-size: 1.25rem; margin-bottom: 0.5rem; }
        p { color: #374151; }
        .actions { margin-top: 1rem; }
        .btn { display: inline-block; background: #111827; color: #fff; padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none; }
        .muted { color: #6b7280; font-size: 0.875rem; }
        .error { color: #b91c1c; }
        code { background: #f3f4f6; padding: 0.125rem 0.25rem; border-radius: 0.25rem; }
    </style>
    <script>
        async function acceptInvite() {
            const params = new URLSearchParams(window.location.search);
            const token = params.get('token');
            if (!token) return alert('Missing token');
            const email = (document.getElementById('email') || {}).value || '';
            try {
                const qs = new URLSearchParams({ token });
                if (email) qs.set('email', email);
                const res = await fetch('/api/orgs/invitations/accept?' + qs.toString(), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    credentials: 'include'
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    document.getElementById('result').innerText = 'Invitation accepted. You have joined the organization.';
                } else {
                    document.getElementById('result').innerText = (data.error && data.error.message) || 'Failed to accept invitation.';
                }
            } catch (e) {
                document.getElementById('result').innerText = 'Network error.';
            }
        }
    </script>
    
</head>
<body>
    <div class="card">
        <h1>Organization Invitation</h1>
        @if(($status ?? '') === 'error')
            <p class="error">{{ $message }}</p>
        @else
            <p>Enter the invited email (if not logged in) and click Accept.</p>
            <div style="margin: 0.5rem 0;">
                <label for="email" class="muted">Email</label><br>
                <input id="email" type="email" placeholder="your@email.com" style="padding: 0.4rem; width: 100%; max-width: 360px;" />
            </div>
            <div class="actions">
                <button class="btn" onclick="acceptInvite()">Accept Invitation</button>
            </div>
            <p id="result" class="muted" style="margin-top: 0.75rem;"></p>
            <p class="muted">Token: <code>{{ $token }}</code></p>
        @endif
    </div>
</body>
</html>

