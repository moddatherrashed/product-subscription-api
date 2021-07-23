<?php

namespace App\Http\Controllers;

use App\Models\DeliveryTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ResponseHelper;

class DeliveryTimeController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'description' => 'required|string',
                'delivery_interval_id' => 'required|integer',
                'time_from' => 'required|date_format:H:i',
                'time_to' => 'required|date_format:H:i',
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }

        try {
            $time = DeliveryTime::create($validator->validate());

            if ($time) {
                return ResponseHelper::successResponse($time);
            } else {
                return ResponseHelper::errorResponse('cannot create delivery time', 500);
            }
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get(Request $request): JsonResponse
    {
        $germanDays = [
            'monday' => 'Montag',
            'tuesday' => 'Dienstag',
            'wednesday' => 'Mittwoch',
            'thursday' => 'Donnerstag',
            'friday' => 'Freitag',
            'saturday' => 'Samstag',
            'sunday' => 'Sonntag'
        ];

        $validator = Validator::make($request->all(),
            [
                'delivery_interval_id' => 'required|integer',
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }
        try {
            // get the
            $deliveryTimesFromDatabase = DeliveryTime::where('delivery_interval_id', $request->delivery_interval_id)->get();
            $timesToSend = [];
            $deliveryDates = array();
            foreach ($deliveryTimesFromDatabase as $key => $time) {
                // TODO: very bad implemented, restructure the code when there is time
                $deliveryDates[$key] = $this->isDeliveryMonthCurrent($time['description']) ? $this->getDeliveryDate($time['description']) : $this->getnextMonthDeliveryDate($time['description']);
            }
            foreach ($deliveryTimesFromDatabase as $key => $time) {
                $timesToSend[$key]['id'] = $time['id'];
                $timesToSend[$key]['day'] = 'Letzten ' . $germanDays[$time['description']] . ' des Monats';
                $timesToSend[$key]['time_from'] = $time['time_from'];
                $timesToSend[$key]['time_to'] = $time['time_to'];
                $timesToSend[$key]['first_date'] = $this->isDeliveryMonthCurrent($deliveryTimesFromDatabase) ? $this->getDeliveryDate($time['description']) : $this->getnextMonthDeliveryDate($time['description']);
            }

            return ResponseHelper::successResponse($timesToSend);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('delivery time not found', 404);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        try {
            $time = DeliveryTime::findOrFail($id);
            return $time->delete() ?
                ResponseHelper::successResponse('delivery time deleted successfully') :
                ResponseHelper::errorResponse('delivery time cannot be deleted', 400);

        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('delivery time not found', 404);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $time = DeliveryTime::findOrFail($id);
            $requestBody = $request->all();
            foreach ($requestBody as $key => $value) {
                $time->$key = $value;
            }
            return $time->save() ?
                ResponseHelper::successResponse('delivery time updated successfully') :
                ResponseHelper::errorResponse('delivery time cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('delivery time not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    //TODO: these methods should be in a helper (no time for now)
    public function getDeliveryDate($deliveryDay): string
    {
        $day = $deliveryDay;
        $currentMonthNumber = date('m');
        $currentYear = date('Y');
        $currentMonth = date("F", strtotime('00-' . $currentMonthNumber . '-01'));
        return date('d.m.Y', strtotime('last ' . $day . ' of ' . $currentMonth . ' ' . $currentYear, strtotime($currentMonth)));
    }

    public function getnextMonthDeliveryDate($deliveryDay): string
    {
        $day = $deliveryDay;
        $nextMonthNumber = strval(intval(date('m')) + 1);
        $currentYear = date('Y');
        $nextMonth = date("F", strtotime('00-' . $nextMonthNumber . '-01'));
        return date('d.m.Y', strtotime('last ' . $day . ' of ' . $nextMonth . ' ' . $currentYear, strtotime($nextMonth)));
    }


    public function isDeliveryMonthCurrent($deliveryDay): bool
    {

        $currentMonthNumber = date('m');
        $currentYear = date('Y');
        $currentMonth = date("F", strtotime('00-' . $currentMonthNumber . '-01'));
        $minDeliveryDate = '20.' . $currentMonth . '.' . $currentYear;
        $currentDate = date('d.m.Y');
        //check if the current date less than the (min delivery date 20est of the month)
        // 21 > 20 true !true false
        return !(strtotime($currentDate) > strtotime(date('d.m.Y', strtotime($minDeliveryDate))));
    }
}
