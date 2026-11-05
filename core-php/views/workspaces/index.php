<h2>Your workspaces</h2>

<?php if ($invitations): ?>
    <div style="background:#fff8e1;padding:.75rem;margin-bottom:1rem">
        <strong>You have invitations:</strong>
        <ul>
            <?php foreach ($invitations as $i): ?>
                <li>
                    <a href="/invitations/<?= e($i['token']) ?>">
                        Join <?= e($i['workspace_name']) ?> as <?= e($i['role']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$workspaces): ?>
    <p>You don't belong to any workspace yet.</p>
<?php else: ?>
    <ul>
        <?php foreach ($workspaces as $w): ?>
            <li><a href="/workspaces/<?= (int) $w['id'] ?>"><?= e($w['name']) ?></a> (<?= e($w['role']) ?>)</li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<p><a href="/workspaces/create">+ New workspace</a></p>