<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Repository\Booking\BookingRepository;
use App\Http\Repository\Dashboard\DashboardRepository;
use App\Models\Booking\SchServiceBooking;
use App\Models\Settings\CmnBranch;
use Carbon\Carbon;
use ErrorException;
use Exception;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function home()
    {
        return view('dashboard.dashboard');
    }


    public function getDashboardCommonData()
    {
       try {
            $dashboardRepo = new DashboardRepository();
            $rtrData = [
                'bookingStatus' => $dashboardRepo->getBookingStatus(),
                'incomAndOtherStatistics' => $dashboardRepo->getIncomeAndOtherStatistics(),
                'topService' => $dashboardRepo->getTopServices()
            ];
            return $this->apiResponse(['status' => '1', 'data' => $rtrData], 200);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '403', 'data' => $ex], 400);
        }
    }
    public function getBookingInfo(Request $request)
    {
        try {
            $dashboardRepo = new DashboardRepository();
            $rtrData = $dashboardRepo->getBookingInfo($request->serviceStatus, $request->duration);
            return $this->apiResponse(['status' => '1', 'data' => $rtrData], 200);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '403', 'data' => $ex], 400);
        }
    }

    public function changeBookingStatus(Request $request)
    {
        try {
            $bookingRepo = new BookingRepository();
            $rtrData = $bookingRepo->ChangeBookingStatusAndReturnBookingData($request->id,$request->status,1);
            return $this->apiResponse(['status' => '1', 'data' => $rtrData], 200);
        }catch (ErrorException $ex) {
            return $this->apiResponse(['status' => '-501', 'data' => $ex->getMessage()], 400);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
        }
    }
}
