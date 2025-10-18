<?php
namespace App\Utils;

use Cake\ORM\TableRegistry;

class CheckVisitLimitDurationHelper
{
    public static function checkLimit($date, int $duration, string $method): int
    {
        $visitsTable = TableRegistry::getTableLocator()->get('Visits');

        // Agrupa os visits dessa data específica
        $data = $visitsTable->find()
            ->select([
                'date',
                'total_visits' => $visitsTable->find()->func()->count('id'),
                'total_completed' => $visitsTable->find()->func()->count('completed'),
                'total_duration' => $visitsTable->find()->func()->sum('duration')
            ])
            ->where(['date' => $date])
            ->group('date')
            ->enableHydration(false)
            ->first();

        // se não tiver nenhum registro com essa data, ou seja vazio
        if(!empty($data)){
            if($method === 'add') {
                $total = intval($data['total_duration']) + intval($duration); 
            }else {
                $total = intval($data['total_duration']) + intval($duration);
            }
        } else {
            $total = 0;
        }
        
        return $total;
    }
}
