<p><a href="/workspaces/<?= (int) $workspace['id'] ?>">← <?= e($workspace['name']) ?></a></p>
<h2>Projects</h2>

<?php if ($canManage): ?>
    <p><a href="/workspaces/<?= (int) $workspace['id'] ?>/projects/create">+ New project</a></p>
<?php endif; ?>

<?php if (!$projects): ?>
    <p>No projects yet.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Name</th>
            <th>Status</th>
            <th>Deadline</th>
            <th>Progress</th>
        </tr>
        <?php foreach ($projects as $p):
            $pct = $p['task_count'] ? (int) round($p['done_count'] / $p['task_count'] * 100) : 0; ?>
            <tr>
                <td><a href="/workspaces/<?= (int) $workspace['id'] ?>/projects/<?= (int) $p['id'] ?>"><?= e($p['name']) ?></a></td>
                <td><span class="badge"><?= e($p['status']) ?></span></td>
                <td><?= e($p['deadline'] ?? '—') ?></td>
                <td><progress max="100" value="<?= $pct ?>"></progress> <span class="muted"><?= (int) $p['done_count'] ?>/<?= (int) $p['task_count'] ?></span></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>