<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>WorkSphere</title>
</head>

<body style="font-family:sans-serif;max-width:600px;margin:4rem auto">
    <h1>WorkSphere is running ✅</h1>
    <p>PHP <?= htmlspecialchars(PHP_VERSION) ?></p>
    <p>Database: <?= $dbOk ? '✅ connected' : '❌ ' . htmlspecialchars($error ?? 'failed') ?></p>
</body>

</html>