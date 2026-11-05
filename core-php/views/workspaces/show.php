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

<h3>Overview</h3>
<div class="stats">
    <div class="stat"><strong><?= (int) $stats['total_projects'] ?></strong><span>Projects</span></div>
    <div class="stat"><strong><?= (int) $stats['total_tasks'] ?></strong><span>Tasks</span></div>
    <div class="stat"><strong><?= (int) $stats['completion'] ?>%</strong><span>Completed</span></div>
    <div class="stat"><strong class="<?= $stats['overdue'] ? 'overdue' : '' ?>"><?= (int) $stats['overdue'] ?></strong><span>Overdue</span></div>
    <div class="stat"><strong><?= (int) $stats['mine'] ?></strong><span>My open tasks</span></div>
</div>
<p class="muted">
    To do <?= (int) $stats['tasks']['todo'] ?> ·
    In progress <?= (int) $stats['tasks']['in_progress'] ?> ·
    Done <?= (int) $stats['tasks']['done'] ?>
</p>

<?php if ($stats['team']): ?>
    <h3>Team productivity</h3>
    <table>
        <tr>
            <th>Member</th>
            <th>Assigned</th>
            <th>Done</th>
        </tr>
        <?php foreach ($stats['team'] as $t): ?>
            <tr>
                <td><?= e($t['name']) ?></td>
                <td><?= (int) $t['assigned'] ?></td>
                <td><?= (int) $t['done'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h3>Recent activity</h3>
<?php if (!$activity): ?>
    <p class="muted">Nothing yet.</p>
<?php else: ?>
    <ul>
        <?php foreach ($activity as $a): ?>
            <li>
                <strong><?= e($a['user_name'] ?? 'Someone') ?></strong> <?= e($a['description']) ?>
                <span class="muted">· <?= e($a['created_at']) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

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
            <select name="role">
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