<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BdeScorecardService;
use Illuminate\Http\Request;
use Exception;

class BdeScorecardController extends Controller
{
    protected $scorecardService;

    public function __construct(BdeScorecardService $scorecardService)
    {
        $this->scorecardService = $scorecardService;
    }

    /**
     * Get ranked leaderboard of BDEs.
     */
    public function leaderboard(Request $request)
    {
        try {
            $period = $request->input('period', 'this_month');
            $leaderboard = $this->scorecardService->getLeaderboard($period);
            return $this->success($leaderboard);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get detailed scorecard for a single BDE.
     */
    public function show(Request $request, int $userId)
    {
        try {
            $period = $request->input('period', 'this_month');
            $scorecard = $this->scorecardService->getScorecard($userId, $period);
            return $this->success($scorecard);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get performance trend for a BDE.
     */
    public function trend(Request $request, int $userId)
    {
        try {
            $months = $request->input('months', 6);
            $trend = $this->scorecardService->getTrend($userId, (int)$months);
            return $this->success($trend);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
