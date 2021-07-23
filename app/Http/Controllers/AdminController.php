<?php

namespace App\Http\Controllers;

use App\Models\DeliveryInterval;
use App\Models\DeliveryTime;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JetBrains\PhpStorm\Pure;
use ResponseHelper;
use Stripe\BaseStripeClient;
use Stripe\StripeClient;

class AdminController extends Controller
{



    public function createInterval(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'zip_code' => 'required',
                'city' => 'required',
                'times' => 'required'
            ]);
//        dd($request->times);
        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }
        try {
            // first insert the first zip code and the city
            $deliveryInterval = DeliveryInterval::create($validator->validate());
            if ($deliveryInterval) {
                // then loop over the times and create each
                foreach ($request->times as $item) {
                    // box content id should be already in the request
                    DeliveryTime::create([
                        'description' => $item['description'],
                        'delivery_interval_id' => $deliveryInterval->id,
                        'time_from' => $item['time_from'],
                        'time_to' => $item['time_to'],
                    ]);
                }
                return ResponseHelper::successResponse($deliveryInterval);
            } else {
                return ResponseHelper::errorResponse('cannot create interval', 500);
            }
        } catch (QueryException | ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    public function deleteInterval($interval_id): JsonResponse
    {
        // delete the interval
        $interval = DeliveryInterval::findOrFail($interval_id);
        $times = DeliveryTime::where('delivery_interval_id', '=', $interval_id);

        return $interval->delete() && $times->delete() ?
            ResponseHelper::successResponse('interval deleted successfully') :
            ResponseHelper::errorResponse('interval cannot be deleted', 400);

    }

    public function sendNotification($notificationTitle, $notificationBody, $fcmToken): JsonResponse
    {
        //API Url
        $url = 'https://fcm.googleapis.com/fcm/send';
        $authServerKey = env('FIREBASE_SERVER_KEY');

        if (!empty($fcmToken) && !empty($notificationTitle) && !empty($notificationBody)) {
            $msg = array
            (
                'title' => "$notificationTitle",
                'body' => "$notificationBody",
                'priority' => 'high',
                'sound' => 'default',
                'vibrate' => 1,
            );
            $fields = array
            (
                'to' => $fcmToken,
                'notification' => $msg,
            );
            $headers = array('Content-Type: application/json', 'Authorization: key=' . $authServerKey);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            if ($result === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }
            curl_close($ch);
            return ResponseHelper::successResponse('Notification has been sent');
        } else {
            return ResponseHelper::errorResponse('Not all info is provided', 400);
        }
    }

    public function editNotice(Request $request): JsonResponse
    {
        try {
            $notice = Notice::findOrFail(1);
            $notice->is_hidden = $request->is_hidden;
            $notice->title = $request->title;
            $notice->description = $request->description;
            $notice->save() ?
                ResponseHelper::successResponse('notice updated successfully') :
                ResponseHelper::errorResponse('notice cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('no notices were found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    public function getNotice(): JsonResponse
    {
        try {
            return ResponseHelper::successResponse(Notice::findOrFail(1));
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('no notices were found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }
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


    #[Pure] public function isDeliveryMonthCurrent($deliveryDay): bool
    {
        $currentDate = date('d.m.Y');
        // 21 > 20 true !true false
        return (strtotime($this->getDeliveryDate($deliveryDay)) >= strtotime($currentDate));
    }

    /**
     * @return JsonResponse
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getAllUsers(): JsonResponse
    {
        $users = User::select('*')->with(['addresses', 'subscriptions', 'subscriptions.times', 'subscriptions.times.intervals', 'subscriptions.boxContents', 'subscriptions.boxContents.box', 'subscriptions.boxContents.boxHasProducts', 'subscriptions.boxContents.boxHasProducts.product'])->get();
        $usersToSend = [];
        $stripe = new StripeClient(
            env('STRIPE_SECRET')
        );
        $allSubscriptions = $stripe->subscriptions->all(['limit' => 10]);

        for ($i = 20; $allSubscriptions->has_more; $i = $i + 10) {
            $allSubscriptions = $stripe->subscriptions->all(['limit' => $i]);
        }
        foreach ($users as $user) {

            $userToSend = [];
            $stripe = [];
            if ($user->subscriptions !== null) {
                foreach ($allSubscriptions->data as $stripeUser) {
                    if ($user->subscriptions->stripe_sub_id === $stripeUser->id) {
                        $stripe = $stripeUser;
                        $user->subscriptions->times->delveiry_date = $this->isDeliveryMonthCurrent($user->subscriptions->times->description) ? $this->getDeliveryDate($user->subscriptions->times->description) : $this->getnextMonthDeliveryDate($user->subscriptions->times->description);
                    }
                }
            } else {
                $stripe = null;
            }
            $userToSend = [
                'user' => $user,
                'stripe' => $stripe,
            ];
            array_push($usersToSend, $userToSend);
        }
        return ResponseHelper::successResponse($usersToSend);
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getProductionPizzas(): JsonResponse
    {
        $users = $this->getAllUsers()->original['response'];
        $sortByDate = [];
        foreach ($users as $key => $user) {
            // check if there is even a subscription
            if ($user['stripe'] !== null && $user['stripe'] !== []) {
                // check if the user has a valid subscription
                if ($user['stripe']->status === 'active' && $user['stripe']->pause_collection === null) {
                    $pizzas = $user['user']->subscriptions->boxContents->boxHasProducts;
                    $pizzasCount = array_count_values(array_map(function ($item) {
                        return $item->product->name;
                    }, json_decode($pizzas)));
                    array_push($sortByDate, $pizzasCount);
                }
            }
        }
        $pizzasNames = [];
        foreach ($sortByDate as $item) {
            foreach ($item as $subKey => $subItem) {
                $counter = $subItem;
                while ($counter !== 0) {
                    $pizzasNames = array_merge($pizzasNames, [$subKey]);
                    $counter--;
                }
            }

        }
        return ResponseHelper::successResponse(array_count_values($pizzasNames));
    }

    public function sendReminderEmail(): array
    {
        // get all active users
        $users = $this->getAllUsers()->original['response'];
        $usersWithReminderEmailFlag = [];
        foreach ($users as $user) {
            // check if there is even a subscription
            if ($user['stripe'] !== null && $user['stripe'] !== []) {
                // check if the user has a valid subscription and the user did not already cancel the subscription
                if ($user['stripe']->status === 'active' && $user['stripe']->cancel_at_period_end !== true && $user['stripe']->pause_collection === null) {
                    // get current date
                    $currentDate = date('d.m.Y');
                    // send the reminder email if the the current date is 5 days before the delivery date
                    $pizzas = $user['user']->subscriptions->boxContents->boxHasProducts;

                    $pizzasCount = array_count_values(array_map(function ($item) {
                        return $item->product->name;
                    }, json_decode($pizzas)));
                    $pizzasCountString = '';
                    foreach ($pizzasCount as $key => $item) {
                        $pizzasCountString = $pizzasCountString . ' ' . $item . 'x' . $key . ',';
                    }

                    if (strtotime($currentDate . ' + 3 days') === strtotime($user['user']->subscriptions->times->delveiry_date)) {
                        $to_name = 'Diomio';
                        $to_email = $user['user']->email;
                        $data = array('date' => $user['user']->subscriptions->times->delveiry_date, 'time_from' => date('H:i', strtotime($user['user']->subscriptions->times->time_from)), 'time_to' => date('H:i', strtotime($user['user']->subscriptions->times->time_to)));
                        Mail::send('emails.reminderemail', $data, function ($message) use ($to_name, $to_email) {
                            $message->to($to_email, $to_name)->subject('Erinnerung an dein Abo');
                            $message->from('noreply@diomio.ch', 'ABO MIO');
                        });
                        // send mobile notification also
                        $notificationTitle = 'Erinnerung an dein Abo';
                        $notificationBody = 'Zur Erinnerung: Am ' . $user['user']->subscriptions->times->delveiry_date . ' zwischen ' . date('H:i', strtotime($user['user']->subscriptions->times->time_from)) . ' - ' . date('H:i', strtotime($user['user']->subscriptions->times->time_to)) . ' treffen die Pizzas bei dir
                        vor der Haustüre ein.';

                        $this->sendNotification($notificationTitle, $notificationBody, $user['user']->fcm_token);
                    }
                    array_push($usersWithReminderEmailFlag, [
                        'email' => $user['user']->email,
                        'date' => $user['user']->subscriptions->times->delveiry_date,
                        'time_from' => $user['user']->subscriptions->times->time_from,
                        'time_to' => $user['user']->subscriptions->times->time_to,
                        'pizzas' => $pizzasCountString,
                        'send_email?' => strtotime($currentDate . ' + 5 days') === strtotime($user['user']->subscriptions->times->delveiry_date)
                    ]);
                }
            }
        }

        return $usersWithReminderEmailFlag;
    }
}
