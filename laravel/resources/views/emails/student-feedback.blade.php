<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Uma conversa com {{ $institutionName }}</title>
</head>
<body style="margin:0;background:#f5f7fb;color:#263247;font-family:Arial,sans-serif;line-height:1.65;padding:32px 16px">
    <main style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:32px">
        <p style="margin:0 0 24px;color:#2563eb;font-size:12px;font-weight:bold;letter-spacing:2px;text-transform:uppercase">PermaneceUni · apoio estudantil</p>
        <h1 style="margin:0 0 20px;color:#142342;font-size:22px">Uma conversa com a sua instituição</h1>
        <div style="white-space:pre-line">{{ $messageText }}</div>
        <p style="margin:28px 0 0;color:#64748b;font-size:12px">Esta mensagem foi enviada pela equipe de {{ $institutionName }}.</p>
    </main>
</body>
</html>
