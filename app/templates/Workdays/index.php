<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Workday> $workdays
 */
?>
<div class="workdays index content">
    <?= $this->Html->link(__('New Workday'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Workdays') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('date') ?></th>
                    <th><?= $this->Paginator->sort('visits') ?></th>
                    <th><?= $this->Paginator->sort('completed') ?></th>
                    <th><?= $this->Paginator->sort('duration') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($workdays as $workday): ?>
                <tr>
                    <td><?= $this->Number->format($workday->id) ?></td>
                    <td><?= h($workday->date) ?></td>
                    <td><?= $this->Number->format($workday->visits) ?></td>
                    <td><?= $this->Number->format($workday->completed) ?></td>
                    <td><?= $this->Number->format($workday->duration) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $workday->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $workday->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $workday->id], ['confirm' => __('Are you sure you want to delete # {0}?', $workday->id)]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('first')) ?>
            <?= $this->Paginator->prev('< ' . __('previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('next') . ' >') ?>
            <?= $this->Paginator->last(__('last') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
