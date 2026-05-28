<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\KudosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador para manejar la lógica de reclamo de recompensas diarias por inicio de sesión.
 * Se encarga de verificar si el usuario ya ha reclamado su recompensa del día, calcular la racha de inicio de sesión,
 * otorgar los Kudos correspondientes y devolver la información relevante al cliente.
 */
class DailyRewardController extends Controller
{
    private KudosService $kudosService;

    public function __construct(KudosService $kudosService)
    {
        $this->kudosService = $kudosService;
    }

    public function claim(): JsonResponse
    {
        $user = Auth::user();
        $rewardResult = $this->kudosService->processDailyLogin($user);

        if (!$rewardResult['awarded']) {
            return $this->respondData(
                data: [
                    'status' => 'already_claimed',
                    'kudos' => $user->fresh()->total_kudos,
                    'streak' => $rewardResult['streak']
                ],
                meta: [
                    'message' => 'Reward already claimed today.',
                    'server_date' => $rewardResult['server_date'],
                    'is_new_record' => $rewardResult['is_new_record'],
	                  'is_critical' => $rewardResult['is_critical'],
                ]
            );
        }

        return $this->respondData(
            data: [
                'status' => 'claimed',
                'kudos' => $user->fresh()->total_kudos,
                'streak' => $rewardResult['streak']
            ],
            meta: [
                'message' => 'Daily reward granted!',
                'server_date' => $rewardResult['server_date'],
                'is_new_record' => $rewardResult['is_new_record'],
                'kudos_awarded' => $rewardResult['kudos_awarded'],
		            'base_kudos' => $rewardResult['base_kudos'],
		            'multiplier' => $rewardResult['multiplier'],
	              'is_critical' => $rewardResult['is_critical'],
            ]
        );
    }
}
