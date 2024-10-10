<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Services\SchServiceCategory;
use Exception;
use Illuminate\Queue\QueueManager;

class CategoriesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function category()
    {
        return view('services.category');
    }
    /**
     * Summary of create department
     * Author: kaysar
     * Date: 08-Aug-2021
     * @param Request $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function createCategory(Request $data)
    {
        try {
            $validator = Validator::make($data->toArray(), [
                'name' => ['required', 'string', 'max:200', 'unique:sch_service_categories'],
            ]);

            if (!$validator->fails()) {
                $data['created_by'] = auth()->id();

                SchServiceCategory::create($data->all());
                return $this->apiResponse(['status' => '1', 'data' => ''], 200);
            }
            return $this->apiResponse(['status' => '500', 'data' => $validator->errors()], 400);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
        }
    }

    /**
     * Summary of update department
     * Author: kaysar
     * Date: 08-Aug-2021
     * @param Request $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCategory(Request $data)
    {
        try {
            $validator = Validator::make($data->toArray(), [
                'name' => ['required', 'string', 'max:200'],
            ]);
            if (!$validator->fails()) {
                SchServiceCategory::where('id', $data->id)->update([
                    'name' => $data->name,
                    'created_by' => $data->created_by,
                    'modified_by' => $data->modified_by,
                ]);
                return $this->apiResponse(['status' => '1', 'data' => ''], 200);
            }
            return $this->apiResponse(['status' => '500', 'data' => $validator->errors()], 400);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
        }
    }
    /**
     * Summary of delete Department
     * Author: Kaysar
     * Date: 8-Aug-2021
     * @param Request $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCategory(Request $data)
    {
        try {

            $rtr = SchServiceCategory::where('id', $data->id)->delete();
            return $this->apiResponse(['status' => '1', 'data' => $rtr], 200);
        } catch (Exception $ex) {
            return $this->apiResponse(['status' => '501', 'data' => $ex], 400);
        }
    }
    /**
     * Summary of get brandepartment list
     * Author: Kaysar
     * Date: 8-Aug-2021
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategorytList()
    {
        try {
            $data = SchServiceCategory::leftJoin('sch_services', 'sch_service_categories.id', '=', 'sch_services.sch_service_category_id')
                ->selectRaw('sch_service_categories.id,
             sch_service_categories.name,
             count(sch_services.id) as total_service')
                ->groupBy(
                    'sch_service_categories.id',
                    'sch_service_categories.name'
                )->get();
            return $this->apiResponse(['status' => '1', 'data' => $data], 200);
        } catch (QueueManager $qx) {
            return $this->apiResponse(['status' => '403', 'data' => $qx], 400);
        }
    }
}
