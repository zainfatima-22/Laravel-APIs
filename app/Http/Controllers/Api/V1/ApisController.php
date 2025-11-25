<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class ApisController extends Controller
{
    use ApiResponses;

    /**
     * Check if a relationship should be included based on the 'include' query parameter.
     */
    public function include(string $relationship, ?Request $request = null): bool
    {
        $request = $request ?? request();
        $param = $request->get('include');

        if (!isset($param)) {
            return false;
        }

        $includeValues = explode(',', strtolower($param));

        return in_array(strtolower($relationship), $includeValues, true);
    }
}
