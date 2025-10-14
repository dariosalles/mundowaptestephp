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
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $visit->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $visit->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Visits'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="visits form content">
            <?= $this->Form->create($visit) ?>
            <fieldset>
                <legend><?= __('Edit Visit') ?></legend>
                <?php
                    echo $this->Form->control('date');
                    echo $this->Form->control('completed');
                    echo $this->Form->control('forms');
                    echo $this->Form->control('products');
                    echo $this->Form->control('duration');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
