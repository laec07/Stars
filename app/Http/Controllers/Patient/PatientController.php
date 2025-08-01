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
                'state' => $data->state,
                'email_verified_at' => Carbon::now(),
                'is_sys_adm' => 0,
                'status' => 1,
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
                'email' => ['required', 'string', 'unique:cmn_patients,email,' . $data->id . ',id'],
                'phone_no' => ['required', 'string', 'unique:cmn_patients,phone_no,' . $data->id . ',id', 'max:20']
            ]);
    
             if (!$validator->fails()) {
                //create new user
                $data['user_id']=UtilityRepository::emptyToNull($data->user_id);
                if ($data->user_id == 0) {
                    $userId =   User::create(
                        [
                    'name' => $data->full_name,
                    'username' => $data->phone_no,
                    'password' => Hash::make('12345678'),
                    'email' => $data->email,
                    'dob' => $data->dob,
                    'treated' => $data->treated,
                    'has_study' => $data->has_study,
                    'archivo' => $data->archhivo ?? null,
                    'state' => $data->state,
                    'email_verified_at' => Carbon:: now(),
                    'is_sys_adm' => 0,
                    'status' => 1,
                    'user_type' => UserType::WebsiteUser
                ]
            );
                 $data['user_id'] = $userId->id;

                    
                } else {
                    $savedUser = User::where('id', $data->user_id)->first();
                    if ($savedUser != null) {
                        $savedUser->email = $data->email;
                        $data->name = $data->full_name;
                        $savedUser->update();
                    }
                }
                $data['dob']=UtilityRepository::emptyToNull($data->dob);
                CmnPatient::where('id', $data->id)->update($data->toArray());
                return $this->apiResponse(['status' => '1', 'data' => ''], 200);
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
            $data = CmnPatient::select(
                '*'
            )->get();
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }
}
