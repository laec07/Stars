<?php
$monthOf = new \DateTime($input['year'].'-'.$input['month'].'-01');
$totalBasic = 0;
$totalService = 0;
$totalCommission = 0;
$totalAddition = 0;
$totalSalary = 0;
$totalDeduction = 0;
$totalNetPay = 0;
?>
<style type="text/css">
    .w-100{
        width: 100%;
    }
    .mt-20{
        margin-top: 20px;
    }
    .text-center{
        text-align: center;
    }
    #report-head{
        font-size: 30px;
    }
    .head{
        font-size: 26px;
    }
    .head > span{
        font-weight: bold;
    }
    .font-bold{
        font-weight: bold;
    }
</style>
<table border="0" class="w-100 text-center">
    <thead>
        <tr>
            <th id="#report-head">{{$company_info->name}}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{$company_info->address}}</td>
        </tr>
        <tr>
            <td>Slalary Sheet</td>
        </tr>
    </tbody>
</table>
<h4><span>Month of {{$monthOf->format('F-Y')}}</span></h4>
@foreach($salaries as $keyS => $salaryBranch)
<h4 class="mt-20"><span>Branch: </span>{{$salaryBranch[0]->employee->branch->name}}</h4>
<table border="1" cellspacing="0" cellpadding="3" class="w-100 mt-20">
    <thead>
        <tr>
            <th>A</th>
            <th>B</th>
            <th>C</th>
            <th>D</th>
            <th>E</th>
            <th>F</th>
            <th>G</th>
            <th>H</th>
            <th>I</th>
            <th>J</th>
            <th>K</th>
        </tr>
        <tr>
            <th>Employee ID</th>
            <th>Name</th>
            <th>Basic Salary</th>
            <th>Total service</th>
            <th>%</th>
            <th>Commission</th>
            <th>Addition</th>
            <th>Total (C+F+G)</th>
            <th>Deduction</th>
            <th>Net Pay (H-I)</th>
            <th>Signature</th>
        </tr>
    </thead>
    <tbody>
        @foreach($salaryBranch as $key => $value)
        <?php
        $totalBasic += $value->basic_salary;
        $totalService += $value->total_service_amount;
        $totalCommission += $value->commission_amount;
        $totalAddition += $value->addition;
        $totalSalary += $value->total_salary;
        $totalDeduction += $value->deduction;
        $totalNetPay += $value->netpay;
        ?>
        <tr>
            <td>{{$value->employee->employee_id}}</td>
            <td>{{$value->employee->full_name}}</td>
            <td>{{$value->basic_salary}}</td>
            <td>{{$value->total_service_amount}}</td>
            <td>{{$value->commission}}</td>
            <td>{{$value->commission_amount}}</td>
            <td>{{$value->addition}}</td>
            <td>{{$value->total_salary}}</td>
            <td>{{$value->deduction}}</td>
            <td>{{$value->netpay}}</td>
            <td></td>
        </tr>
        @endforeach
        <tr>
            <td class="font-bold">Total</td>
            <td></td>
            <td class="font-bold">{{$totalBasic}}</td>
            <td class="font-bold">{{$totalService}}</td>
            <td></td>
            <td class="font-bold">{{$totalCommission}}</td>
            <td class="font-bold">{{$totalAddition}}</td>
            <td class="font-bold">{{$totalSalary}}</td>
            <td class="font-bold">{{$totalDeduction}}</td>
            <td class="font-bold">{{$totalNetPay}}</td>
            <td></td>
        </tr>
    </tbody>
</table>
@endforeach
