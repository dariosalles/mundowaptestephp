<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateVisitsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('visits', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        
        $table->addColumn('id', 'integer', [
            'autoIncrement' => true,
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('date', 'date', [
            'default' => null,
            'null' => false,
        ]);
        // ALTERADO DO ORIGINAL db_structure.sql
        // ADICIONADO STATUS
        // $table->addColumn('status', 'integer', [
        //     'default' => null,
        //     'null' => false,
        // ]);
        $table->addColumn('forms', 'integer', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('products', 'integer', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('completed', 'integer', [
            'default' => 0,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('duration', 'integer', [
            'default' => 0,
            'limit' => null,
            'null' => false,
        ]);

        $table->addPrimaryKey(['id']);
        $table->addIndex(['date'], [
            'name' => 'date',
        ]);
        
        $table->create();
    }
}
