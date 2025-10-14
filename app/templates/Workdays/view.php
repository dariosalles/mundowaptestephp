<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Workday $workday
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Workday'), ['action' => 'edit', $workday->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Workday'), ['action' => 'delete', $workday->id], ['confirm' => __('Are you sure you want to delete # {0}?', $workday->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Workdays'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Workday'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="workdays view content">
            <h3><?= h($workday->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($workday->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Visits') ?></th>
                    <td><?= $this->Number->format($workday->visits) ?></td>
                </tr>
                <tr>
                    <th><?= __('Completed') ?></th>
                    <td><?= $this->Number->format($workday->completed) ?></td>
                </tr>
                <tr>
                    <th><?= __('Duration') ?></th>
                    <td><?= $this->Number->format($workday->duration) ?></td>
                </tr>
                <tr>
                    <th><?= __('Date') ?></th>
                    <td><?= h($workday->date) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
