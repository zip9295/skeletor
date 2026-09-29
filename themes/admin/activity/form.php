<?php if (!isset($data['activityData']) || !$data['activityData']): ?>
    <p>No activity data available.</p>
    <?php return; endif; ?>

<?php
$activity = $data['activityData'];
$action = $activity['action'];
$actionColors = ['create' => '#22c55e', 'update' => '#f59e0b', 'delete' => '#ef4444', 'restore' => '#3b82f6'];
$actionColor = $actionColors[$action] ?? '#6b7280';
?>

<div class="activityDetail">
    <div class="activityDetailHeader">
        <h1><?= $data['formTitle'] ?></h1>
    </div>

    <div class="activityDetailMeta">
        <div class="activityMetaItem">
            <span class="activityMetaLabel"><?=$this->t('Action')?></span>
            <span class="activityBadge" style="background: <?= $actionColor ?>; color: #fff; padding: 2px 10px; border-radius: 4px;">
                <?= ucfirst($action) ?>
            </span>
        </div>
        <div class="activityMetaItem">
            <span class="activityMetaLabel"><?=$this->t('Entity')?></span>
            <span><?= $this->e($activity['entityType']) ?> #<?= $activity['entityId'] ?></span>
        </div>
        <div class="activityMetaItem">
            <span class="activityMetaLabel"><?=$this->t('User')?></span>
            <span><?= $this->e($activity['user']) ?></span>
        </div>
        <div class="activityMetaItem">
            <span class="activityMetaLabel"><?=$this->t('Date')?></span>
            <span><?= $activity['createdAt'] ?></span>
        </div>
        <?php if ($activity['ipAddress']): ?>
        <div class="activityMetaItem">
            <span class="activityMetaLabel"><?=$this->t('IP Address')?></span>
            <span><?= $this->e($activity['ipAddress']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <?php if (in_array($action, ['update', 'restore']) && $activity['diff']): ?>
        <h4><?=$this->t('Changes')?></h4>
        <table class="activityDiffTable">
            <thead>
                <tr>
                    <th><?=$this->t('Field')?></th>
                    <th><?=$this->t('Old Value')?></th>
                    <th><?=$this->t('New Value')?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activity['diff'] as $field => $change): ?>
                <tr>
                    <td><strong><?= $this->e($field) ?></strong></td>
                    <td class="activityOldValue"><?= $this->e(is_array($change['old']) ? json_encode($change['old']) : (string)($change['old'] ?? '—')) ?></td>
                    <td class="activityNewValue"><?= $this->e(is_array($change['new']) ? json_encode($change['new']) : (string)($change['new'] ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($action === 'create' && $activity['newData']): ?>
        <h4><?=$this->t('Created Data')?></h4>
        <table class="activityDiffTable">
            <thead>
                <tr>
                    <th><?=$this->t('Field')?></th>
                    <th><?=$this->t('Value')?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activity['newData'] as $field => $value): ?>
                <tr>
                    <td><strong><?= $this->e($field) ?></strong></td>
                    <td><?= $this->e(is_array($value) ? json_encode($value) : (string)($value ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($action === 'delete' && $activity['oldData']): ?>
        <h4><?=$this->t('Deleted Data')?></h4>
        <table class="activityDiffTable">
            <thead>
                <tr>
                    <th><?=$this->t('Field')?></th>
                    <th><?=$this->t('Value')?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activity['oldData'] as $field => $value): ?>
                <tr>
                    <td><strong><?= $this->e($field) ?></strong></td>
                    <td><?= $this->e(is_array($value) ? json_encode($value) : (string)($value ?? '—')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
    .activityDetailHeader {
        margin-bottom:1rem;
    }
    .activityDetail { padding: 0 10px; }
    .activityDetailMeta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 20px;
        border-radius: 8px;
        color: var(--colorSurface-600);
    }
    .activityBadge {
        width:max-content;
    }
    .activityMetaItem { display: flex; flex-direction: column; gap: 4px; }
    .activityMetaLabel { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.6; }
    .activityDiffTable {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 0.875rem;
        color: var(--colorSurface-600);
    }
    .activityDiffTable th,
    .activityDiffTable td {
        padding: 8px 12px;
        border-bottom: 1px solid var(--colorSurface-600);
        text-align: left;
        word-break: break-word;
    }

    .activityDiffTable th:first-of-type {
        width:100px;
    }
    .activityDiffTable th {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        opacity: 0.6;
    }
    .activityOldValue { color: #ef4444; }
    .activityNewValue { color: #22c55e; }
</style>
