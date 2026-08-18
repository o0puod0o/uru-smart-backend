<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>URU Smart SSO Callback</title>
</head>
<body>
<script>
    window.URUSmartAuth = @json(['token' => $token, 'user' => $user]);
    window.ReactNativeWebView?.postMessage(JSON.stringify(window.URUSmartAuth));
</script>
<pre id="payload">@json(['token' => $token, 'user' => $user], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
</body>
</html>
