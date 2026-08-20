<!doctype html>
<html lang="<?= e(\App\Core\Lang::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(__('app.name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
    <!-- Favicon / App Icons -->
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('images/nousin-logo.svg')) ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= e(asset('images/favicon/favicon-16x16.png')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('images/favicon/favicon-32x32.png')) ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="<?= e(asset('images/favicon/favicon-48x48.png')) ?>">
    <link rel="shortcut icon" href="<?= e(url('favicon.ico')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset('images/favicon/favicon-180x180.png')) ?>">
    <link rel="manifest" href="<?= e(url('site.webmanifest')) ?>">
    <meta name="theme-color" content="#ffffff">
</head>
<body class="public-page">
    <?= $content ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
