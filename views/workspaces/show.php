<h2><?= e($workspace['name']) ?></h2>
<p>Your role: <strong><?= e($workspace['role']) ?></strong></p>
<p><a href="/workspaces/<?= (int) $workspace['id'] ?>/projects">View projects →</a></p>

<?php if ($inviteLink): ?>
    <p style="background:#e8f5e9;padding:.75rem;word-break:break-all">
        Invitation created. Send this link to the invitee:<br>
        <strong><?= e($inviteLink) ?></strong>
    </p>
<?php endif; ?>
<?php if ($inviteError): ?><p class="error"><?= e($inviteError) ?></p><?php endif; ?>

<h3>Members</h3>
<ul>
    <?php foreach ($members as $m): ?>
        <li><?= e($m['name']) ?> (<?= e($m['email']) ?>) — <?= e($m['role']) ?></li>
    <?php endforeach; ?>
</ul>

<?php if ($canInvite): ?>
    <h3>Invite someone</h3>
    <form method="post" action="/workspaces/<?= (int) $workspace['id'] ?>/invitations" novalidate>
        <?= csrf_field() ?>
        <label>Email
            <input type="email" name="email">
        </label>
        <label>Role
            <select name="role" style="width:100%;padding:.5rem;margin-top:.25rem">
                <option value="member">Member</option>
                <?php if ($workspace['role'] === 'owner'): ?>
                    <option value="admin">Admin</option>
                <?php endif; ?>
            </select>
        </label>
        <button type="submit">Create invitation</button>
    </form>

    <?php if ($pending): ?>
        <h3>Pending invitations</h3>
        <ul>
            <?php foreach ($pending as $p): ?>
                <li><?= e($p['email']) ?> as <?= e($p['role']) ?> (expires <?= e($p['expires_at']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

<p><a href="/workspaces">Back to workspaces</a></p>