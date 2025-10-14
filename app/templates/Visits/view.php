<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Visit $visit
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Visit'), ['action' => 'edit', $visit->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Visit'), ['action' => 'delete', $visit->id], ['confirm' => __('Are you sure you want to delete # {0}?', $visit->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Visits'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Visit'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="visits view content">
            <h3><?= h($visit->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($visit->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Completed') ?></th>
                    <td><?= $this->Number->format($visit->completed) ?></td>
                </tr>
                <tr>
                    <th><?= __('Forms') ?></th>
                    <td><?= $this->Number->format($visit->forms) ?></td>
                </tr>
                <tr>
                    <th><?= __('Products') ?></th>
                    <td><?= $this->Number->format($visit->products) ?></td>
                </tr>
                <tr>
                    <th><?= __('Duration') ?></th>
                    <td><?= $this->Number->format($visit->duration) ?></td>
                </tr>
                <tr>
                    <th><?= __('Date') ?></th>
                    <td><?= h($visit->date) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
