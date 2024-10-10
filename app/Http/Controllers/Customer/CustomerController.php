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
                'email' => ['required', 'string', 'unique:cmn_customers,email'],
                'phone_no' => ['required', 'string', 'unique:cmn_customers,phone_no', 'max:20'],
                'street_address' => ['required', 'string']
            ]);
            if (!$validator->fails()) {
                $data['user_id'] =  $data['user_id']=UtilityRepository::emptyToNull($data->user_id);               
                //create new user
                if ($data->user_id == 0) {
                    $userId =   User::create(
                        [
                            'name' => $data->full_name,
                            'username' => $data->phone_no,
                            'password' => Hash::make('12345678'),
                            'email' => $data->email,
                            'email_verified_at' => Carbon::now(),
                            'is_sys_adm' => 0,
                            'status' => 1,
                            'user_type' => UserType::WebsiteUser
                        ]
                    );
                    $data['user_id'] = $userId->id;
                }

                $data['id'] = null;
                $data['created_at'] = auth()->id();
                $data['dob']=UtilityRepository::emptyToNull($data->dob);
                $rtr = CmnCustomer::create($data->all());
                return $this->apiResponse(['status' => '1', 'data' => ['cmn_customer_id' => $rtr->id]], 200);
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
                'street_address' => ['required', 'string']
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
                            'email_verified_at' => Carbon::now(),
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
                CmnCustomer::where('id', $data->id)->update($data->toArray());
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
