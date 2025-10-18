<?php
namespace App\Utility;

class AddressFetcher
{
    public static function getAddressByPostalCode(string $cep): array
    {

        // Remove tudo que não for número
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) {
            return ['error' => 'CEP inválido'];
        }

        // Cria contexto com timeout de 3 segundos
        $context = stream_context_create([
            'http' => ['timeout' => 3]
        ]);

        try {
            // Tenta buscar pela República Virtual
            $urlRV = "http://cep.republicavirtual.com.br/web_cep.php?cep={$cep}&formato=json";
            $responseRV = file_get_contents($urlRV, false, $context);

            if ($responseRV) {
                $dataRV = json_decode($responseRV, true);
                if (!empty($dataRV) && isset($dataRV['resultado']) && $dataRV['resultado'] != 0) {
                    return [
                        'postal_code'  => $cep,
                        'street'       => trim(($dataRV['tipo_logradouro'] ?? '') . ' ' . ($dataRV['logradouro'] ?? '')),
                        'neighborhood' => $dataRV['bairro'] ?? null,
                        'city'         => $dataRV['cidade'] ?? null,
                        'state'        => $dataRV['uf'] ?? null,
                        'source'       => 'Republica Virtual'
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Logar o erro se quiser (ex: Log::error(...))
        }

        try {
            // Tenta buscar pelo ViaCEP
            $urlViaCEP = "https://viacep.com.br/ws/{$cep}/json/";
            $responseViaCEP = file_get_contents($urlViaCEP, false, $context);

            if ($responseViaCEP) {
                $dataVia = json_decode($responseViaCEP, true);
                if (!empty($dataVia) && !isset($dataVia['erro'])) {
                    return [
                        'postal_code'  => $cep,
                        'street'       => $dataVia['logradouro'] ?? null,
                        'neighborhood' => $dataVia['bairro'] ?? null,
                        'city'         => $dataVia['localidade'] ?? null,
                        'state'        => $dataVia['uf'] ?? null,
                        'source'       => 'Via CEP'
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Logar o erro se quiser (ex: Log::error(...))
        }

        return ['error' => true, 'message' => 'CEP não encontrado'];
    }

}
