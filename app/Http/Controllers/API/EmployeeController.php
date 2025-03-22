<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    //
    public function applyJob(Request $request){
        try{
                $validator = Validator::make($request->all(), [
                    'id' => 'required|int|digits_between:1,11',
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'error' => $validator->errors()
                    ]);
                }
                    $job = Job::find($request->id);
                    if(!$job)
                    {
                        return $this->json_response('success', 'Applied Job', 'Job Not Found', 204,$job);

                    }
                    $recruiter_id = $job->user_id;
                    if ($recruiter_id == Auth::user()->id)
                    {
                        return $this->json_response('success', 'Applied Job', 'You can not  your own Job', 204,$job);

                    }
                    $jobApplication = JobApplication::where('job_id', $request->id)
                                        ->where('user_id', Auth::user()->id)
                                        ->exists();
                                        if($jobApplication){
                                            return $this->json_response('success', 'Applied Job', 'You have already applied for this job', 200,$job);

                                        }
                    JobApplication::Create(
                        [
                            'user_id' => Auth::user()->id,
                            'job_id' => $job->id,
                            'recruiter_id' => $recruiter_id,
                            'applied_date' => now(),
                        ]
                    );
                    return $this->json_response('success', 'Applied Job', 'Job applied  job successfully', 200,$job);
        }catch (\Exception $e) {
            return response()->json(['error' =>  $e->getMessage(),'line'=> $e->getLine(),'File'=> $e->getFile()], 500);
        }
    }
}
