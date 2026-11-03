<h2>Log in</h2>
<form method="post" action="/login" novalidate>
    <?= csrf_field() ?>

    <label>Email
        <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>">
    </label>
    <?php if (isset($errors['email'])): ?><p class="error"><?= e($errors['email']) ?></p><?php endif; ?>

    <label>Password
        <input type="password" name="password">
    </label>

    <button type="submit">Log in</button>
</form>
<p>No account yet? <a href="/register">Register</a></p>