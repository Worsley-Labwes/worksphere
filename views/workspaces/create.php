<h2>New workspace</h2>
<form method="post" action="/workspaces" novalidate>
    <?= csrf_field() ?>
    <label>Workspace name
        <input type="text" name="name" value="<?= e($old['name'] ?? '') ?>">
    </label>
    <?php if (isset($errors['name'])): ?><p class="error"><?= e($errors['name']) ?></p><?php endif; ?>
    <button type="submit">Create</button>
</form>
<p><a href="/workspaces">Back</a></p>