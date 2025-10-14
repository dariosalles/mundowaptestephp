<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Visit> $visits
 */
?>
<div class="visits index content">
    <?= $this->Html->link(__('New Visit'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Visits') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('id') ?></th>
                    <th><?= $this->Paginator->sort('date') ?></th>
                    <th><?= $this->Paginator->sort('completed') ?></th>
                    <th><?= $this->Paginator->sort('forms') ?></th>
                    <th><?= $this->Paginator->sort('products') ?></th>
                    <th><?= $this->Paginator->sort('duration') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visits as $visit): ?>
                <tr>
                    <td><?= $this->Number->format($visit->id) ?></td>
                    <td><?= h($visit->date) ?></td>
                    <td><?= $this->Number->format($visit->completed) ?></td>
                    <td><?= $this->Number->format($visit->forms) ?></td>
                    <td><?= $this->Number->format($visit->products) ?></td>
                    <td><?= $this->Number->format($visit->duration) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('View'), ['action' => 'view', $visit->id]) ?>
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $visit->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $visit->id], ['confirm' => __('Are you sure you want to delete # {0}?', $visit->id)]) ?>
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
