<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateAddressesTable extends AbstractMigration
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
        $table = $this->table('addresses', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        
        $table->addColumn('id', 'integer', [
            'autoIncrement' => true,
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('foreign_table', 'string', [
            'default' => null,
            'limit' => 100,
            'null' => false,
        ]);
        $table->addColumn('foreign_id', 'integer', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('postal_code', 'string', [
            'default' => null,
            'limit' => 8,
            'null' => false,
        ]);
        $table->addColumn('state', 'string', [
            'default' => null,
            'limit' => 2,
            'null' => false,
        ]);
        $table->addColumn('city', 'string', [
            'default' => null,
            'limit' => 200,
            'null' => false,
        ]);
        $table->addColumn('sublocality', 'string', [
            'default' => null,
            'limit' => 200,
            'null' => false,
        ]);
        $table->addColumn('street', 'string', [
            'default' => null,
            'limit' => 200,
            'null' => false,
        ]);
        $table->addColumn('street_number', 'string', [
            'default' => null,
            'limit' => 200,
            'null' => false,
        ]);
        $table->addColumn('complement', 'string', [
            'default' => '',
            'limit' => 200,
            'null' => true, // ALTERADO PELA REGRA // Campos opcionais / passíveis de valores vazios:
        ]);
        
        $table->addPrimaryKey(['id']);
        $table->addIndex([
            'foreign_table',
            'foreign_id',
        ], [
            'name' => 'foreign_table__foreign_id',
        ]);
        
        $table->create();
    }

}
