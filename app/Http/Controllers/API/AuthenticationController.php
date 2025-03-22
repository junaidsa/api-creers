<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthenticationController extends Controller
{
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|numeric|digits_between:10,15',
            'role' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'error' => $validator->errors()], 401);
        }
        $phoneNumber = $request->phone_number;
        $role = $request->role;
        $user = User::where('phone_number', $phoneNumber)->first();

        if ($user) {
            if ($user->role !== $role) {
                return response()->json([
                    'status' => false,
                    'message' => 'This phone number is already registered with a different role.'
                ], 400);
            }
        } else {
            $user = User::create([
                'phone_number' => $phoneNumber,
                'role' => $role,
            ]);
        }
        $otp = rand(100000, 999999);
        DB::table('phone_otps')->updateOrInsert(
            ['phone_number' => $phoneNumber],
            ['otp' => $otp, 'created_at' => now()]
        );
        $whatsappResponse = $this->sendOtpToWhatsApp($phoneNumber, $otp);

        if (!$whatsappResponse['status']) {
            return response()->json(['status' => false, 'message' => 'Failed to send OTP via WhatsApp'], 500);
        }

        return $this->json_response('success', 'User', 'OTP sent successfully via WhatsApp', 200, $user);
    }


    // Function to send OTP via WhatsApp API
    private function sendOtpToWhatsApp($phoneNumber, $otp)
    {
        $whatsappApiUrl = "https://demo.digitalsms.biz/api/";
        $whatsappApiKey = "416a1ed609bd03b2c7d0134d3dd5c2f5";
        $message = urlencode("Your verification OTP is: $otp");

        // Build the request URL
        $requestUrl = "{$whatsappApiUrl}?apikey={$whatsappApiKey}&mobile={$phoneNumber}&msg={$message}";

        // Send request using HTTP Client
        $response = Http::get($requestUrl);

        // Check response status
        if ($response->successful()) {
            return ['status' => true, 'message' => 'OTP sent via WhatsApp'];
        } else {
            return ['status' => false, 'message' => 'Failed to send OTP via WhatsApp'];
        }
    }


    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|numeric|digits_between:10,15',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'token' => $validator->errors()]);
        }
        $otpRecord = DB::table('phone_otps')->where('phone_number', $request->phone_number)->first();
        if (!$otpRecord || $otpRecord->otp != $request->otp) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }
        DB::table('phone_otps')->where('phone_number', $request->phone_number)->delete();
        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Generate Token
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'status' => 200,
            'message' => 'OTP verified',
            'token' => $token,
            'user' => $user
        ], 200);
    }
    public function ProfileUpdate(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => ['required', 'email', Rule::unique('users', 'email')->ignore(Auth::user()->id)],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
            $user = User::find(Auth::user()->id);

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'errors' => ['User not found.'],
                ]);
            }
            $user->role = Auth::user()->role;
            $user->phone_number = Auth::user()->phone_number;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->company_name = $request->company_name;
            $user->company_role = $request->company_role;
            $user->nearest_landmark = $request->nearest_landmark;
            $user->experience_level = $request->experience_level;
            $user->recent_job_role = $request->recent_job_role;
            $user->experience_years = $request->experience_years;
            $user->experience_month = $request->experience_month;
            $user->job_role = $request->job_role;
            $user->location = $request->location;
            $user->work_mode = $request->work_mode;
            $user->save();
            return $this->json_response('success', 'Update Profile', 'Update Your Profile Successfully', 200, $user);
        }catch (\Exception $e) {
            return response()->json(['error' =>  $e->getMessage(),'line'=> $e->getLine(),'File'=> $e->getFile()], 500);
        }
    }
    public function show_opt(Request $request)
{
    $validator = Validator::make($request->all(), [
        'phone_number' => 'required|numeric|digits_between:10,15',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'error' => $validator->errors()], 401);
    }

    $phoneNumber = $request->query('phone_number'); // Get from query parameters

    $user = DB::table('phone_otps')->where('phone_number', $phoneNumber)->first();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'User not found.'
        ], 400);
    }

    return $this->json_response('success', 'Show OTP', 'This number OTP', 200, $user);
}



}
