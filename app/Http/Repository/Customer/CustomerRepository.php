<?php
namespace App\Http\Repository\Customer;

use App\Models\Customer\CmnCustomer;

class CustomerRepository{

    public function getCustomerDropDown(){
        return CmnCustomer::select('id','full_name as name','phone_no')->orderBy('id','DESC')->get();
    }
}