<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ResponseHelper;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Token;

class PaymentController extends Controller
{

    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function createSubscription(Request $request, $userId, $subscriptionId, SubscriptionController $subscriptionController, UserController $userController): JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'stripe_product_name' => 'required|string',
                'card_number' => 'required|string',
                'exp_month' => 'required',
                'exp_year' => 'required|integer',
                'cvc' => 'required|string'
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }

        $user = User::find($userId);
        $stripe = new StripeClient(
            env('STRIPE_SECRET')
        );
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


        try {
            // create stripe customer
            $user->createAsStripeCustomer();
            // add payment method
            $card = $stripe->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'number' => $request->card_number,
                    'exp_month' => $request->exp_month,
                    'exp_year' => $request->exp_year,
                    'cvc' => $request->cvc,
                ],
            ]);
            // create card token
            $cardToken = $stripe->tokens->create([
                'card' => [
                    'number' => $request->card_number,
                    'exp_month' => $request->exp_month,
                    'exp_year' => $request->exp_year,
                    'cvc' => $request->cvc,
                ],
            ]);
            // specify a customer who created the card
            $stripe->customers->createSource(
                $user->stripe_id,
                ['source' => $cardToken->id]
            );
            // attach card to the customer
            $stripe->paymentMethods->attach(
                $card->id,
                ['customer' => $user->stripe_id]
            );
            $createSubscription = $stripe->subscriptions->create([
                'customer' => $user->stripe_id,
                'items' => [
                    ['price' => $priceId]
                ]
            ]);
            // update stripe subscription id in the database
            $subscriptionController->setSubscriptionId($createSubscription->id, $subscriptionId);
            $userController->addPaymentMethodDetails($card, $userId);
            return ResponseHelper::successResponse($createSubscription);
        } catch (ApiErrorException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 400);
        }
    }

    // update payment method
    public function updatePaymentMethod(Request $request, UserController $userController): JsonResponse
    {
        $user = auth()->user();
        $stripe = new StripeClient(
            env('STRIPE_SECRET')
        );
        $validator = Validator::make($request->all(),
            [
                'card_number' => 'required|string',
                'exp_month' => 'required',
                'exp_year' => 'required|integer',
                'cvc' => 'required|string'
            ]);

        if ($validator->fails()) {
            return ResponseHelper::errorResponse($validator->errors(), 400);
        }
        try {

            // add new card
            $card = $stripe->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'number' => $request->card_number,
                    'exp_month' => $request->exp_month,
                    'exp_year' => $request->exp_year,
                    'cvc' => $request->cvc,
                ],
            ]);
            // create card token
            $cardToken = $stripe->tokens->create([
                'card' => [
                    'number' => $request->card_number,
                    'exp_month' => $request->exp_month,
                    'exp_year' => $request->exp_year,
                    'cvc' => $request->cvc,
                ],
            ]);
            // specify a customer who created the card
            $stripe->customers->createSource(
                $user->stripe_id,
                ['source' => $cardToken->id]
            );
            // attach card to the customer
            $stripe->paymentMethods->attach(
                $card->id,
                ['customer' => $user->stripe_id]
            );
            $stripe->customers->update(
                $user->stripe_id,
                ['invoice_settings' => ['default_payment_method' => $card->id]]
            );
            // delete old card from customer
            $detachPaymentMethod = $stripe->paymentMethods->detach($user->pm_id);

            $userController->addPaymentMethodDetails($card, $user->id);
            return ResponseHelper::successResponse($detachPaymentMethod);

        } catch (ApiErrorException $e) {
            return ResponseHelper::errorResponse($e->getMessage(), 400);
        }
    }
}
