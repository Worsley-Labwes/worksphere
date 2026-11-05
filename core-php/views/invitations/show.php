<h2>You're invited</h2>
<p>
    <strong><?= e($invitation['inviter_name']) ?></strong> invited you to join
    <strong><?= e($invitation['workspace_name']) ?></strong> as <strong><?= e($invitation['role']) ?></strong>.
</p>

<form method="post" action="/invitations/<?= e($token) ?>/accept" style="display:inline">
    <?= csrf_field() ?>
    <button type="submit">Accept</button>
</form>
<form method="post" action="/invitations/<?= e($token) ?>/decline" style="display:inline">
    <?= csrf_field() ?>
    <button type="submit">Decline</button>
</form>