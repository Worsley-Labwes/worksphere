<?php

function e(?string $v): string
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $to): never
{
    header("Location: $to");
    exit;
}

function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    exit($message ?: ($code === 404 ? '404 Not Found' : 'Error'));
}

function view(string $name, array $data = []): void
{
    extract($data);
    ob_start();
    require __DIR__ . "/../views/$name.php";
    $content = ob_get_clean();
    require __DIR__ . '/../views/layout.php';
}

// One-time session values (validation errors, old input)
function pull(string $key, mixed $default = []): mixed
{
    $value = $_SESSION[$key] ?? $default;
    unset($_SESSION[$key]);
    return $value;
}

function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) {
        http_response_code(419);
        exit('Page expired. Go back, refresh and try again.');
    }
}

function intended_url(string $default = '/dashboard'): string
{
    $url = pull('intended', null);

    if (is_string($url) && str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, '\\')) {
        return $url;
    }
    return $default;
}
