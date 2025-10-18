<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;
use App\Utils\AddressHelper;
use App\Utils\TotalDurationHelper;
use App\Utils\CheckVisitLimitDurationHelper;

use Cake\I18n\FrozenDate;

use Cake\ORM\TableRegistry;

use App\Utility\AddressFetcher; // importa a classe utilitária externa

use Cake\Validation\Validator;

class VisitsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Visits');
        $this->request->allowMethod(['get', 'post', 'put', 'delete']);
        $this->viewBuilder()->setClassName('Json'); // força resposta JSON
    }

    // GET /api/visits
    public function index()
    {
        $visits = $this->Visits->find()->all();
        $this->set([
            'status' => 'success',
            'data' => $visits,
            '_serialize' => ['status', 'data']
        ]);
    }

    // POST /api/visits/by-date
    public function byDate()
    {
        $requestData = $this->request->getData();

        // ######################## INICIO - VALIDATOR #################################

        $validator = new Validator();

        $validator
            // --- date ---
            ->requirePresence('date', 'create')
            ->notEmptyDate('date', 'A data é obrigatória')
            ->date('date', ['ymd'], 'Formato de data inválido (use YYYY-MM-DD)');

        $errors = $validator->validate($requestData);

        if (!empty($errors)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => 'error',
                    'errors' => $errors
                ]));
        }

        // ######################## FIM - VALIDATOR #################################

        $visits = $this->Visits->find()
            ->where(['date' => $requestData['date']])
            ->all(); // ou ->toList() se quiser array puro

        $this->set([
            'status' => 'success',
            'data' => $visits,
            '_serialize' => ['status', 'data']
        ]);
    }

    // GET /api/visits/{id}
    public function view($id = null)
    {
        $visit = $this->Visits->findById($id)->first();
        if (!$visit) {
            $this->set([
                'status' => 'error',
                'message' => 'Visita não encontrada',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        $this->set([
            'status' => 'success',
            'data' => $visit,
            '_serialize' => ['status', 'data']
        ]);
    }

    // POST /api/visits - CREATE
    public function add()
    {
        // REGRA DE CRIAÇÃO
        // Sempre que uma visita for criada, ou que os valores de formulários (forms) e/ou produtos
        // (products) forem alterados, o valor da duração (duration) deve ser setado com a quantidade
        // em minutos de acordo com seguinte regra:
        // ○​ Cada formulário da visita deve consumir quinze minutos;
        // ○​ Cada produto da visita deve consumir cinco minutos;

        // Pega os dados da request
        $requestData = $this->request->getData();

        // ######################## INICIO - VALIDATOR #################################

        $validator = new Validator();

        $validator
            // --- date ---
            ->requirePresence('date', 'create')
            ->notEmptyDate('date', 'A data é obrigatória')
            ->add('date', 'validFormat', [
                'rule' => function ($value, $context) {
                    // Remove espaços em branco
                    $value = trim($value);

                    // Tenta interpretar como dd/mm/yyyy
                    $dmy = \DateTime::createFromFormat('d/m/Y', $value);

                    if ($dmy && $dmy->format('d/m/Y') === $value) {
                        return true;
                    }

                    // Tenta interpretar como yyyy-mm-dd
                    $ymd = \DateTime::createFromFormat('Y-m-d', $value);
                    if ($ymd && $ymd->format('Y-m-d') === $value) {
                        return true;
                    }

                    return false;
                },
                'message' => 'Formato de data inválido. Use DD/MM/AAAA ou AAAA-MM-DD.'
            ])
            // --- forms ---
            ->requirePresence('forms', 'create')
            ->notEmptyString('forms', 'O campo forms é obrigatório')
            ->integer('forms', 'Forms precisa ser um número inteiro')
            ->greaterThanOrEqual('forms', 0, 'Forms não pode ser negativo')

            // --- products ---
            ->requirePresence('products', 'create')
            ->notEmptyString('products', 'O campo products é obrigatório')
            ->integer('products', 'Products precisa ser um número inteiro')
            ->greaterThanOrEqual('products', 0, 'Products não pode ser negativo')

            // --- completed ---
            ->allowEmptyString('completed')
            ->add('completed', 'custom', [
                'rule' => function ($value, $context) {
                    // Se estiver vazio, é válido
                    if ($value === '' || $value === null) {
                        return true;
                    }
                    // Se preenchido, deve ser 0 ou 1
                    return is_numeric($value) && in_array((int)$value, [0, 1], true);
                },
                'message' => 'O campo completed pode ser nulo/vazio ou conter 0 ou 1'
            ])

            // --- postal_code ---
            ->requirePresence('postal_code', 'create')
            ->notEmptyString('postal_code', 'O CEP é obrigatório')
            ->regex('postal_code', '/^\d{5}-?\d{3}$/', 'CEP inválido (use 00000-000 ou 00000000)')
            ->add('postal_code', 'validCep', [
                'rule' => function ($value, $context) {
                    $result = AddressFetcher::getAddressByPostalCode($value);

                    // Retorna false se 'error' for true
                    return empty($result['error']);
                },
                'message' => 'CEP inválido ou não encontrado.'
            ]);

        $errors = $validator->validate($requestData);

        if (!empty($errors)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => 'error',
                    'errors' => $errors
                ]));
        }

        // ######################## FIM - VALIDATOR #################################

        $requestDate = $requestData['date'];
        $requestForms = $requestData['forms'];
        $requestProducts = $requestData['products'];
        $requestCompleted = !empty($requestData['completed']) ? $requestData['completed'] : null;
        $requestPostalCode = $requestData['postal_code'];
        $requestSublocality = !empty($requestData['sublocality']) ? $requestData['sublocality'] : null;
        $requestStreet = !empty($requestData['street']) ? $requestData['street'] : null;
        $requestStreetNumber = !empty($requestData['street_number']) ? $requestData['street_number'] : null;
        $requestComplement = !empty($requestData['complement']) ? $requestData['complement'] : null;
        $requestDateObj = new FrozenDate($requestDate);
        $requestDuration = TotalDurationHelper::calculateDuration($requestForms, $requestProducts);

        // Função para calculo do total de formulários e produtos
        $requestData['duration'] = TotalDurationHelper::calculateDuration($requestData['forms'], $requestData['products']);

        $newValues = [
            'date' => $requestDate,
            'forms' => intval($requestForms),
            'products' => intval($requestProducts),
            'completed' => $requestCompleted ?? 0,
            'postal_code' => $requestPostalCode,
            'sublocality' => $requestSublocality,
            'street' => $requestStreet,
            'street_number' => $requestStreetNumber,
            'complement' => $requestComplement,
            'duration' => intval($requestDuration)
        ];

        //dd($newValues);

        // Cria a entidade e popula com os dados, incluindo duration
        $visit = $this->Visits->newEmptyEntity();
        $visit = $this->Visits->patchEntity($visit, $newValues);

        // Salva no banco
        if ($this->Visits->save($visit)) {
            $this->set([
                'status' => 'success',
                'data' => $visit,
                '_serialize' => ['status', 'data']
            ]);
        } else {
            $this->set([
                'status' => 'error',
                'errors' => $visit->getErrors(), // Adiciona os erros de validação
                'message' => 'Erro ao salvar a visita',
                '_serialize' => ['status', 'message', 'errors']
            ]);
        }
    }

    public function edit($id = null)
    {
        $visit = $this->Visits->findById($id)->first();

        // se não tiver registro, da o erro e retorna
        if (!$visit) {
            $this->set([
                'status' => 'error',
                'message' => 'Visita não encontrada',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        // sem erro, continua
        $visitDate = $visit->date;
        $visitDuration = $visit->duration;
        $oldDate = clone $visitDate; // clona o objeto antes do patchEntity

        // Guarda os valores antigos - valores atuais do registro
        $oldValues = [
            'date' => $visit->date,
            'forms' => $visit->forms,
            'products' => $visit->products,
            'completed' => $visit->completed,
            'duration' => $visit->duration,
            //'postal_code' => $visit->postal_code
        ];

        // Valores atuais vindo pela request
        $requestData = $this->request->getData();

        // ######################## INICIO - VALIDATOR #################################

        $validator = new Validator();

        $validator
            // --- date ---
            ->requirePresence('date', 'create')
            ->notEmptyDate('date', 'A data é obrigatória')
            ->add('date', 'validFormat', [
                'rule' => function ($value, $context) {
                    // Tenta interpretar como dd/mm/yyyy
                    $dmy = \DateTime::createFromFormat('d/m/Y', $value);
                    if ($dmy && $dmy->format('d/m/Y') === $value) {
                        return true;
                    }

                    // Tenta interpretar como yyyy-mm-dd
                    $ymd = \DateTime::createFromFormat('Y-m-d', $value);
                    if ($ymd && $ymd->format('Y-m-d') === $value) {
                        return true;
                    }

                    return false;
                },
                'message' => 'Formato de data inválido. Use DD/MM/AAAA ou AAAA-MM-DD.'
            ])
            // --- forms ---
            ->requirePresence('forms', 'create')
            ->notEmptyString('forms', 'O campo forms é obrigatório')
            ->integer('forms', 'Forms precisa ser um número inteiro')
            ->greaterThanOrEqual('forms', 0, 'Forms não pode ser negativo')

            // --- products ---
            ->requirePresence('products', 'create')
            ->notEmptyString('products', 'O campo products é obrigatório')
            ->integer('products', 'Products precisa ser um número inteiro')
            ->greaterThanOrEqual('products', 0, 'Products não pode ser negativo')

            // --- completed ---
            ->allowEmptyString('completed')
            ->add('completed', 'custom', [
                'rule' => function ($value, $context) {
                    // Se estiver vazio, é válido
                    if ($value === '' || $value === null) {
                        return true;
                    }
                    // Se preenchido, deve ser 0 ou 1
                    return is_numeric($value) && in_array((int)$value, [0, 1], true);
                },
                'message' => 'O campo completed pode ser nulo ou conter 0 ou 1'
            ])

            // --- postal_code ---
            ->requirePresence('postal_code', 'create')
            ->notEmptyString('postal_code', 'O CEP é obrigatório')
            ->regex('postal_code', '/^\d{5}-?\d{3}$/', 'CEP inválido (use 00000-000 ou 00000000)')
            ->add('postal_code', 'validCep', [
                'rule' => function ($value, $context) {
                    $result = AddressFetcher::getAddressByPostalCode($value);

                    // Retorna false se 'error' for true
                    return empty($result['error']);
                },
                'message' => 'CEP inválido ou não encontrado.'
            ]);

        $errors = $validator->validate($requestData);

        if (!empty($errors)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => 'error',
                    'errors' => $errors
                ]));
        }

        // ######################## FIM - VALIDATOR #################################

        $requestDate = $requestData['date'];
        $requestForms = $requestData['forms'];
        $requestProducts = $requestData['products'];
        $requestCompleted = !empty($requestData['completed']) ? $requestData['completed'] : null;
        $requestPostalCode = $requestData['postal_code'];
        $requestSublocality = !empty($requestData['sublocality']) ? $requestData['sublocality'] : null;
        $requestStreet = !empty($requestData['street']) ? $requestData['street'] : null;
        $requestStreetNumber = !empty($requestData['street_number']) ? $requestData['street_number'] : null;
        $requestComplement = !empty($requestData['complement']) ? $requestData['complement'] : null;
        $requestDateObj = new FrozenDate($requestDate);
        $requestDuration = TotalDurationHelper::calculateDuration($requestForms, $requestProducts);

        $requestData['duration'] = $requestDuration;

        $newValues = [
            'date' => $requestDate,
            'forms' => intval($requestForms),
            'products' => intval($requestProducts),
            'completed' => $requestCompleted ?? 0,
            'postal_code' => $requestPostalCode,
            'sublocality' => $requestSublocality,
            'street' => $requestStreet,
            'street_number' => $requestStreetNumber,
            'complement' => $requestComplement,
            'duration' => intval($requestDuration)
        ];

        // try {
            /**
             * REGRA 2
             * Sempre que o endereço associado a uma visita for alterado, o mesmo deverá ser substituído,
             * e não editado, ou seja, o endereço atual deve ser deletado e deverá ser criado um novo.
             */
            $postalCode = preg_replace('/\D/', '', $requestPostalCode);

            // acesso a tabela Address
            $addressesTable = TableRegistry::getTableLocator()->get('Addresses');

            // Busca o endereço atual vinculado à visita
            $currentAddress = $addressesTable->find()
                ->where(['foreign_table' => 'visits', 'foreign_id' => $visit->id])
                ->first();

            if ($currentAddress 
                && $currentAddress['postal_code'] !== $postalCode 
                || $requestSublocality !== null
                || $requestStreet !== null 
                || $requestComplement !== null) {

                $this->updateAddress($currentAddress, $newValues, $visit, $postalCode);
               
            }

            // * REGRA 3 (UPDATE)
            // Sempre que uma visita for criada, ou que os valores de formulários (forms) e/ou produtos
            // (products) forem alterados, o valor da duração (duration) deve ser setado com a quantidade
            // em minutos de acordo com seguinte regra:
            // ○​ Cada formulário da visita deve consumir quinze minutos;
            // ○​ Cada produto da visita deve consumir cinco minutos;
            $keysToCompare = ['forms', 'products'];
            $hasChanged = false;

            foreach ($keysToCompare as $key) {
                if (($oldValues[$key] ?? null) !== ($newValues[$key] ?? null)) {
                    $hasChanged = true;
                    break; // já encontramos uma diferença, não precisa continuar
                }
            }

            if ($hasChanged) {

                $this->Visits->patchEntity($visit, $newValues);

            }
            
            /**
             * REGRA 4 (UPDATE)
             * Sempre que uma visita for criada, ou que a sua data for alterada, deverá ser consultado
             * o registro correspondente na tabela de dias úteis (workdays). Caso não exista, criar.
             */

            // SE AS DATAS NÃO FOREM IGUAIS (DATA ATUAL DO REGISTRO COM A DATA ENVIADA PELO REQUEST)
            if (!$visitDate->eq($requestDateObj)) {

                $workdaysTable = TableRegistry::getTableLocator()->get('Workdays');
                $oldDate = $visitDate;
                $newDate = $requestDateObj;

                $existingWorkday = $workdaysTable->find()->where(['date' => $newDate])->first();
                
                // Cria o novo workday, se não existir
                if (!$existingWorkday) {
                    $newWorkday = $workdaysTable->newEntity([
                        'date' => $newDate,
                        'visits' => 1,
                        'completed' => 0,
                        'duration' => 0
                    ]);
                    $workdaysTable->save($newWorkday);
                }

                // Atualiza o registro de visita
                $visit = $this->Visits->patchEntity($visit, $newValues);

                if ($this->Visits->save($visit)) {

                    // Atualiza apenas as duas datas afetadas
                    $this->updateWorkdays($oldDate, $newDate);

                    $this->set([
                        'status' => 'success',
                        'data' => $visit,
                        '_serialize' => ['status', 'data']
                    ]);
                } else {
                    $this->set([
                        'status' => 'error',
                        'errors' => $visit->getErrors(),
                        '_serialize' => ['status', 'errors']
                    ]);
                }

                
            } else {

                $oldDate = $visitDate;
                $newDate = $requestDateObj;

                $visit = $this->Visits->patchEntity($visit, $newValues);

                if ($this->Visits->save($visit)) {
                    
                    // Atualiza apenas as duas datas afetadas
                    $this->updateWorkdays($oldDate, $newDate);

                    $this->set([
                        'status' => 'success',
                        'data' => $visit,
                        '_serialize' => ['status', 'data']
                    ]);
                } else {
                    $this->set([
                        'status' => 'error',
                        'errors' => $visit->getErrors(),
                        '_serialize' => ['status', 'errors']
                    ]);
                }

            }

            /**
             * REGRA 5
             * Sempre que uma visita for criada, ou o valor da duração for alterado,
             * o valor da duração da workday correspondente deve refletir a soma total.
             */
            // if ($visitDuration !== $requestDuration) {
            //     $workdaysTable = TableRegistry::getTableLocator()->get('Workdays');

            //     $workday = $workdaysTable->find()
            //         ->where(['date' => $requestDateObj])
            //         ->first();

            //     if ($workday) {
            //         // Soma total de duração de todas as visitas da mesma data
            //         $totalDuration = $this->Visits->find()
            //             ->where(['date' => $requestDateObj])
            //             ->sumOf('duration');

            //         $workday->duration = $totalDuration;
            //         $workdaysTable->save($workday);
            //     }
            // }

        // } catch (\Exception $e) {
        //     $this->set([
        //         'status' => 'error',
        //         'message' => $e->getMessage(),
        //         '_serialize' => ['status', 'message']
        //     ]);
        // }

        
    }

    // DELETE /api/visits/{id}
    public function delete($id = null)
    {
        $visit = $this->Visits->findById($id)->first();
        if (!$visit) {
            $this->set([
                'status' => 'error',
                'message' => 'Visita não encontrada',
                '_serialize' => ['status', 'message']
            ]);
            return;
        }

        if ($this->Visits->delete($visit)) {
            $this->set([
                'status' => 'success',
                'message' => 'Visita removida com sucesso',
                '_serialize' => ['status', 'message']
            ]);
        } else {
            $this->set([
                'status' => 'error',
                'message' => 'Erro ao remover a visita',
                '_serialize' => ['status', 'message']
            ]);
        }
    }

    private function updateWorkdays($oldDate = null, $newDate = null)
    {
        $visitsTable = TableRegistry::getTableLocator()->get('Visits');
        $workdaysTable = TableRegistry::getTableLocator()->get('Workdays');

        // Lista de datas a atualizar (sem duplicar)
        $datesToUpdate = array_unique(array_filter([$oldDate, $newDate]));

        foreach ($datesToUpdate as $date) {

            // Agrupa os visits dessa data específica
            $data = $visitsTable->find()
                ->select([
                    'date',
                    'total_visits' => $visitsTable->find()->func()->count('id'),
                    'total_completed' => $visitsTable->find()->func()->sum('completed'),
                    'total_duration' => $visitsTable->find()->func()->sum('duration')
                ])
                ->where(['date' => $date])
                ->group('date')
                ->enableHydration(false)
                ->first();

            $workday = $workdaysTable->find()->where(['date' => $date])->first();

            if ($data) {
                // Há visitas para essa data → atualiza ou cria o workday
                if ($workday) {
                    $workday->visits = $data['total_visits'];
                    $workday->completed = $data['total_completed'];
                    $workday->duration = $data['total_duration'];
                    $workdaysTable->save($workday);
                } else {
                    $newWorkday = $workdaysTable->newEntity([
                        'date' => $data['date'],
                        'visits' => $data['total_visits'],
                        'completed' => $data['total_completed'],
                        'duration' => $data['total_duration'],
                    ]);
                    $workdaysTable->save($newWorkday);
                }
            } elseif ($workday) {
                // Se não há mais visitas para essa data, zera ou remove
                $workdaysTable->delete($workday);
            }
        }
    }

    private function updateAddress($currentAddress, $newValues, $visit, string $postalCode) 
    {

        try {
             
            $addressesTable = TableRegistry::getTableLocator()->get('Addresses');

            // Atualiza o endereço criando um novo registro
            $addressData = AddressFetcher::getAddressByPostalCode($postalCode);

            if (!$addressData || empty($addressData['street'])) {
                throw new \Exception('Endereço não encontrado para o CEP informado.');
            }

            // Busca o endereço atual vinculado à visita para excluir
            $currentAddress = $addressesTable->find()
                ->where(['foreign_table' => 'visits', 'foreign_id' => $visit->id])
                ->first();

            // Remove o endereço atual (se existir)
            if ($currentAddress) {
                $addressesTable->delete($currentAddress);
            }

            $patchData = [
                'foreign_table' => 'visits',
                'foreign_id' => $visit->id,
                'postal_code' => $postalCode,
                'state' => $addressData['state'] ?? null,
                'city' => $addressData['city'] ?? null,
                'sublocality' => ($newValues['sublocality']) ? $newValues['sublocality'] : (($addressData['neighborhood']) ? $addressData['neighborhood'] : null),
                'street' => ($newValues['street']) ? $newValues['street'] : (($addressData['street']) ? $addressData['street'] : null),
                'street_number' => $newValues['street_number'],
                'complement' => $newValues['complement'],
            ];

            // Cria novo endereço vinculado à visita
            $newAddress = $addressesTable->newEntity($patchData);

            if (!$addressesTable->save($newAddress)) {
                // tratar erro, logar ou lançar exceção
                throw new \Exception('Não foi possível salvar o endereço.');
            }
                
        } catch (\Throwable $th) {
            throw new \Exception('Erro ao salvar novo endereço: ' . $th->getMessage(), 0, $th);
        }
       
    }

    public function reallocatePending()
    {
        $requestData = $this->request->getData();

        // Validação da data
        $validator = new Validator();
        $validator
            ->requirePresence('date', 'create')
            ->notEmptyDate('date', 'A data é obrigatória')
            ->date('date', ['ymd'], 'Formato de data inválido (use YYYY-MM-DD)');

        $errors = $validator->validate($requestData);

        if (!empty($errors)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'status' => 'error',
                    'errors' => $errors
                ]));
        }

        try {
            $date = new FrozenDate($requestData['date']);

            $results = $this->reallocatePendingVisitsWithExisting($date);

            $this->set([
                'status' => 'success',
                'message' => 'Visitas pendentes realocadas com sucesso',
                'data' => $results,
                '_serialize' => ['status', 'message', 'data']
            ]);

        } catch (\Exception $e) {
            $this->set([
                'status' => 'error',
                'message' => 'Erro ao realocar visitas: ' . $e->getMessage(),
                '_serialize' => ['status', 'message']
            ]);
        }
    }

    /**
     * Realoca visitas pendentes considerando a duração existente em cada data futura
     */
    private function reallocatePendingVisitsWithExisting(FrozenDate $startDate): array
    {
        $visitsTable = $this->Visits;
        
        // Busca visitas pendentes da data especificada (completed = 0)
        $pendingVisits = $visitsTable->find()
            ->where([
                'date' => $startDate,
                'completed' => 0
            ])
            ->order(['duration' => 'DESC', 'id' => 'ASC'])
            ->toArray();

        if (empty($pendingVisits)) {
            return ['message' => 'Nenhuma visita pendente encontrada para a data especificada'];
        }

        $results = [
            'original_date' => $startDate->format('Y-m-d'),
            'total_pending_visits' => count($pendingVisits),
            'total_pending_duration' => array_sum(array_column($pendingVisits, 'duration')),
            'reallocations' => [],
            'new_visits_created' => [],
            'daily_distribution' => []
        ];

        $currentDate = $startDate->modify('+1 day');
        $maxDaysToCheck = 30;

        // Processa cada visita pendente
        foreach ($pendingVisits as $visit) {
            $visitDuration = $visit->duration;
            $placed = false;
            $checkDate = clone $currentDate;
            $daysChecked = 0;

            // Procura um dia com espaço disponível
            while (!$placed && $daysChecked < $maxDaysToCheck) {
                // Calcula a duração atual desta data (visits existentes + visits criados)
                $existingDuration = $this->getExistingDurationForDate($checkDate);
                $allocatedDuration = $this->getAllocatedDurationForDate($results['new_visits_created'], $checkDate);
                $totalDuration = $existingDuration + $allocatedDuration;
                $availableCapacity = 480 - $totalDuration;

                // Se há espaço para esta visita, coloca aqui
                if ($visitDuration <= $availableCapacity) {
                    // Cria uma NOVA visita para a data futura
                    $newVisit = $this->createNewVisitForDate($visit, $checkDate);
                    
                    if ($newVisit) {
                        $results['reallocations'][] = [
                            'original_visit_id' => $visit->id,
                            'new_visit_id' => $newVisit->id,
                            'old_date' => $visit->date->format('Y-m-d'),
                            'new_date' => $checkDate->format('Y-m-d'),
                            'duration' => $visitDuration,
                            'existing_duration' => $existingDuration,
                            'available_capacity' => $availableCapacity,
                            'type' => 'new_visit_created'
                        ];
                        
                        $results['new_visits_created'][] = [
                            'date' => $checkDate->format('Y-m-d'),
                            'visit_id' => $newVisit->id,
                            'duration' => $visitDuration
                        ];
                        
                        $placed = true;
                        
                        // Atualiza o workday desta data específica
                        $this->updateWorkdays($checkDate, $checkDate);
                    }
                }
                
                // Avança para o próximo dia se não encontrou espaço
                if (!$placed) {
                    $checkDate = $checkDate->modify('+1 day');
                    $daysChecked++;
                }
            }

            if (!$placed) {
                throw new \Exception("Não foi possível realocar a visita ID {$visit->id} dentro de {$maxDaysToCheck} dias");
            }
        }

        // Coleta estatísticas finais de distribuição
        $this->collectDistributionStats($results, $startDate, $checkDate);

        return $results;
    }

    /**
     * Calcula a duração total existente para uma data específica
     */
    private function getExistingDurationForDate(FrozenDate $date): int
    {
        $visitsTable = $this->Visits;
        
        $result = $visitsTable->find()
            ->select([
                'total_duration' => $visitsTable->find()->func()->sum('duration')
            ])
            ->where([
                'date' => $date
            ])
            ->enableHydration(false)
            ->first();

        return (int)($result['total_duration'] ?? 0);
    }

    private function getAllocatedDurationForDate(array $newVisitsCreated, FrozenDate $date): int
    {
        $dateKey = $date->format('Y-m-d');
        $total = 0;
        
        foreach ($newVisitsCreated as $visit) {
            if ($visit['date'] === $dateKey) {
                $total += $visit['duration'];
            }
        }
        
        return $total;
    }

    private function createNewVisitForDate($originalVisit, FrozenDate $targetDate)
    {
        $visitsTable = $this->Visits;
        
        // Cria nova entidade com os mesmos dados da visita original, mas com nova data
        $newVisit = $visitsTable->newEntity([
            'date' => $targetDate,
            'forms' => $originalVisit->forms,
            'products' => $originalVisit->products,
            'completed' => 0, // Nova visita sempre como pendente
            'duration' => $originalVisit->duration,
        ]);
        
        if ($visitsTable->save($newVisit)) {
            // Copia o endereço da visita original para a nova visita
            $this->copyAddressForNewVisit($originalVisit->id, $newVisit->id);
            return $newVisit;
        }
        
        return null;
    }

    private function copyAddressForNewVisit($originalVisitId, $newVisitId): void
    {
        $addressesTable = TableRegistry::getTableLocator()->get('Addresses');
        
        $originalAddress = $addressesTable->find()
            ->where([
                'foreign_table' => 'visits',
                'foreign_id' => $originalVisitId
            ])
            ->first();
        
        if ($originalAddress) {
            $newAddress = $addressesTable->newEntity([
                'foreign_table' => 'visits',
                'foreign_id' => $newVisitId,
                'postal_code' => $originalAddress->postal_code,
                'state' => $originalAddress->state,
                'city' => $originalAddress->city,
                'sublocality' => $originalAddress->sublocality,
                'street' => $originalAddress->street,
                'street_number' => $originalAddress->street_number,
                'complement' => $originalAddress->complement,
            ]);
            
            $addressesTable->save($newAddress);
        }
    }

    /**
     * Coleta estatísticas de distribuição após a realocação
     */
    private function collectDistributionStats(array &$results, FrozenDate $startDate, FrozenDate $endDate): void
    {
        $datesToCheck = [];
        $current = clone $startDate;
        
        // Coleta todas as datas entre startDate e endDate
        while ($current <= $endDate) {
            $datesToCheck[] = clone $current;
            $current = $current->modify('+1 day');
        }

        // Para cada data, calcula a distribuição
        foreach ($datesToCheck as $date) {
            $existingDuration = $this->getExistingDurationForDate($date);
            $allocatedDuration = $this->getAllocatedDurationForDate($results['new_visits_created'], $date);
            $totalDuration = $existingDuration + $allocatedDuration;
            $availableCapacity = 480 - $totalDuration;
            
            $results['daily_distribution'][] = [
                'date' => $date->format('Y-m-d'),
                'existing_duration' => $existingDuration,
                'new_allocated_duration' => $allocatedDuration,
                'total_duration' => $totalDuration,
                'available_capacity' => $availableCapacity,
                'utilization_percent' => round(($totalDuration / 480) * 100, 2)
            ];
        }
    }

    /**
     * Versão alternativa mais otimizada - processa em lotes
     */
    private function reallocatePendingVisitsBatch(FrozenDate $startDate): array
    {
        $visitsTable = $this->Visits;
        
        // Busca visitas pendentes
        $pendingVisits = $visitsTable->find()
            ->where([
                'date' => $startDate,
                'completed' => 0
            ])
            ->order(['duration' => 'DESC'])
            ->toArray();

        if (empty($pendingVisits)) {
            return ['message' => 'Nenhuma visita pendente encontrada para a data especificada'];
        }

        $results = [
            'original_date' => $startDate->format('Y-m-d'),
            'total_pending_visits' => count($pendingVisits),
            'total_pending_duration' => array_sum(array_column($pendingVisits, 'duration')),
            'reallocations' => []
        ];

        $currentDate = $startDate->modify('+1 day');
        $maxDaysToCheck = 30;
        
        // Agrupa visitas por data de destino
        $allocations = [];
        
        foreach ($pendingVisits as $visit) {
            $visitDuration = $visit->duration;
            $allocated = false;
            $checkDate = clone $currentDate;
            $daysChecked = 0;

            while (!$allocated && $daysChecked < $maxDaysToCheck) {
                // Calcula duração já alocada + duração existente para esta data
                $existingDuration = $this->getExistingDurationForDate($checkDate);
                $allocatedDuration = $this->getAllocatedDurationForDate($allocations, $checkDate);
                $totalDuration = $existingDuration + $allocatedDuration;
                $availableCapacity = 480 - $totalDuration;

                if ($visitDuration <= $availableCapacity) {
                    // Pode alocar nesta data
                    $dateKey = $checkDate->format('Y-m-d');
                    $allocations[$dateKey][] = [
                        'visit' => $visit,
                        'duration' => $visitDuration
                    ];
                    $allocated = true;
                } else {
                    $checkDate = $checkDate->modify('+1 day');
                    $daysChecked++;
                }
            }

            if (!$allocated) {
                throw new \Exception("Não foi possível realocar a visita ID {$visit->id}");
            }
        }

        // Executa as alocações no banco de dados
        foreach ($allocations as $date => $visitsToAllocate) {
            $targetDate = new FrozenDate($date);
            $existingDuration = $this->getExistingDurationForDate($targetDate);
            
            foreach ($visitsToAllocate as $allocation) {
                $visit = $allocation['visit'];
                $oldDate = $visit->date;
                $visit->date = $targetDate;
                
                if ($visitsTable->save($visit)) {
                    $results['reallocations'][] = [
                        'visit_id' => $visit->id,
                        'old_date' => $oldDate->format('Y-m-d'),
                        'new_date' => $date,
                        'duration' => $allocation['duration'],
                        'existing_duration' => $existingDuration
                    ];
                }
            }
        }

        // Coleta estatísticas
        $this->collectDistributionStats($results, $startDate, $checkDate);

        return $results;
    }

    /**
     * Obtém a duração alocada para uma data específica no array de alocações
     */
    // private function getAllocatedDurationForDate(array $allocations, FrozenDate $date): int
    // {
    //     $dateKey = $date->format('Y-m-d');
    //     if (!isset($allocations[$dateKey])) {
    //         return 0;
    //     }

    //     return array_sum(array_column($allocations[$dateKey], 'duration'));
    // }

    /**
     * Atualiza todos os workdays afetados
     */
    private function updateAllAffectedWorkdays(FrozenDate $startDate, FrozenDate $endDate): void
    {
        $datesToUpdate = [];
        $current = clone $startDate;
        
        while ($current <= $endDate) {
            $datesToUpdate[] = clone $current;
            $current = $current->modify('+1 day');
        }

        foreach ($datesToUpdate as $date) {
            $this->updateWorkdays($date, $date);
        }
    }

}
