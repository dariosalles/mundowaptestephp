<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

use Cake\Event\EventInterface;
use ArrayObject;
use Cake\ORM\TableRegistry;


use App\Utility\AddressFetcher;

/**
 * Visits Model
 *
 * @method \App\Model\Entity\Visit newEmptyEntity()
 * @method \App\Model\Entity\Visit newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Visit[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Visit get($primaryKey, $options = [])
 * @method \App\Model\Entity\Visit findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Visit patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Visit[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Visit|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Visit saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Visit[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Visit[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Visit[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Visit[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class VisitsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('visits');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->date('date')
            ->requirePresence('date', 'create')
            ->notEmptyDate('date');

        $validator
            ->integer('completed')
            ->notEmptyString('completed');

        $validator
            ->integer('forms')
            ->requirePresence('forms', 'create')
            ->notEmptyString('forms');

        $validator
            ->integer('products')
            ->requirePresence('products', 'create')
            ->notEmptyString('products');

        $validator
            ->integer('duration')
            ->notEmptyString('duration');

        return $validator;
    }

    // Evento afterSave
    public function afterSave(EventInterface $event, $entity, ArrayObject $options)
    {

        if ($entity->isNew()) {

            // a cada criação da visita é criado um endereço atrelado
            $cep = $entity->postal_code ?? null;
            if (!$cep) return;
            $addressData = AddressFetcher::getAddressByPostalCode($cep);

            // Ignora se houve erro no retorno
            if (!isset($addressData['error'])) {
                $addressesTable = TableRegistry::getTableLocator()->get('Addresses');
                $address = $addressesTable->newEmptyEntity();

                $requestedData = [
                    'foreign_table' => 'visits',
                    'foreign_id' => $entity->id,
                    'postal_code' => $cep,
                    'state' => $addressData['state'] ?? null,
                    'city' => $addressData['city'] ?? null,
                    'sublocality' => ($entity->sublocality) ? $entity->sublocality : (($addressData['neighborhood']) ? $addressData['neighborhood'] : null),
                    'street' => ($entity->street) ? $entity->street : $addressData['street'],
                    'street_number' => ($entity->street_number) ? $entity->street_number : null,
                    'complement' => ($entity->complement) ? $entity->complement : null,
                ];

                // dd($requestedData);

                $address = $addressesTable->patchEntity($address, $requestedData);

                $addressesTable->save($address);
            }

            // a cada criação da visita é criado um workday caso já não exista
            if (!empty($entity->date)) {
                $workdaysTable = TableRegistry::getTableLocator()->get('Workdays');

                // Busca um registro pela data da visita para ver se já não existe
                $workday = $workdaysTable->find()
                    ->where(['date' => $entity->date])
                    ->first();

                // caso já existir essa data somente atualiza visits e duration
                if ($workday) {
                    // Atualiza o registro existente
                    $workday->visits += 1;

                    // Se a visita tiver duração, soma também
                    if (!empty($entity->duration)) {
                        $workday->duration += $entity->duration;
                    }

                    $workdaysTable->save($workday);

                // se não cria um novo registro
                } else {

                    $newWorkday = $workdaysTable->newEmptyEntity();
                    $newWorkday = $workdaysTable->patchEntity($newWorkday, [
                        'date'      => $entity->date,
                        'visits'    => 1,
                        'completed' => 0,
                        'duration'  => $entity->duration ?? 0,
                    ]);
                    $workdaysTable->save($newWorkday);
                }
            }
        } 
    }

    public function beforeMarshal(\Cake\Event\EventInterface $event, \ArrayObject $data, \ArrayObject $options)
    {
        if (!empty($data['date'])) {
            $dmy = \DateTime::createFromFormat('d/m/Y', $data['date']);
            if ($dmy) {
                $data['date'] = $dmy->format('Y-m-d');
                return;
            }

            $ymd = \DateTime::createFromFormat('Y-m-d', $data['date']);
            if ($ymd) {
                $data['date'] = $ymd->format('Y-m-d');
            }
        }
    }
}
