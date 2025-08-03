<?php
namespace App\Http\Repository\Patient;


use App\Models\Patient\CmnPatient;

class PatientRepository{

    public function getPatientDropDown(){
        return CmnPatient::select('id','full_name as name','phone_no')->where('status', 1)->orderBy('id','DESC')->get();
    }
}