<?php
namespace App\Utils;

class TotalDurationHelper
{
    public static function calculateDuration(string $forms, string $products): int
    {
        // Calcula duration (cada form = 15min, cada product = 5min)
        $forms = intval($forms ?? 0);
        $products = intval($products ?? 0);

        $total = $forms * 15 + $products * 5;

        return $total;
        
    }

    // public static function calculateDuration(string $forms, string $products): int
    // {
    //     // Calcula duration (cada form = 15min, cada product = 5min)
    //     $forms = intval($forms ?? 0);
    //     $products = intval($products ?? 0);

    //     $total = $forms * 15 + $products * 5;

    //     if($total > 480){
    //         return 0;
    //     } else {
    //         return $total;
    //     }
    // }
}
