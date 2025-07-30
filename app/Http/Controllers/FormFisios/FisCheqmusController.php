<?php

namespace App\Http\Controllers\FormFisios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use App\Models\FormFisios\FisCheqmus;

class FisCheqmusController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Summary of muscle check 
     * Author: laestrada
     * Date: 09-mar-2025
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\FormFisios\View\View
     */
    public function formCheqMusc(Request $request){
        return view('forms_fisios.cheqmus');

    }

    public function getAllformCheqMusc()
    {
        try {
            $data = FisCheqmus::join('cmn_customers', 'fis_cheqmus.csm_id', '=', 'cmn_customers.id')
            ->join('users', 'fis_cheqmus.user_id', '=', 'users.id')
            ->select(
                'fis_cheqmus.id',
                'fis_cheqmus.Fecha',
                'fis_cheqmus.diagnostico',
                'fis_cheqmus.personalizado1',
                'fis_cheqmus.personalizado2',
                'fis_cheqmus.personalizado3',
                'cmn_customers.full_name as customer_name',
                'users.name as name_user'
            )
            ->get();
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }

    public function createformCheqMusc()
    {
        dd("Hol mundo");
        try {
            $data = FisCheqmus::join('cmn_customers', 'fis_cheqmus.csm_id', '=', 'cmn_customers.id')
            ->join('users', 'fis_cheqmus.user_id', '=', 'users.id')
            ->select(
                'fis_cheqmus.id',
                'fis_cheqmus.Fecha',
                'fis_cheqmus.diagnostico',
                'fis_cheqmus.personalizado1',
                'fis_cheqmus.personalizado2',
                'fis_cheqmus.personalizado3',
                'cmn_customers.full_name as customer_name',
                'users.name as name_user'
            )
            ->get();
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }

    public function updateformCheqMusc()
    {
        try {
            $data = FisCheqmus::join('cmn_customers', 'fis_cheqmus.csm_id', '=', 'cmn_customers.id')
            ->join('users', 'fis_cheqmus.user_id', '=', 'users.id')
            ->select(
                'fis_cheqmus.id',
                'fis_cheqmus.Fecha',
                'fis_cheqmus.diagnostico',
                'fis_cheqmus.personalizado1',
                'fis_cheqmus.personalizado2',
                'fis_cheqmus.personalizado3',
                'cmn_customers.full_name as customer_name',
                'users.name as name_user'
            )
            ->get();
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }

    public function deleteformCheqMusc()
    {
        try {
            $data = FisCheqmus::join('cmn_customers', 'fis_cheqmus.csm_id', '=', 'cmn_customers.id')
            ->join('users', 'fis_cheqmus.user_id', '=', 'users.id')
            ->select(
                'fis_cheqmus.id',
                'fis_cheqmus.Fecha',
                'fis_cheqmus.diagnostico',
                'fis_cheqmus.personalizado1',
                'fis_cheqmus.personalizado2',
                'fis_cheqmus.personalizado3',
                'cmn_customers.full_name as customer_name',
                'users.name as name_user'
            )
            ->get();
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }

}
