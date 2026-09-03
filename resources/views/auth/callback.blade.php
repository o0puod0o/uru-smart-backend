<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>URU Smart SSO Callback</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f8fafc;
            color: #0f172a;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
        }
        .card {
            max-width: 560px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        h1 {
            font-size: 24px;
            margin: 0 0 12px;
        }
        p {
            margin: 0;
            color: #475569;
            line-height: 1.6;
        }
        .ok { color: #047857; }
        .error { color: #b91c1c; }
        pre {
            display: none;
            margin-top: 16px;
            text-align: left;
            white-space: pre-wrap;
            word-break: break-word;
            background: #f1f5f9;
            border-radius: 12px;
            padding: 16px;
        }
    </style>
</head>
<body>
@php
    $payload = $payload ?? [
        'type' => isset($token) ? 'SSO_SUCCESS' : 'SSO_ERROR',
        'token' => $token ?? null,
        'user' => $user ?? null,
        'message' => $message ?? null,
    ];

    $isSuccess = ! empty($payload['token']);
@endphp

<div class="card">
    <h1 class="{{ $isSuccess ? 'ok' : 'error' }}">
        {{ $isSuccess ? 'เข้าสู่ระบบสำเร็จ' : 'เข้าสู่ระบบไม่สำเร็จ' }}
    </h1>
    <p id="status-text">
        {{ $isSuccess
            ? 'กำลังส่งข้อมูลกลับไปยังแอป กรุณารอสักครู่...'
            : ($payload['message'] ?? 'ไม่สามารถยืนยันตัวตนผ่าน SSO ได้ กรุณาลองใหม่อีกครั้ง') }}
    </p>
    <pre id="payload">@json($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)</pre>
</div>

<script>
(function () {
    var payload = @json($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    var message = JSON.stringify(payload);
    var attempts = 0;
    var maxAttempts = 30;
    var statusText = document.getElementById('status-text');

    window.URUSmartAuth = payload;

    function postAuthPayload() {
        attempts += 1;

        try {
            if (window.ReactNativeWebView && typeof window.ReactNativeWebView.postMessage === 'function') {
                window.ReactNativeWebView.postMessage(message);

                if (statusText && payload.token) {
                    statusText.innerHTML = 'ส่ง token กลับไปยังแอปแล้ว หากหน้านี้ยังไม่ปิด กรุณารอสักครู่...';
                }
            }
        } catch (error) {
            // Keep retrying. Android WebView bridge can be ready a little late.
        }

        try {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage(payload, '*');
            }
        } catch (error) {
            // Ignore parent postMessage errors.
        }

        if (attempts < maxAttempts) {
            window.setTimeout(postAuthPayload, 500);
        }
    }

    postAuthPayload();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', postAuthPayload);
    }

    window.addEventListener('load', postAuthPayload);
    window.setTimeout(postAuthPayload, 100);
})();
</script>
</body>
</html>
