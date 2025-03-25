<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Invite;
use App\Models\Language;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Validator;
class UtilityController extends Controller
{

    public function getCategories(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'nullable|numeric',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'error' => $validator->errors()], 401);
            }
            $id = $request->id;
            if ($id) {
                $categories = Category::where('parent_id', $id)
                    ->where('status', 1)
                    ->select('id', 'name', 'icon', 'status', 'parent_id')
                    ->get();

                if ($categories->isEmpty()) {
                    return $this->json_response('error', 'No Subcategories', 'No subcategories found for this category', 204, []);
                }

                return $this->json_response('success', 'Get Subcategories', 'Subcategories list successfully', 200, $categories);
            } else {
                $categories = Category::whereNull('parent_id')
                    ->where('status', 1)
                    ->select('id', 'name', 'icon', 'status')
                    ->get();

                return $this->json_response('success', 'Get Categories', 'Categories list successfully', 200, $categories);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    public function getLanguages(){
        try {
            $languages = Language::get();
            return $this->json_response('success', 'Get Languages', 'Languages list successfully', 200, $languages);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }


    public function getsubscriptions(){
        try {
            $subscriptions = Subscription::get();
            return $this->json_response('success', 'Get Subscriptions', 'subscriptions list successfully', 200, $subscriptions);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }

    }

    public function createOrder(Request $request)
{
    try {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'razorpay_order_id' => 'required',
        ]);

        $subscription = Subscription::findOrFail($request->subscription_id);
        // Create Razorpay order
        // $order = $api->order->create([
        //     'receipt' => 'order_' . uniqid(),
        //     'amount' => $subscription->price * 100, // Razorpay expects amount in paise
        //     'currency' => 'INR',
        //     'payment_capture' => 1
        // ]);

        // Store order in database
        $newOrder = Order::create([
            'user_id' => auth()->id(),
            'subscription_id' => $subscription->id,
            // 'razorpay_order_id' => $order['id'], // Save Razorpay order ID
            'currency' => 'INR',
            'amount' => $subscription->price,
            'status' => 1,
        ]);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Payment processing failed: ' . $e->getMessage()], 500);
    }
}


}
