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
            <?= $this->Html->link(__('List Workdays'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="workdays form content">
            <?= $this->Form->create($workday) ?>
            <fieldset>
                <legend><?= __('Add Workday') ?></legend>
                <?php
                    echo $this->Form->control('date');
                    echo $this->Form->control('visits');
                    echo $this->Form->control('completed');
                    echo $this->Form->control('duration');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
