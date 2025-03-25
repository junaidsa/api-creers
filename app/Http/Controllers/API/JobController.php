<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Invite;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\JobApplication;
use App\Models\Subscription;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function store(Request $request)
    {
        try {
            $userSubscription = UserSubscription::where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('user_subscriptions.end_date', 'desc')
            ->first();
        if (!$userSubscription) {
            return $this->json_response('error', 'Active subscription', 'You do not have an active subscription.', 200, $userSubscription);
        }
        $subscriptionPlan = Subscription::where('id', $userSubscription->subscription_id)->first();

        if (!$subscriptionPlan) {
            return $this->json_response('error', 'subscription plan', 'Invalid subscription plan', 200, $subscriptionPlan);
        }
        if ($userSubscription->jobs_posted >= $subscriptionPlan->job_postings) {
            return $this->json_response('error', 'subscription plan', 'You have reached your job posting limit', 200, $subscriptionPlan);
        }
            $validator = Validator::make($request->all(), [
                'title' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'category_id' => ['required', 'integer'],
                'skills_ids' => ['required', 'array'],
                'skills_ids.*' => ['integer'],
                'working_days' => ['required', 'array'],
                'working_days.*' => ['string'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create a new job
            $job = Job::create([
                'user_id' => auth()->id(),
                'title' => $request->title,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'skiles_ids' => $request->has('skills_ids') ? json_encode($request->skills_ids) : null,
                'working_days' => $request->working_days,
                'work_place' => $request->input('work_place', null),
                'vacancy' => $request->input('vacancy', null),
                'location' => $request->input('location', null),
                'salary_min' => $request->input('salary_min', 0),
                'salary_max' => $request->input('salary_max', 0),
                'salary_type' => $request->input('salary_type', null),
                'employment_type' => $request->input('employment_type', null),
                'working_hours' => $request->input('working_hours', null),
                'language_id' => $request->has('language') ? json_encode($request->language) : null,
                'english_level' => $request->input('english_level', null),
                'qualifications' => $request->input('qualifications', null),
                'gender' => $request->input('gender', null),
                'interview_type' => $request->input('interview_type', null),
                'experience' => $request->input('experience', null),
                'benefits' => $request->input('benefits', null),
            ]);
            $userSubscription->increment('jobs_posted');
            return $this->json_response('success', 'Create Job', 'Job created successfully', 200, $job);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    public function update(Request $request, $id)
{
    try {
        $job = Job::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'category_id' => ['sometimes', 'integer'],
            'skills_ids' => ['sometimes', 'array'],
            'skills_ids.*' => ['integer'],
            'working_days' => ['sometimes', 'array'],
            'working_days.*' => ['string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update job attributes
        $job->update([
            'title' => $request->input('title', $job->title),
            'description' => $request->input('description', $job->description),
            'category_id' => $request->input('category_id', $job->category_id),
            'skiles_ids' => $request->has('skills_ids') ? $request->skills_ids : $job->skiles_ids,
            'working_days' => $request->has('working_days') ? $request->working_days : $job->working_days,
            'work_place' => $request->input('work_place', $job->work_place),
            'vacancy' => $request->input('vacancy', $job->vacancy),
            'location' => $request->input('location', $job->location),
            'salary_min' => $request->input('salary_min', $job->salary_min),
            'salary_max' => $request->input('salary_max', $job->salary_max),
            'salary_type' => $request->input('salary_type', $job->salary_type),
            'employment_type' => $request->input('employment_type', $job->employment_type),
            'working_hours' => $request->input('working_hours', $job->working_hours),
            'language_id' => $request->has('language') ? $request->language : $job->language_id,
            'english_level' => $request->input('english_level', $job->english_level),
            'qualifications' => $request->input('qualifications', $job->qualifications),
            'gender' => $request->input('gender', $job->gender),
            'interview_type' => $request->input('interview_type', $job->interview_type),
            'experience' => $request->input('experience', $job->experience),
            'benefits' => $request->input('benefits', $job->benefits),
        ]);

        return $this->json_response('success', 'Update Job', 'Job updated successfully', 200, $job);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}


    public function jobDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'job_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'error' => $validator->errors()], 401);
            }

            $job_id = $request->job_id;

            $job = Job::where('jobs.id', $job_id)
            ->leftJoin('users', 'jobs.user_id', '=', 'users.id')
            ->select(
                'jobs.*',
                'users.company_name as company',
                'users.phone_number as phone',
                DB::raw(" CASE
                    WHEN jobs.is_verified = 1 THEN 'Live'
                    WHEN jobs.is_verified = 2 THEN 'Closed'
                    ELSE 'Draft'
                END as status")
            )
            ->first();
            return $this->json_response('success', 'Job Details', 'Get Job Deatils successfully', 200, $job);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

        public function myJob(Request $request)
        {
            try {
                $user = Auth::user();
                $status = $request->input('status');

                $query = Job::leftJoin('users', 'jobs.user_id', '=', 'users.id') // Join with users table
                    ->select(
                        'jobs.*',
                        'users.company_name as company',
                        'users.phone_number as phone'
                    )
                    ->with('category');

                if ($user->role === 'recruiter') {
                    // Recruiter: Get jobs they created
                    $query->where('jobs.user_id', $user->id);
                } elseif ($user->role === 'employee') {
                    // Employee: Get jobs they applied to
                    $query->whereIn('jobs.id', function ($subQuery) use ($user) {
                        $subQuery->select('job_id')
                            ->from('job_applications')
                            ->where('user_id', $user->id);
                    });
                }

                if ($status !== null) {
                    $query->where('is_verified', $status);
                }

                $jobs = $query->get();

                return $this->json_response('success', 'Get Jobs', 'Jobs list retrieved successfully', 200, $jobs);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ], 500);
            }
        }


    public function filterJobs(Request $request)
    {
        try {
            $findjobs = Job::where('is_verified', 1)
            ->leftJoin('users', 'jobs.user_id', '=', 'users.id') // Join with users table
            ->select(
                'jobs.*',
                'users.company_name as company',
                'users.phone_number as phone'
            );
            if (!empty($request->title)) {
                $findjobs = $findjobs->where('title', 'LIKE', '%' . $request->title . '%');
            }
            if (!empty($request->location)) {
                $findjobs = $findjobs->where('location', $request->location);
            }
            if (!empty($request->category)) {
                $category = Category::where('name', $request->category)->first();
                if ($category) {
                    $findjobs = $findjobs->where('category_id', $category->id);
                }
            }
            if (!empty($request->employment_type)) {
                $jobTypeArray = explode(',', $request->employment_type);
                $findjobs = $findjobs->whereIn('employment_type', $jobTypeArray);
            }
            if (!empty($request->experience)) {
                $findjobs = $findjobs->where('experience', $request->experience);
            }
            $findjobs = $findjobs->get();
            return $this->json_response('success', 'Get jobs', 'jobs list successfully', 200, $findjobs);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    public function getJob(Request $request)
    {
        try {
            $user = Auth::user();
                $query = Job::leftJoin('users', 'jobs.user_id', '=', 'users.id')
        ->select(
            'jobs.*',
            'users.company_name as company',
            'users.phone_number as phone'
        )->with('category');
                $jobs = $query->get();
        return $this->json_response('success', 'Get Jobs', 'Jobs list  successfully', 200, $jobs);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    public function updateStatus($id)
    {
        try {
            $job = Job::find($id);
            if (!$job) {
                return response()->json(['status' => 'error', 'message' => 'Job not found'], 404);
            }
            $job->update(['is_verified' => 2]);

            return $this->json_response('success', 'Closed successfully', 'Job Closed successfully', 200,$job);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    public function applicantList(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'job_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'error' => $validator->errors()], 401);
            }
        $id = $request->job_id;
        $jobapplicants = JobApplication::where('job_id', $id)->with('user')->get();
        $jobapplicants->each(function ($applicant) {
            if ($applicant->user) {
                $applicant->user->makeHidden(['company_name', 'company_role', 'nearest_landmark']);
            }
        });
                return $this->json_response('success', 'Applicant Job', 'Job Applicant List successfully', 200,$jobapplicants);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ], 500);
            }
    }
    public function recommendedApplicants(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'job_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'error' => $validator->errors()], 401);
            }

            $job = Job::find($request->job_id);

            if (!$job) {
                return response()->json(['status' => false, 'message' => 'Job not found'], 404);
            }

            // Decode skills_ids from string to array
            $jobSkills = json_decode($job->skiles_ids, true);
            if (!is_array($jobSkills)) {
                $jobSkills = [];
            }

            // Get recommended applicants with role check
            $recommendedApplicants = JobApplication::where('job_id', $job->id)
                ->whereHas('user', function ($query) use ($job, $jobSkills) {
                    $query->where('role', 'employee') // Ensure the user is an employee
                        ->where('category_id', $job->category_id) // Match category
                        ->whereNotNull('skiles_ids')
                        ->where(function ($q) use ($jobSkills) {
                            foreach ($jobSkills as $skill) {
                                $q->orWhereJsonContains('skiles_ids', $skill) // JSON format check
                                  ->orWhereRaw("FIND_IN_SET(?, skiles_ids)", [$skill]); // String format check
                            }
                        });
                })
                ->with(['user'])
                ->get();

            return $this->json_response('success', 'Recommended Applicants', 'Filtered applicants retrieved successfully', 200, $recommendedApplicants);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }
    public function createInvite(Request $request)
{
    try {
        $validatedData = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'job_id' => 'required|exists:jobs,id',
            'status' => 'nullable|in:active,expired,accepted,rejected',
        ]);

        $validatedData['user_id'] = auth()->id();
        $validatedData['recruiter_id'] = auth()->id();
        $invite = Invite::create($validatedData);
        return $this->json_response('success', 'Create Invite', 'Invite created successfully', 200, $invite);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}

public function getUserInvites()
{
    try {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access',
            ], 401);
        }
        if ($user->role === 'recruiter') {
            $invites = Invite::where('recruiter_id', $user->id)->get();
        } elseif ($user->role === 'employee') {
            $invites = Invite::where('employee_id', $user->id)->get();
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied',
            ], 403);
        }

        return $this->json_response('success', 'get Invite', 'User invites retrieved successfully', 200, $invites);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}

public function updateInviteStatus(Request $request, $inviteId)
{
    try {
        $user = auth()->user();        // Find the invite
        $invite = Invite::find($inviteId);
        if (!$invite) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invite not found',
            ], 404);
        }

        // Allow only employees to change the status
        if ($user->role !== 'employee') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only employees can update their invite status',
            ], 403);
        }

        // Validate status
        $request->validate([
            'status' => 'required|in:accepted,rejected'
        ]);

        // Update status
        $invite->status = $request->status;
        $invite->save();

        return $this->json_response('success', 'Update Invite Status', 'Invite status updated successfully', 200, $invite);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}
public function updateApplicantStatus(Request $request, $id)
{
    try {
        $user = auth()->user();
       $jobApplication = JobApplication::find($id);
        if (!$jobApplication) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job application not found',
            ], 404);
        }

        // Validate status input
        $request->validate([
            'applied_status' => 'required|in:shortlisted,rejected,hired'
        ]);

        // Only recruiters can update the status
        if ($user->role !== 'recruiter') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only recruiters can update job application status',
            ], 403);
        }

        // Recruiter can update to 'shortlisted', 'rejected', or 'hired'
        $jobApplication->applied_status = $request->applied_status;
        $jobApplication->save();
        return $this->json_response('success', 'Update Application Status', 'Job application status updated successfully', 200, $jobApplication);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}
}
