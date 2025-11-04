<?php

namespace App\Http\Controllers\Patient;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Repository\UtilityRepository;
use App\Models\Patient\CmnPatient;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function patient()
    {   
        return view('patient.patient');
    }

    public function patientStore(Request $data)
{
    try {
        $validator = Validator::make($data->all(), [ 
            'full_name' => 'required|string',
            'email' => ['required', 'string', 'unique:cmn_patients,email'],
            'phone_no' => ['required', 'string', 'unique:cmn_patients,phone_no', 'max:20'],

        ]);


        $rutaArchivo = $data->image_url; 
                if ($rutaArchivo != null) {
                    $rutaArchivo = UtilityRepository::saveFile($rutaArchivo, ['image/png', 'pdf/pdf', 'image/jpg', 'image/jpeg']);
                }

        if (!$validator->fails()) {
            $creatorId =  auth()->id();
            $data['user_id'] =  $data['user_id']=UtilityRepository::emptyToNull($data->user_id);               
            //create new user

                $userId =   CmnPatient::create(
                    [
                'full_name' => $data->full_name,
                'phone_no' => $data->phone_no,
                'email' => $data->email,
                'dob' => $data->dob,
                'treated' => $data->treated,
                'has_study' => $data->has_study,
                'archivo' => $rutaArchivo ?? null,
                'tax_number' => $data->tax_number,
                'state' => $data->state,
                'email_verified_at' => Carbon::now(),
                'is_sys_adm' => 0,
                'status' => 1,
                "created_by" => $creatorId
            ]
        );

        $data['user_id'] = $userId->id;

        
        return $this->apiResponse(['status' => '1', 'data' => ['cmn_patient_id' => $userId->id]], 200);
        }
         return $this->apiResponse(['status' => '500', 'data' => $validator->errors()], 400);
    } catch (Exception $ex) {
        return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
    }
}

    public function patientUpdate(Request $data)
    {

        try {
            $validator = Validator::make($data->all(), [
            
                'full_name' => ['required', 'string'],
               'email' => ['required', 'string', 'unique:cmn_patients,email,' . $data->user_id . ',id'],
                'phone_no' => ['required', 'string', 'unique:cmn_patients,phone_no,' . $data->user_id . ',id', 'max:20']
            ]);
    
            $rutaArchivo = $data->image_url; 
                if ($rutaArchivo != null) {
                    $rutaArchivo = UtilityRepository::saveFile($rutaArchivo, ['image/png', 'pdf/pdf', 'image/jpg', 'image/jpeg']);
                }
                
             if (!$validator->fails()) {
                $creatorId =  auth()->id();
                $data['user_id']=UtilityRepository::emptyToNull($data->user_id);
               $patient = CmnPatient::find($data->user_id);

                if ($patient) {
                    $patient->update([
                        'full_name' => $data->full_name,
                        'phone_no' => $data->phone_no,
                        'email' => $data->email,
                        'dob' => $data->dob,
                        'treated' => $data->treated,
                        'has_study' => $data->has_study,
                        'archivo' => $rutaArchivo ?? $patient->archivo, // mantiene el anterior si no se envía uno nuevo
                        'tax_number' => $data->tax_number,
                        'state' => $data->state,
                        'is_sys_adm' => 0,
                        'status' => 1,
                        'updated_by' => $creatorId // o 'updated_by' si usas ese campo
                    ]);
                
                 $data['user_id'] = $patient->id;

                    return $this->apiResponse(['status' => '1', 'data' => ''], 200);
                }else {
                    return $this->apiResponse(['status' => '0', 'data' => 'No encontrado'], 400);
                } 
               
                
            }
            return $this->apiResponse(['status' => '500', 'data' => $validator->errors()], 400);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
        }
    }


    public function patientDelete(Request $data)
    {
        try {
            $rtr = CmnPatient::where('id', $data->id)->delete();
            return $this->apiResponse(['status' => '1', 'data' => $rtr], 200);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
        }
    }

    public function getAllPatient()
    {
        try {
            $data = CmnPatient::select('*')
             ->where('status', 1)
            ->get();
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }
}
