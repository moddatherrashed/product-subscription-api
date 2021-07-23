<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use JetBrains\PhpStorm\Pure;
use PhpParser\Node\Expr\Cast\Object_;
use ResponseHelper;
use Stripe\Stripe;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;

class UserController extends Controller
{
    protected $jwt;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'email' => 'required|email|max:255',
                'password' => 'required',
            ]);
        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->validator->errors()->getMessages(), 400);
        }

        try {
            if (!$token = $this->jwt->attempt($request->only('email', 'password'))) {
                return ResponseHelper::errorResponse('email or password is wrong', 404);
            }
        } catch (TokenExpiredException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 401);

        } catch (TokenInvalidException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 401);

        } catch (JWTException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
        if (auth()->user()->role === 'user') {
            return ResponseHelper::successResponse(compact('token'));
        }
        return ResponseHelper::errorResponse('you are an admin please login in the admin dashboard', 401);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'firstname' => 'required',
                'lastname' => 'required',
                'email' => 'required|email|max:255|unique:users',
                'password' => 'required',
                'phone_number' => 'required',
                'role' => 'string',
                'app_version' => 'string',
                'platform' => 'string'
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }

        try {
            $user = User::create(array_merge(
                $validator->validated(),
                ['password' => Hash::make($request->input('password'))]
            ));
            if ($user) {
                return ResponseHelper::successResponse($user);
            } else {
                return ResponseHelper::errorResponse('cannot create user', 500);
            }
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function me()
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
        $userInfo = User::where('id', '=', auth()->user()->id)->with(['addresses', 'subscriptions', 'subscriptions.boxContents', 'subscriptions.times', 'subscriptions.boxContents.box', 'subscriptions.boxContents.boxHasProducts.product'])->get();
        if ($userInfo[0]) {
            if ($userInfo[0]['subscriptions']) {
                $userInfo[0]['subscriptions']['times']['day'] = $germanDays[$userInfo[0]['subscriptions']['times']['description']];
                $userInfo[0]['subscriptions']['times']['date'] = $this->isDeliveryMonthCurrent($userInfo[0]['subscriptions']['times']['description']) ? $this->getDeliveryDate($userInfo[0]['subscriptions']['times']['description']) : $this->getnextMonthDeliveryDate($userInfo[0]['subscriptions']['times']['description']);
//                $today = date('d.m.Y');
//                $deliveryDate = $this->getDeliveryDate($userInfo[0]['subscriptions']['times']['description']);
//                $diff = date("d.m.Y", strtotime("-2 days", strtotime(date("d.m.Y", strtotime($deliveryDate)))));
                $currentMonthNumber = date('m');
                $currentYear = date('Y');
                $currentMonth = date("F", strtotime('00-' . $currentMonthNumber . '-01'));
                $diff = '21.' . $currentMonth . '.' . $currentYear;
                $currentDate = date('d.m.Y');
                //check if the current date less than the (min delivery date 20est of the month)
                // 21 > 20 true !true false
//                return !(strtotime($currentDate) > strtotime(date('d.m.Y', strtotime($minDeliveryDate))));
                $userInfo[0]['subscriptions']['times']['is_edit_allowed'] = strtotime($currentDate) < strtotime($diff);
            }
        }
        return ResponseHelper::successResponse($userInfo);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function deleteUser($id): JsonResponse
    {
        try {
            $address = User::findOrFail($id);
            return $address->delete() ?
                ResponseHelper::successResponse('user deleted successfully') :
                ResponseHelper::errorResponse('user cannot be deleted', 400);

        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('user not found', 404);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateUserInfo(Request $request): JsonResponse
    {
        try {
            $user = User::findOrFail(auth()->user()->id);
            $requestBody = $request->all();
            foreach ($requestBody as $key => $value) {
                $user->$key = $value;
            }
            return $user->save() ?
                ResponseHelper::successResponse('user updated successfully') :
                ResponseHelper::errorResponse('user cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('user not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resetUserPassword(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'password' => 'required',
            ]);
        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->validator->errors()->getMessages(), 400);
        }
        try {
            $user = User::findOrFail(auth()->user()->id);
            $user->password = Hash::make($request->password);
            return $user->save() ?
                ResponseHelper::successResponse('password updated successfully') :
                ResponseHelper::errorResponse('password cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('user not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            auth()->logout();
            return ResponseHelper::successResponse('user successfully signed out');
        } catch (TokenExpiredException | UnauthorizedException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 401);

        } catch (TokenInvalidException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 401);

        } catch (JWTE2xception $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function adminLogin(Request $request): JsonResponse
    {
        try {
            $this->validate($request, [
                'email' => 'required|email|max:255',
                'password' => 'required',
            ]);
        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->validator->errors()->getMessages(), 400);
        }

        try {

            if (!$token = $this->jwt->attempt($request->only('email', 'password'))) {
                return ResponseHelper::errorResponse('email or password is wrong', 404);
            }

        } catch (TokenExpiredException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 401);

        } catch (TokenInvalidException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 401);

        } catch (JWTException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
        if (auth()->user()->role === 'admin') {
            return ResponseHelper::successResponse(compact('token'));
        }

        return ResponseHelper::errorResponse('This action needs an admin permission', 401);
    }

    // add payment method details
    public function addPaymentMethodDetails($card, $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            $user->pm_id = $card->id;
            $user->card_brand = $card->card['brand'];
            $user->card_last_four = $card->card['last4'];

            return $user->save() ?
                ResponseHelper::successResponse('card added successfully') :
                ResponseHelper::errorResponse('card cannot be added', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('user not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    public function forgetPassword(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email|max:255',
            ]);
        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->validator->errors()->getMessages(), 400);
        }
        // generate the password
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        $newPassword = implode($pass);
        $newHashedPassword = Hash::make($newPassword);
        // update password
        try {
            $user = User::where('email', '=', $request->email)->firstOrFail();
            $user->password = $newHashedPassword;
            $user->save();
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('user not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }

        // send an email with the new password
        try {
            $to_name = 'Diomio';
            $to_email = $request->email;
            $data = array('newPassword' => $newPassword);
            Mail::send('emails.forgetpassword', $data, function ($message) use ($to_name, $to_email) {
                $message->to($to_email, $to_name)->subject('Anfrage für neues Passwort');
                $message->from('noreply@diomio.ch', 'ABO MIO');
            });

        } catch (\Exception $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 400);

        }
        ResponseHelper::successResponse('New password email sent');
    }

    public function updateAppInfo(Request $request)
    {
        // fcm token, app version
        try {
            $this->validate($request, [
                'fcm_token' => 'required',
                'app_version' => 'required',
                'platform' => 'required',
            ]);
        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->validator->errors()->getMessages(), 400);
        }
        try {
            $user = User::findOrFail(auth()->user()->id);
            $user->fcm_token = $request->fcm_token;
            $user->app_version = $request->app_version;
            $user->platform = $request->platform;

            return $user->save() ?
                ResponseHelper::successResponse('app info updated successfully') :
                ResponseHelper::errorResponse('app info cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('user not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->middleware('auth:api', ['except' => ['login', 'register', 'adminLogin', 'appCheck', 'forgetPassword']]);
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

        $currentMonthNumber = date('m');
        $currentYear = date('Y');
        $currentMonth = date("F", strtotime('00-' . $currentMonthNumber . '-01'));
        $minDeliveryDate = '20.' . $currentMonth . '.' . $currentYear;
        $currentDate = date('d.m.Y');
        //check if the current date less than the (min delivery date 20est of the month)
        // 21 > 20 true !true false
        return !(strtotime($currentDate) > strtotime(date('d.m.Y', strtotime($minDeliveryDate)))) || (strtotime($this->getDeliveryDate($deliveryDay)) >= strtotime($currentDate));
    }

    public function appCheck()
    {
        $version = '1.0.1';
        $isMaintenanceMode = 0;

        $response = [];
        $response['version'] = $version;
        $response['is_maintenance_mode'] = $isMaintenanceMode;

        return ResponseHelper::successResponse($response);
    }
}
