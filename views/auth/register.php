<h2>Create your account</h2>
<form method="post" action="/register" novalidate>
    <?= csrf_field() ?>

    <label>Name
        <input type="text" name="name" value="<?= e($old['name'] ?? '') ?>">
    </label>
    <?php if (isset($errors['name'])): ?><p class="error"><?= e($errors['name']) ?></p><?php endif; ?>

    <label>Email
        <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>">
    </label>
    <?php if (isset($errors['email'])): ?><p class="error"><?= e($errors['email']) ?></p><?php endif; ?>

    <label>Password
        <input type="password" name="password">
    </label>
    <?php if (isset($errors['password'])): ?><p class="error"><?= e($errors['password']) ?></p><?php endif; ?>

    <label>Confirm password
        <input type="password" name="password_confirmation">
    </label>

    <button type="submit">Register</button>
</form>
<p>Already have an account? <a href="/login">Log in</a></p>