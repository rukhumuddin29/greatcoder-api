<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    use \App\Traits\LogsActivity;

    /**
     * Wrap database operations in a transaction
     */
    protected function transactional(callable $callback)
    {
        DB::beginTransaction();
        try {
            $result = $callback();
            DB::commit();
            return $result;
        }
        catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
