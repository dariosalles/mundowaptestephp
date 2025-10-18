<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Address $address
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Html->link(__('Edit Address'), ['action' => 'edit', $address->id], ['class' => 'side-nav-item']) ?>
            <?= $this->Form->postLink(__('Delete Address'), ['action' => 'delete', $address->id], ['confirm' => __('Are you sure you want to delete # {0}?', $address->id), 'class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('List Addresses'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
            <?= $this->Html->link(__('New Address'), ['action' => 'add'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="addresses view content">
            <h3><?= h($address->id) ?></h3>
            <table>
                <tr>
                    <th><?= __('Foreign Table') ?></th>
                    <td><?= h($address->foreign_table) ?></td>
                </tr>
                <tr>
                    <th><?= __('Postal Code') ?></th>
                    <td><?= h($address->postal_code) ?></td>
                </tr>
                <tr>
                    <th><?= __('State') ?></th>
                    <td><?= h($address->state) ?></td>
                </tr>
                <tr>
                    <th><?= __('City') ?></th>
                    <td><?= h($address->city) ?></td>
                </tr>
                <tr>
                    <th><?= __('Sublocality') ?></th>
                    <td><?= h($address->sublocality) ?></td>
                </tr>
                <tr>
                    <th><?= __('Street') ?></th>
                    <td><?= h($address->street) ?></td>
                </tr>
                <tr>
                    <th><?= __('Street Number') ?></th>
                    <td><?= h($address->street_number) ?></td>
                </tr>
                <tr>
                    <th><?= __('Complement') ?></th>
                    <td><?= h($address->complement) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($address->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Foreign Id') ?></th>
                    <td><?= $this->Number->format($address->foreign_id) ?></td>
                </tr>
            </table>
            <div class="related">
                <h4><?= __('Related Visits') ?></h4>
                <?php if (!empty($address->visits)) : ?>
                <div class="table-responsive">
                    <table>
                        <tr>
                            <th><?= __('Id') ?></th>
                            <th><?= __('Date') ?></th>
                            <th><?= __('Status') ?></th>
                            <th><?= __('Forms') ?></th>
                            <th><?= __('Products') ?></th>
                            <th><?= __('Address Id') ?></th>
                            <th><?= __('Completed') ?></th>
                            <th><?= __('Duration') ?></th>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                        <?php foreach ($address->visits as $visits) : ?>
                        <tr>
                            <td><?= h($visits->id) ?></td>
                            <td><?= h($visits->date) ?></td>
                            <td><?= h($visits->status) ?></td>
                            <td><?= h($visits->forms) ?></td>
                            <td><?= h($visits->products) ?></td>
                            <td><?= h($visits->address_id) ?></td>
                            <td><?= h($visits->completed) ?></td>
                            <td><?= h($visits->duration) ?></td>
                            <td class="actions">
                                <?= $this->Html->link(__('View'), ['controller' => 'Visits', 'action' => 'view', $visits->id]) ?>
                                <?= $this->Html->link(__('Edit'), ['controller' => 'Visits', 'action' => 'edit', $visits->id]) ?>
                                <?= $this->Form->postLink(__('Delete'), ['controller' => 'Visits', 'action' => 'delete', $visits->id], ['confirm' => __('Are you sure you want to delete # {0}?', $visits->id)]) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
