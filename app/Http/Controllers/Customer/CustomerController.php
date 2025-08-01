<?php

namespace App\Http\Controllers\Customer;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Repository\UtilityRepository;
use App\Models\Customer\CmnCustomer;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function customer()
    {   
        return view('customer.customer');
    }

    public function customerStore(Request $data)
    {
       
        try {
            $validator = Validator::make($data->all(), [
                'full_name' => ['required', 'string'],
               // 'email' => ['required', 'string', 'unique:cmn_customers,email'],
                'phone_no' => ['required', 'string', 'unique:cmn_customers,phone_no', 'max:20'],
                'street_address' => ['required', 'string'],
                'stre' => ['nullable', 'in:Yes,No'],
                'mosly' => ['nullable', 'in:Ligth,Moderate,Deep pressure'],
                'traumatic' => ['nullable', 'in:YES,NO']
            ]);
            if (!$validator->fails()) {
                $data['user_id'] =  $data['user_id']=UtilityRepository::emptyToNull($data->user_id);               

                    $userId =   CmnCustomer::create(
                        [
                            'full_name' =>  html_entity_decode($data->full_name),
                            'phone_no' => $data->phone_no,
                            'email' => $data->email,
                            'street_address' => html_entity_decode($data->street_address),
                            'dob' => $data->dob,
                            'Occupation' => html_entity_decode($data->Occupation),
                            'exercie' => html_entity_decode($data->exercie),
                            'hobbies' => html_entity_decode($data->hobbies),
                            'services' => html_entity_decode($data->services),
                            'ser'=>$data->ser,
                            'ses'=>$data->ses,
                            'medical' => html_entity_decode($data->medical),
                            'traumatic'=>$data->traumatic,
                            'ex' => html_entity_decode($data->ex),
                            'mosly'=>$data->mosly,
                            'stre'=>$data->stre,
                            'mos' => html_entity_decode($data->mos),
                            'li' => html_entity_decode($data->li),
                            'email_verified_at' => Carbon::now(),
                            'is_sys_adm' => 0,
                            'status' => 1,
                            'user_type' => UserType::WebsiteUser
                        ]
                    );
                    $data['user_id'] = $userId->id;

                return $this->apiResponse(['status' => '1', 'data' => ['cmn_customer_id' => $userId->id]], 200);
            }
            return $this->apiResponse(['status' => '500', 'data' => $validator->errors()], 400);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
        }
    }

    public function customerUpdate(Request $data)
    {
        try {
            $validator = Validator::make($data->all(), [
                'full_name' => ['required', 'string'],
               'email' => ['required', 'string', 'unique:cmn_customers,email,' . $data->id . ',id'],
                'phone_no' => ['required', 'string', 'unique:cmn_customers,phone_no,' . $data->id . ',id', 'max:20'],
                'street_address' => ['required', 'string'],
                'Occupation' => ['required', 'string']
            ]);

            if (!$validator->fails()) {
                //create new user
                $data['user_id']=UtilityRepository::emptyToNull($data->user_id);
                
                   CmnCustomer::where('id', $data->id)->update([
                        'full_name' => html_entity_decode($data->full_name),
                        'phone_no' => $data->phone_no,
                        'email' => $data->email,
                        'street_address' => html_entity_decode($data->street_address),
                        'dob' => $data->dob,
                        'Occupation' => html_entity_decode($data->Occupation),
                        'exercie' => html_entity_decode($data->exercie),
                        'hobbies' => html_entity_decode($data->hobbies),
                        'services' => html_entity_decode($data->services),
                        'ser' => $data->ser,
                        'ses' => $data->ses,
                        'medical' => html_entity_decode($data->medical),
                        'traumatic' => $data->traumatic,
                        'ex' => html_entity_decode($data->ex),
                        'mosly' => $data->mosly,
                        'stre' => $data->stre,
                        'mos' => html_entity_decode($data->mos),
                        'li' => html_entity_decode($data->li),
                    ]);
                $data['dob']=UtilityRepository::emptyToNull($data->dob);

                return $this->apiResponse(['status' => '1', 'data' => ''], 200);
            }
            return $this->apiResponse(['status' => '500', 'data' => $validator->errors()], 400);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
        }
    }

    public function customerDelete(Request $data)
    {
        try {
            $rtr = CmnCustomer::where('id', $data->id)->delete();
            return $this->apiResponse(['status' => '1', 'data' => $rtr], 200);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
        }
    }

    public function getAllCustomer()
    {
        try {
            $data = CmnCustomer::select(
                '*'
            )->get();
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }
}
