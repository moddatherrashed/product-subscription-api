<?php

namespace App\Http\Controllers;

use App\Models\BoxContent;
use App\Models\BoxContentHasProduct;
use App\Models\Subscription;
use DateTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ResponseHelper;
use Stripe\BaseStripeClient;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Mailgun\Mailgun;
use function Sodium\add;

class SubscriptionController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * register -> subscribe (address id) -> address (delivery interval id) ->
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'status' => 'required|in:active,terminated,paused,inactive',
                'user_id' => 'required',
                'delivery_interval_id' => 'required',
                'delivery_time_id' => 'required'
            ]);
        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }
        try {
            $subscription = Subscription::create($validator->validate());
            if ($subscription) {
                return ResponseHelper::successResponse($subscription);
            } else {
                return ResponseHelper::errorResponse('cannot create subscription', 500);
            }
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }


    /**
     * @param $stripeSubscriptionId
     * @param $subscriptionId
     * @return JsonResponse
     */
    public function setSubscriptionId($stripeSubscriptionId, $subscriptionId): JsonResponse
    {
        try {
            $subscription = Subscription::findOrFail($subscriptionId);
            $subscription->stripe_sub_id = $stripeSubscriptionId;

            return $subscription->save() ?
                ResponseHelper::successResponse('stripe subscription id successfully') :
                ResponseHelper::errorResponse('stripe subscription id cannot be updated', 400);

        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('stripe subscription id not found', 404);
        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function changeSubscriptionStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,terminated,paused,inactive'
        ]);

        try {
            if ($validator->fails()) {
                return ResponseHelper::errorResponse($validator->errors(), 400);
            }
            $userId = auth()->user()->id;
            $subscription = Subscription::where('user_id', $userId)->first();
            $subscription->status = $request->status;
            return $subscription->save() ?
                ResponseHelper::successResponse('status updated successfully to ' . $request->status) :
                ResponseHelper::errorResponse('status cannot be updated', 400);

        } catch (QueryException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        try {
            $subscription = Subscription::findOrFail($id);
            return $subscription->delete() ?
                ResponseHelper::successResponse('subscription deleted successfully') :
                ResponseHelper::errorResponse('subscription cannot be deleted', 400);

        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 500);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('subscription not found', 404);
        }
    }

    /**
     * @return JsonResponse
     */
    public function getUserSubscription(): JsonResponse
    {
        $userId = auth()->user()->id;
        try {
            $subscription = Subscription::where('user_id', $userId)->first();
            return ResponseHelper::successResponse($subscription);
        } catch (ModelNotFoundException $e) {
            return ResponseHelper::errorResponse('address not found', 404);
        }
    }

    /**
     * @param Request $request
     * @param UserController $userController
     * @param AddressController $addressController
     * @param SubscriptionController $subscriptionController
     * @param BoxContentController $boxContentController
     * @param BoxContentHasProductController $boxContentHasProductController
     * @param PaymentController $paymentController
     * @return JsonResponse
     */
    public function createSubscription(Request $request,
                                       UserController $userController,
                                       AddressController $addressController,
                                       SubscriptionController $subscriptionController,
                                       BoxContentController $boxContentController,
                                       BoxContentHasProductController $boxContentHasProductController,
                                       PaymentController $paymentController,
                                       DeliveryTimeController $deliveryTimeController): JsonResponse
    {
        /**
         * subscription process
         * 1- register
         * 2- create address
         * 3- create subscription
         * 4- create box content
         * 5- create box content has product
         * 6- login user
         */
        try {
            $registrationResponse = $userController->register($request);
            // if the registration success
            if ($registrationResponse->original['success'] === 1) {
                // create the address if success
                $request['user_id'] = $registrationResponse->original['response']->id;
                $createAddress = $addressController->createAddress($request);
                if ($request->delivery_address !== null) {
                    $secondRequest = new Request();
                    $secondRequest['firstname'] = $request->delivery_address['firstname'];
                    $secondRequest['lastname'] = $request->delivery_address['lastname'];
                    $secondRequest['street'] = $request->delivery_address['street'];
                    $secondRequest['zip'] = $request->delivery_address['zip'];
                    $secondRequest['country'] = $request->delivery_address['country'];
                    $secondRequest['city'] = $request->delivery_address['city'];
                    $secondRequest['user_id'] = $registrationResponse->original['response']->id;
                    $createDeliveryAddress = $addressController->createAddress($secondRequest);
                    if ($createDeliveryAddress->original['success'] !== 1) {
                        $userController->deleteUser($registrationResponse->original['response']->id);
                        $addressController->deleteAddress($createAddress->original['response']->id);
                        return ResponseHelper::errorResponse($createDeliveryAddress->original['error'], 400);
                    }
                }
                if ($createAddress->original['success'] === 1) {
                    // create the subscription if success
                    $createSubscription = $subscriptionController->subscribe($request);
                    if ($createSubscription->original['success'] === 1) {
                        //  create box content if success
                        $request['subscription_id'] = $createSubscription->original['response']->id;
                        $boxContent = $boxContentController->createBoxContent($request);
                        if ($boxContent->original['success'] === 1) {
                            // create box has content has product if  success
                            // here you should loop over the products and insert them
                            $request['box_content_id'] = $boxContent->original['response']->id;
                            foreach ($request->products as $key => $item) {
                                $request['box_content_id'] = $boxContent->original['response']->id;
                                $request['product_id'] = $item;
                                $boxContentHasProduct = $boxContentHasProductController->create($request);
                                if ($boxContentHasProduct->original['success'] === 1) {
                                    // log the user in
                                    if (count($request->products) - 1 === $key) {
                                        $userLogin = $userController->login($request);
                                        if ($userLogin->original['success'] === 1) {
                                            // create stripe subscription
                                            $makePayment = $paymentController->createSubscription($request, $registrationResponse->original['response']->id, $createSubscription->original['response']->id, $this, $userController);
                                            if ($makePayment->original['success'] === 1) {
                                                $subscriptionController->sendConfirmationEmail($request->email, $request->pizzas_string, $request->box_size, $request->box_price, $request->delivery_time_id, $request->delivery_interval_id, $request->firstname, $deliveryTimeController);
                                                return ResponseHelper::successResponse($userLogin->original['response']);
                                            }
                                            // delete box content subscription, address and the user
                                            $boxContentController->deleteBoxContent($boxContent->original['response']->id);
                                            $userController->deleteUser($registrationResponse->original['response']->id);
                                            $addressController->deleteAddress($createAddress->original['response']->id);
                                            $subscriptionController->delete($createSubscription->original['response']->id);
                                            return ResponseHelper::errorResponse($makePayment->original['error'], 400);
                                        } else {
                                            return ResponseHelper::errorResponse($userLogin->original['error'], 400);
                                        }
                                    }
                                } else {
                                    // if create box content has product did fail
                                    // delete box content subscription, address and the user
                                    $boxContentController->deleteBoxContent($boxContent->original['response']->id);
                                    $userController->deleteUser($registrationResponse->original['response']->id);
                                    $addressController->deleteAddress($createAddress->original['response']->id);
                                    $subscriptionController->delete($createSubscription->original['response']->id);
                                    return ResponseHelper::errorResponse($boxContentHasProduct->original['error'], 400);
                                }
                            }

                        } else {
                            // if create box content did fail
                            // delete subscription, address and the user
                            $userController->deleteUser($registrationResponse->original['response']->id);
                            $addressController->deleteAddress($createAddress->original['response']->id);
                            $subscriptionController->delete($createSubscription->original['response']->id);
                            return ResponseHelper::errorResponse($boxContent->original['error'], 400);
                        }
                    } else {
                        // if create subscription did fail
                        // delete the address and delete user
                        $userController->deleteUser($registrationResponse->original['response']->id);
                        $addressController->deleteAddress($createAddress->original['response']->id);
                        return ResponseHelper::errorResponse($createSubscription->original['error'], 400);
                    }
                } else {
                    // if create address did fail
                    // delete the user
                    $userController->deleteUser($registrationResponse->original['response']->id);
                    return ResponseHelper::errorResponse($createAddress->original['error'], 400);
                }
            } else {
                // if registration did fail
                return ResponseHelper::errorResponse($registrationResponse->original['error'], 400);
            }

        } catch (ValidationException $e) {
            return ResponseHelper::errorResponse($e->validator->errors()->getMessages(), 404);

        }
    }

    /**
     * @param $email
     * @param $pizzas
     * @param $boxSize
     * @param $price
     * @param $timeId
     * @param $intervalId
     * @param $name
     * @param $deliveryTimeController
     * @return string
     */
    public function sendConfirmationEmail($email, $pizzas, $boxSize, $price, $timeId, $intervalId, $name, $deliveryTimeController): string
    {
        $request = new Request();
        $request['delivery_interval_id'] = $intervalId;
        $deliveryTime = $deliveryTimeController->get($request);
        $day = '';
        foreach ($deliveryTime->original['response'] as $time) {
            if ($time['id'] === $timeId) {
                $day = $time['day'] . ' ' . date('H:i', strtotime($time['time_from'])) . ' - ' . date('H:i', strtotime($time['time_to']));
            }
        }
        $to_name = 'Diomio';
        $to_email = $email;
        $data = array('boxSize' => $boxSize, 'pizzas' => $pizzas, 'interval' => $day, 'price' => $price, 'name' => $name);
        Mail::send('emails.welcomeemail', $data, function ($message) use ($to_name, $to_email) {
            $message->to($to_email, $to_name)->subject('Willkommen in ABO MIO');
            $message->from('noreply@diomio.ch', 'ABO MIO');
        });
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function stopSubscription(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'stripe_sub_id' => 'required|string',
                'status' => 'required|string'
            ]);
        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }
        $stripe = new StripeClient(
            env('STRIPE_SECRET')
        );
        // stop subscription from stripe
        $stripe->subscriptions->update(
            $request->stripe_sub_id,
            [
                'cancel_at_period_end' => true,
            ]
        );
        return $this->changeSubscriptionStatus($request);
    }

    public function stopAllSubscriptions()
    {
        $subscriptions = Subscription::all();
        $stripe = new StripeClient(
            env('STRIPE_SECRET')
        );
        $results = [];
        foreach ($subscriptions as $sub) {
            // stop subscription from stripe
            $userSubscription = $stripe->subscriptions->retrieve(
                $sub->stripe_sub_id,
                []
            );
            $stopResult = "nothing";
            if ($userSubscription->status === 'active') {
                $stopResult = $stripe->subscriptions->update(
                    $sub->stripe_sub_id,
                    [
                        'cancel_at_period_end' => true,
                    ]
                );
            }

            array_push($results, $stopResult);
        }


        return ResponseHelper::successResponse($results);
    }

    //pause subscription

    // get stripe subscription
    /**
     * @return JsonResponse
     */
    public function getStripeSubscription(): JsonResponse
    {
        $stripe = new StripeClient(
            env('STRIPE_SECRET')
        );
        try {
            $userId = auth()->user()->id;
            $subscription = Subscription::where('user_id', $userId)->get();

            $userSubscription = $stripe->subscriptions->retrieve(
                $subscription[0]->stripe_sub_id,
                []
            );
            return ResponseHelper::successResponse($userSubscription);
        } catch (ApiErrorException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 400);
        }
    }


    public function updateSubscription(Request $request, BoxContentHasProductController $boxContentHasProductController, AddressController $addressController)
    {
        /**
         * box_content table: update box id
         * box_content_has_product
         * subscription: update delivery_interval_id and delivery_time_id
         * addresses: update street and zip code and city or update delivery address
         */
        $validator = Validator::make($request->all(),
            [
                'subscription_id' => 'required|integer',
                'box_id' => 'required|integer',
                'box_content_id' => 'required|integer',
                'delivery_interval_id' => 'required|integer',
                'delivery_time_id' => 'required|integer',
                'stripe_product_name' => 'required|string',
                'street' => 'required|string',
                'zip' => 'required|string',
                'city' => 'required|string',
                'country' => 'required|string',
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }
        $stripe = new StripeClient(
            env('STRIPE_SECRET')
        );
        try {
            // update box id where subscription_id
            $boxContent = BoxContent::where('subscription_id', $request->subscription_id)->first();
            // delete all old products
            $productsIds = BoxContentHasProduct::where('box_content_id', $request->box_content_id)->pluck('id')->toArray();
            $deleteOldProducts = BoxContentHasProduct::whereIn('id', $productsIds);
            $deleteOldProducts->delete();
            // add the new products
            foreach ($request->products as $key => $item) {
                // box content id should be already in the request
                $request['product_id'] = $item;
                $boxContentHasProductController->create($request);
            }
            // update subscription table
            $subscription = Subscription::where('id', $request->subscription_id)->first();
            $subscription->delivery_interval_id = $request->delivery_interval_id;
            $subscription->delivery_time_id = $request->delivery_time_id;
            $subscription->save();
            //update address
            $userAddress = $addressController->getUserAddress();
            $addressRequest = new Request();
            $addressRequest['street'] = $request->street;
            $addressRequest['city'] = $request->city;
            $addressRequest['zip'] = $request->zip;
            $addressRequest['country'] = $request->country;
            $addressResponse = $addressController->updateAddress($addressRequest, $userAddress->original['response'][0]->id);
            // if the user did change the box
            if ($boxContent->box_id !== intval($request->box_id) || $this->getStripeSubscription()->original['response']->cancel_at_period_end === true) {
                $boxContent->box_id = $request->box_id;
                $boxContent->save();
                $productName = $request->stripe_product_name;

                // get all products
                $products = $stripe->products->all();
                // get all prices
                $prices = $stripe->prices->all();

                $productId = '';
                $priceId = '';
                // find product id
                foreach ($products->data as $product) {
                    if ($product->name === $productName) {
                        $productId = $product->id;
                    }
                }
                // find price id
                foreach ($prices->data as $price) {
                    if ($price->product === $productId) {
                        $priceId = $price->id;
                    }
                }

                $user = auth()->user();
                // create new sub
                $newSubscription = $stripe->subscriptions->create([
                    'customer' => $user->stripe_id,
                    'items' => [
                        ['price' => $priceId]
                    ]
                ]);
                if ($newSubscription) {
                    // delete first subscription and subscribe again if exists
                    if ($this->getStripeSubscription()->original['response']->status === 'active') {
                        $subscriptionToRemove = $stripe->subscriptions->retrieve(
                            $subscription->stripe_sub_id
                        );


                        // sample prorated invoice for a subscription with quantity of 0
                        $sample_subscription_item = array(
                            "id" => $subscription->items->data[0]->id,
                            "plan" => $subscription->items->data[0]->plan->id,
                            "quantity" => $subscription->items->data[0]->quantity,
                        );

                        $upcoming_prorated_invoice = $stripe->invoices->upcoming([
                            "customer" => $subscription->customer,
                            "subscription" => $subscription->id,
                            "subscription_items" => array($sample_subscription_item),
                            "subscription_proration_date" => ' PRORATED_DATE ', // optional
                        ]);

                        // find prorated amount
                        $prorated_amount = 0;
                        foreach ($upcoming_prorated_invoice->lines->data as $invoice) {
                            if ($invoice->type == "invoiceitem") {
                                $prorated_amount = ($invoice->amount < 0) ? abs($invoice->amount) : 0;
                                break;

                            }
                        }

                        // find charge id on the active subscription's last invoice
                        $latest_invoice = $stripe->invoices->retrieve($subscription->latest_invoice);
                        $latest_charge_id = $latest_invoice->charge;

                        // refund amount from last invoice charge
                        if ($prorated_amount > 0) {

                            $refund = $stripe->refunds->create([
                                'charge' => $latest_charge_id,
                                'amount' => $prorated_amount,
                            ]);

                        }

                        // delete subscription
                        $subscription->delete();
                    }
                    // update stripe sub id in the database
                    return $this->setSubscriptionId($newSubscription->id, $subscription->id);
                }

            }

            return ResponseHelper::successResponse($addressResponse);
        } catch (ApiErrorException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 400);

        }
    }

    public function pauseSubscription($subscription_id)
    {
        /*
         * we will pause payment collection by updating the subscription
        */
        $stripe = new StripeClient(
            env('STRIPE_SECRET')
        );

        try {

            $oneMonthFromNow = date('Y-m-d', strtotime('+1 month'));
            $pauseSubscription = $stripe->subscriptions->update(
                $subscription_id,
                [
                    'pause_collection' => [
                        // offer the service for free
                        'behavior' => 'mark_uncollectible',
                        // resume collecting the payment after one month
                        'resumes_at' => strtotime($oneMonthFromNow)
                    ],
                ]
            );
            return ResponseHelper::successResponse($pauseSubscription);
        } catch (ApiErrorException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 400);
        }
    }
}
