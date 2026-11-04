<?php
$labels = ['todo' => 'To do', 'in_progress' => 'In progress', 'done' => 'Done'];
$wid = (int) $workspace['id'];
$base = "/workspaces/$wid";
$me = auth_user()['id'];
$today = date('Y-m-d');
$total = count($tasks);
$doneCount = count(array_filter($tasks, fn($t) => $t['status'] === 'done'));
$pct = $total ? (int) round($doneCount / $total * 100) : 0;
?>
<p><a href="<?= $base ?>/projects">← Projects</a></p>

<h2><?= e($project['name']) ?> <span class="badge"><?= e($project['status']) ?></span></h2>
<?php if ($project['description']): ?><p><?= nl2br(e($project['description'])) ?></p><?php endif; ?>
<p class="muted">
    Deadline: <?= e($project['deadline'] ?? 'not set') ?>
    <?php if ($canManage): ?> · <a href="<?= $base ?>/projects/<?= (int) $project['id'] ?>/edit">Edit project</a><?php endif; ?>
</p>
<progress max="100" value="<?= $pct ?>"></progress>
<p class="muted"><?= $doneCount ?> of <?= $total ?> tasks done (<?= $pct ?>%)</p>

<h3>Add a task</h3>
<form method="post" action="<?= $base ?>/projects/<?= (int) $project['id'] ?>/tasks" novalidate>
    <?= csrf_field() ?>

    <label>Title
        <input type="text" name="title" value="<?= e($taskOld['title'] ?? '') ?>">
    </label>
    <?php if (isset($taskErrors['title'])): ?><p class="error"><?= e($taskErrors['title']) ?></p><?php endif; ?>

    <label>Description
        <textarea name="description" rows="2"><?= e($taskOld['description'] ?? '') ?></textarea>
    </label>

    <label>Priority
        <select name="priority">
            <?php foreach (['low', 'medium', 'high'] as $p): ?>
                <option value="<?= $p ?>" <?= ($taskOld['priority'] ?? 'medium') === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Assign to
        <select name="assigned_to">
            <option value="">Unassigned</option>
            <?php foreach ($members as $m): ?>
                <option value="<?= (int) $m['id'] ?>" <?= (string) ($taskOld['assigned_to'] ?? '') === (string) $m['id'] ? 'selected' : '' ?>>
                    <?= e($m['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php if (isset($taskErrors['assigned_to'])): ?><p class="error"><?= e($taskErrors['assigned_to']) ?></p><?php endif; ?>

    <label>Due date
        <input type="date" name="due_date" value="<?= e($taskOld['due_date'] ?? '') ?>">
    </label>
    <?php if (isset($taskErrors['due_date'])): ?><p class="error"><?= e($taskErrors['due_date']) ?></p><?php endif; ?>

    <button type="submit">Add task</button>
</form>

<h3>Board</h3>
<div class="board">
    <?php foreach ($labels as $status => $label): ?>
        <div class="column">
            <h3><?= $label ?></h3>

            <?php foreach ($tasks as $t):
                if ($t['status'] !== $status) continue;
                $canMove = $canManage || (int) $t['assigned_to'] === $me || (int) $t['created_by'] === $me;
                $canDelete = $canManage || (int) $t['created_by'] === $me;
                $overdue = $t['due_date'] && $t['due_date'] < $today && $t['status'] !== 'done';
            ?>
                <div class="card">
                    <strong><?= e($t['title']) ?></strong>
                    <span class="badge <?= e($t['priority']) ?>"><?= e($t['priority']) ?></span>
                    <?php if ($t['description']): ?><div class="muted"><?= nl2br(e($t['description'])) ?></div><?php endif; ?>
                    <div class="muted">
                        <?= $t['assignee_name'] ? 'Assigned to ' . e($t['assignee_name']) : 'Unassigned' ?>
                        <?php if ($t['due_date']): ?>
                            · <span class="<?= $overdue ? 'overdue' : '' ?>">Due <?= e($t['due_date']) ?><?= $overdue ? ' (overdue)' : '' ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($canMove): foreach ($labels as $s => $l): if ($s === $status) continue; ?>
                            <form method="post" action="<?= $base ?>/tasks/<?= (int) $t['id'] ?>/status" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="status" value="<?= e($s) ?>">
                                <button type="submit" class="small">→ <?= e($l) ?></button>
                            </form>
                    <?php endforeach;
                    endif; ?>

                    <?php if ($canDelete): ?>
                        <form method="post" action="<?= $base ?>/tasks/<?= (int) $t['id'] ?>/delete" class="inline"
                            onsubmit="return confirm('Delete this task?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="small">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>