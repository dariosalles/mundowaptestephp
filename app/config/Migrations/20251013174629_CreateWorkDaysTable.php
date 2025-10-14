<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateWorkDaysTable extends AbstractMigration
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
        $table = $this->table('workdays', [
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
        $table->addColumn('visits', 'integer', [
            'default' => 0,
            'limit' => null,
            'null' => true, // ALTERADO PELA REGRA // Campos opcionais / passíveis de valores vazios:
        ]);
        $table->addColumn('completed', 'integer', [
            'default' => 0,
            'limit' => null,
            'null' => true, // ALTERADO PELA REGRA // Campos opcionais / passíveis de valores vazios:
        ]);
        $table->addColumn('duration', 'integer', [
            'default' => 0,
            'limit' => null,
            'null' => true, // ALTERADO PELA REGRA // Campos opcionais / passíveis de valores vazios:
        ]);
        
        $table->addPrimaryKey(['id']);
        $table->addIndex(['date'], [
            'name' => 'date',
        ]);
        
        $table->create();
    }
}
