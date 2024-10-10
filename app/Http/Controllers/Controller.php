<?php

namespace App\Http\Controllers;

use App\Models\UserManagement\SecUserBranch;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Settings\CmnBranch;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function apiResponse($data, $responseCode = 200)
    {
        return response()->json($data, $responseCode);
    }

    public function getUserBranch()
    {
        $userBranch = SecUserBranch::where('user_id', Auth()->id())->select('cmn_branch_id')->get();
        if ($userBranch->count([0]) > 0) {
            return $userBranch;
        } else {
            return CmnBranch::select('id as cmn_branch_id')->get();
        }
    }
}
