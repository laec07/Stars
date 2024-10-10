<?php

namespace App\Http\Repository\Dashboard;

use App\Enums\ServiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Repository\Settings\SettingsRepository;
use App\Models\Booking\SchServiceBooking;
use App\Models\Employee\SchEmployee;
use App\Models\Services\SchServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{

    public function getBookingStatus()
    {
        $br = new Controller();
        $today = new Carbon();
        $totalBooking = SchServiceBooking::UserWiseServiceBooking()
            ->whereIn('sch_service_bookings.cmn_branch_id', $br->getUserBranch()->pluck('cmn_branch_id'))
            ->selectRaw(
                'sch_service_bookings.status as status,
                count(sch_service_bookings.status) as serviceCount'
            )->groupBy('sch_service_bookings.status')->get();
        $todayBooking = SchServiceBooking::UserWiseServiceBooking()
            ->where('sch_service_bookings.date', $today->toDateString())
            ->whereIn('sch_service_bookings.cmn_branch_id', $br->getUserBranch()->pluck('cmn_branch_id'))
            ->selectRaw(
                'sch_service_bookings.status as status,
                count(sch_service_bookings.status) as serviceCount'
            )->groupBy('sch_service_bookings.status')->get();
        $rtrData = [
            'totalBooking' => $totalBooking,
            'todayBooking' => $todayBooking
        ];
        return  $rtrData;
    }

    public function getIncomeAndOtherStatistics()
    {
        $br = new Controller();
        $today = new Carbon();
        $todayPaidAndDue = SchServiceBooking::UserWiseServiceBooking()
            ->where('sch_service_bookings.date', $today->toDateString())
            ->whereIn('sch_service_bookings.cmn_branch_id', $br->getUserBranch()->pluck('cmn_branch_id'))
            ->selectRaw(
                'sch_service_bookings.payment_status,
            sum(sch_service_bookings.paid_amount) as paid_amount,
            sum(sch_service_bookings.service_amount) as service_amount'
            )->groupBy('sch_service_bookings.payment_status')->get();

        $todayPaidBy = SchServiceBooking::UserWiseServiceBooking()
            ->join('cmn_payment_types', 'sch_service_bookings.cmn_payment_type_id', '=', 'cmn_payment_types.id')
            ->whereIn('sch_service_bookings.cmn_branch_id', $br->getUserBranch()->pluck('cmn_branch_id'))
            ->where('sch_service_bookings.date', $today->toDateString())
            ->selectRaw(
                'cmn_payment_types.type,
            cmn_payment_types.name as PaymentBy,
            sum(sch_service_bookings.paid_amount) as paid_amount'
            )->groupBy('cmn_payment_types.name', 'cmn_payment_types.type')->get();

        return ['todayPaidAndDue' => $todayPaidAndDue, 'todayPaidBy' => $todayPaidBy];
    }

    /**
     * duration 1=today,2=last month
     * serviceStatus based on service status
     */
    public function getBookingInfo($serviceStatus, $duration)
    {
        $br = new Controller();
        $today = new Carbon();
        $services =  SchServiceBooking::UserWiseServiceBooking()
            ->join('sch_services', 'sch_service_bookings.sch_service_id', '=', 'sch_services.id')
            ->join('cmn_customers', 'sch_service_bookings.cmn_customer_id', '=', 'cmn_customers.id')
            ->join('cmn_branches', 'sch_service_bookings.cmn_branch_id', '=', 'cmn_branches.id')
            ->join('sch_employees', 'sch_service_bookings.sch_employee_id', '=', 'sch_employees.id')
            ->whereIn('sch_service_bookings.cmn_branch_id', $br->getUserBranch()->pluck('cmn_branch_id'));
        if ($duration == 2) {
            $startDay = new Carbon();
            $startDay = $startDay->subDays(30);
            $services = $services->where('sch_service_bookings.date', '>=', $startDay->toDateString())
                ->where('sch_service_bookings.date', '<=', $today->toDateString());
        } else {
            $services = $services->where('sch_service_bookings.date', $today->toDateString());
        }

        if ($serviceStatus != null && $serviceStatus != "") {
            $services = $services->where('sch_service_bookings.status', '=', $serviceStatus);
        } else {
            $services = $services->where('sch_service_bookings.status', '!=', ServiceStatus::Done);
        }
        $services = $services->selectRaw(
            'sch_service_bookings.id,
            sch_service_bookings.status,
            cmn_customers.full_name as customer,
            cmn_customers.phone_no as customer_phone_no,
            cmn_branches.name as branch,
            sch_employees.full_name as employee,
            sch_services.title as service,
            sch_service_bookings.date,
            sch_service_bookings.start_time,
            sch_service_bookings.remarks,
            sch_service_bookings.service_amount-sch_service_bookings.paid_amount as due'
        )->orderByRaw('sch_service_bookings.date desc, start_time desc')->get();
        return $services;
    }

    public function getTopServices()
    {
        $br = new Controller();
        $data = SchServiceBooking::UserWiseServiceBooking()->join('sch_services', 'sch_service_bookings.sch_service_id', '=', 'sch_services.id')
            ->whereIn('sch_service_bookings.cmn_branch_id', $br->getUserBranch()->pluck('cmn_branch_id'))
            ->selectRaw('sch_service_id,sch_services.title,count(sch_service_bookings.sch_service_id) as service_count')
            ->groupBy('sch_service_id', 'sch_services.title')
            ->orderByRaw('service_count desc')->take(10)->get();
        return $data;
    }

    public function getCustomerWiseBookingStatus($userId)
    {
        $bookingStatus = SchServiceBooking::join('cmn_customers', 'sch_service_bookings.cmn_customer_id', '=', 'cmn_customers.id')
            ->where('cmn_customers.user_id', $userId)
            ->selectRaw(
                'sch_service_bookings.status as status,
                count(*) as serviceCount'
            )->groupBy('sch_service_bookings.status')->get();

        return  $bookingStatus;
    }

    public function getLastBooking($numOfRecord, $userId)
    {
        $data =  SchServiceBooking::join('sch_services', 'sch_service_bookings.sch_service_id', '=', 'sch_services.id')
            ->join('sch_employees', 'sch_service_bookings.sch_employee_id', '=', 'sch_employees.id')
            ->join('cmn_customers', 'sch_service_bookings.cmn_customer_id', '=', 'cmn_customers.id')
            ->join('cmn_branches', 'sch_service_bookings.cmn_branch_id', '=', 'cmn_branches.id')
            ->where('cmn_customers.user_id', $userId)
            ->selectRaw(
                'sch_service_bookings.id,
                sch_service_bookings.status,
                sch_employees.full_name as employee,
                cmn_branches.name as branch,
                sch_services.title as service,
                sch_service_bookings.date,
                sch_service_bookings.start_time,
                sch_service_bookings.end_time,
                sch_service_bookings.remarks,
                sch_service_bookings.service_amount-sch_service_bookings.paid_amount as due'
            )->orderBy('sch_service_bookings.date', 'desc')
            ->orderBy('sch_service_bookings.start_time', 'desc')->take($numOfRecord)->get();
        return $data;
    }

    public function getAllBookingExceptDone($userId)
    {
        $data =  SchServiceBooking::join('sch_services', 'sch_service_bookings.sch_service_id', '=', 'sch_services.id')
            ->join('sch_employees', 'sch_service_bookings.sch_employee_id', '=', 'sch_employees.id')
            ->join('cmn_customers', 'sch_service_bookings.cmn_customer_id', '=', 'cmn_customers.id')
            ->join('cmn_branches', 'sch_service_bookings.cmn_branch_id', '=', 'cmn_branches.id')
            ->where('cmn_customers.user_id', $userId)
            ->where('sch_service_bookings.status', '!=', ServiceStatus::Done)
            ->selectRaw(
                'sch_service_bookings.id,
                sch_service_bookings.status,
                sch_employees.full_name as employee,
                cmn_branches.name as branch,
                sch_services.title as service,
                sch_service_bookings.date,
                sch_service_bookings.start_time,
                sch_service_bookings.end_time,
                sch_service_bookings.remarks,
                sch_service_bookings.service_amount-sch_service_bookings.paid_amount as due'
            )->orderBy('sch_service_bookings.date', 'desc')
            ->orderBy('sch_service_bookings.start_time', 'desc')->get();
        return $data;
    }

    public function getDoneBooking($userId)
    {
        $stRepo = new SettingsRepository();
        $currency = $stRepo->cmnCurrency();
        $data =  SchServiceBooking::join('sch_services', 'sch_service_bookings.sch_service_id', '=', 'sch_services.id')
            ->join('sch_employees', 'sch_service_bookings.sch_employee_id', '=', 'sch_employees.id')
            ->join('cmn_customers', 'sch_service_bookings.cmn_customer_id', '=', 'cmn_customers.id')
            ->join('cmn_branches', 'sch_service_bookings.cmn_branch_id', '=', 'cmn_branches.id')
            ->where('cmn_customers.user_id', $userId)
            ->where('sch_service_bookings.status', ServiceStatus::Done)
            ->selectRaw(
                'sch_service_bookings.id,
                sch_service_bookings.status,
                sch_employees.full_name as employee,
                cmn_branches.name as branch,
                sch_services.title as service,
                sch_service_bookings.date,
                sch_service_bookings.start_time,
                sch_service_bookings.end_time,
                sch_service_bookings.remarks,
                sch_service_bookings.service_amount-sch_service_bookings.paid_amount as due'
            )->addSelect(DB::raw("'$currency' as currency"))->orderBy('sch_service_bookings.date', 'desc')
            ->orderBy('sch_service_bookings.start_time', 'desc')->get();
        return $data;
    }

    public function getWebsiteServiceSummary()
    {
        return [
            'totalEmloyee' => SchEmployee::where('status', '!=', 3)->count(),
            'totalService' => SchServices::count(),
            'SatiffiedClient' => SchServiceBooking::selectRaw('count(*) as total')->groupBy('cmn_customer_id')->get()->count('total'),
            'DoneService' => SchServiceBooking::where('status', 4)->count()
        ];
    }
}
