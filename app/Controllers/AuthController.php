<?php

namespace App\Controllers;

use App\Models\User;

class AuthController
{
    public function showRegister(): void
    {
        view('auth/register', ['errors' => pull('errors'), 'old' => pull('old')]);
    }

    public function register(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirmation'] ?? '';

        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) {
            $errors['name'] = 'Name is required (max 100 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (User::findByEmail($email)) {
            $errors['email'] = 'That email is already registered.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $errors['password'] = 'Passwords do not match.';
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['name' => $name, 'email' => $email];
            redirect('/register');
        }

        $id = User::create($name, $email, $password);
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $id, 'name' => $name, 'email' => $email];
        redirect(intended_url());
    }

    public function showLogin(): void
    {
        view('auth/login', ['errors' => pull('errors'), 'old' => pull('old')]);
    }

    public function login(): void
    {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['errors'] = ['email' => 'Invalid email or password.'];
            $_SESSION['old'] = ['email' => $email];
            redirect('/login');
        }

        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
        redirect(intended_url());
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        redirect('/login');
    }
}
