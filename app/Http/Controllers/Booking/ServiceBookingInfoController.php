<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Repository\Booking\BookingRepository;
use ErrorException;
use Exception;
use Illuminate\Http\Request;

class ServiceBookingInfoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function bookingInfo()
    {
        return view('booking.service-booking-info');
    }

    public function getServiceBookingInfo(Request $request)
    {
        try {
            $booking = new BookingRepository();
            $data =  $booking->getBookingInfo($request->dateFrom, $request->dateTo, $request->bookingId, $request->employeeId, $request->customerId, $request->serviceStatus);
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '403', 'data' => $ex], 400);
        }
    }

    public function changeServiceBookingStatus(Request $request)
    {
       try {
            $booking = new BookingRepository();
            $data =  $booking->ChangeBookingStatus($request->id, $request->status,$request->email_notify);
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        }catch (ErrorException $ex) {
            return $this->apiResponse(['status' => '-501', 'data' => $ex->getMessage()], 400);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '403', 'data' => $ex], 400);
        }
    }
}
