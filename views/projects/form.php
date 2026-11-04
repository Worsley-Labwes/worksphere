<?php
$editing = $project !== null;
$val = fn(string $k) => $old[$k] ?? ($project[$k] ?? '');
$wid = (int) $workspace['id'];
$action = "/workspaces/$wid/projects" . ($editing ? '/' . (int) $project['id'] . '/update' : '');
?>
<h2><?= $editing ? 'Edit project' : 'New project' ?></h2>
<form method="post" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>

    <label>Name
        <input type="text" name="name" value="<?= e($val('name')) ?>">
    </label>
    <?php if (isset($errors['name'])): ?><p class="error"><?= e($errors['name']) ?></p><?php endif; ?>

    <label>Description
        <textarea name="description" rows="4"><?= e($val('description')) ?></textarea>
    </label>

    <label>Deadline
        <input type="date" name="deadline" value="<?= e($val('deadline')) ?>">
    </label>
    <?php if (isset($errors['deadline'])): ?><p class="error"><?= e($errors['deadline']) ?></p><?php endif; ?>

    <?php if ($editing): ?>
        <label>Status
            <select name="status">
                <?php foreach (['planning', 'active', 'completed', 'archived'] as $s): ?>
                    <option value="<?= $s ?>" <?= $val('status') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>

    <button type="submit"><?= $editing ? 'Save changes' : 'Create project' ?></button>
</form>
<p><a href="/workspaces/<?= $wid ?>/projects">Cancel</a></p>