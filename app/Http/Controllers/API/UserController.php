<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    //
    public function updateProfilepic(Request $request)
    {
        $id = Auth::user()->id;
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $image = $request->file('image');
        $imageName = $id . '-' . time() . '.' . $image->getClientOriginalExtension();

        // Define upload paths
        $destinationPath = base_path('../uploads/profile_image/');
        $thumbnailPath = base_path('../uploads/profile_image/thum/');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true, true);
        }
        if (!File::exists($thumbnailPath)) {
            File::makeDirectory($thumbnailPath, 0777, true, true);
        }
        $image->move($destinationPath, $imageName);
        $sourcePath = $destinationPath . $imageName;
        $manager = new ImageManager(Driver::class);
        $image = $manager->read($sourcePath);
        $image->cover(200, 200);
        $image->toPng()->save($thumbnailPath . $imageName);
        if (Auth::user()->image) {
            File::delete($destinationPath . Auth::user()->image);
            File::delete($thumbnailPath . Auth::user()->image);
        }
        User::where('id', $id)->update(['image' => $imageName]);
        $thumbUrl = request()->getSchemeAndHttpHost() . "/api/uploads/profile_image/thum/" . $imageName;

        return $this->json_response('success', 'Get image', 'Profile Picture Updated Successfully', 200, $thumbUrl);
    }

    public function updateProfile(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'phone_number' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'company_role' => 'nullable|string|max:255',
            'nearest_landmark' => 'nullable|string|max:255',
            'experience_level' => 'string|in:Fresher,Experienced',
            'experience_years' => 'nullable|integer|min:0',
            'experience_month' => 'nullable|integer|min:0|max:12',
            'recent_job_role' => 'nullable|string|max:255',
            'experience_details' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'work_mode' => 'nullable|array',
            'work_mode.*' => 'string',
            'category_id' => 'nullable|integer|exists:categories,id',
            'skiles_ids' => 'nullable|array',
            'skiles_ids.*' => 'integer',
            'education' => 'nullable|string|max:255',
            'languages' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        // Format experience data
        $experience = $request->experience_level === 'Fresher' ? null : [
            'years' => $request->experience_years,
            'months' => $request->experience_month,
            'details' => $request->experience_details,
        ];
        $user = User::find(Auth::id());
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'company_name' => $request->company_name,
            'company_role' => $request->company_role,
            'nearest_landmark' => $request->nearest_landmark,
            'experience_level' => $request->experience_level,
            'recent_job_role' => $request->recent_job_role,
            'experience' => $experience ? json_encode($experience) : null,
            'location' => $request->location,
            'work_mode' => $request->has('work_mode') ? json_encode($request->work_mode) : null,
            'category_id' => $request->category_id,
            'education' => $request->education,
            'status' => 1,
            'language_id' => $request->has('language') ? json_encode($request->language) : null,
        ]);
        if ($request->has('skiles_ids')) {
            $user->skiles_ids = json_encode($request->skiles_ids);
            $user->save();
        }
        return $this->json_response('success', 'Profile Update', 'Profile updated successfully', 200, $user);
    }

    public function showProfile($id)
    {
        try {
            $user = User::with('category')->find($id);
            if (!$user) {
                return $this->json_response('success', 'User not found', 'view Profile User not found', 204,$user);
            }
            return $this->json_response('success', 'View Profile', 'view Profile successfully', 200,$user);
        }catch (\Exception $e) {
            return response()->json(['error' =>  $e->getMessage(),'line'=> $e->getLine(),'File'=> $e->getFile()], 500);
        }
    }
}
