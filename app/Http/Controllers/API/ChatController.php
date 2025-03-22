<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Chat;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    //
    public function startChat(Request $request, $jobId)
    {
        try {
            $job = Job::select([
                'id',
                'title',
                'category_id',
                'work_place',
                'vacancy',
                'location',
                'salary_min',
                'salary_max',
                'salary_type',
                'employment_type',
                'working_hours',
                'working_days',
                'english_level',
                'gender',
                'interview_type',
                'description',
                'benefits',
                'qualifications',
                'experience',
                'user_id'
            ])->findOrFail($jobId);

            $user = Auth::user();

            if ($user->id == $job->user_id) {
                $recruiter = $user;
                $jobApplication = JobApplication::where('job_id', $jobId)
                    ->where('recruiter_id', $recruiter->id)
                    ->first();

                if (!$jobApplication) {
                    return response()->json(['error' => 'No applicants found for this job.'], 404);
                }

                $employee = User::find($jobApplication->user_id);
            } else {
                $employee = $user;
                $jobApplication = JobApplication::where('job_id', $jobId)
                    ->where('user_id', $employee->id)
                    ->first();

                if (!$jobApplication) {
                    return response()->json(['error' => 'You have not applied for this job.'], 404);
                }

                $recruiter = User::find($jobApplication->recruiter_id);
            }
            $chat = Chat::where('recruiter_id', $recruiter->id)
                ->where('employee_id', $employee->id)
                ->first();

            if (!$chat) {
                $chat = Chat::create([
                    'job_id' => $jobId,
                    'recruiter_id' => $recruiter->id,
                    'employee_id' => $employee->id,
                ]);
            }
            $senderId = $user->id;
            $receiverId = ($senderId == $recruiter->id) ? $employee->id : $recruiter->id;
            $messageText = "**Job Details:**\n";
            $messageText .= "**Title:** {$job->title}\n";
            $messageText .= "**Location:** {$job->location}\n";
            $messageText .= "**Salary:** {$job->salary_min} - {$job->salary_max} {$job->salary_type}\n";
            $messageText .= "**Employment Type:** {$job->employment_type}\n";
            $messageText .= "**Description:** {$job->description}\n";

            Message::create([
                'chat_id' => $chat->id,
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message' => $messageText
            ]);
            return $this->json_response('success', 'Contact', 'Chat initiated successfully', 200, $chat);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], 500);
        }
    }

    public function getContacts()
{
    try {
        $userId = auth()->id();

        $chats = Chat::where('recruiter_id', $userId)
            ->orWhere('employee_id', $userId)
            ->with([
                'recruiter',
                'employee',
                'messages' => function ($query) {
                    $query->latest();
                }
            ])
            ->latest()
            ->get()
            ->map(function ($chat) use ($userId) {
                $contact = $chat->recruiter_id == $userId ? $chat->employee : $chat->recruiter;
                $profileImageUrl = url("/uploads/profile_image/thum/{$contact->image}");

                // Get the last message
                $lastMessage = $chat->messages->first();

                return [
                    'id' => $chat->id,
                    'contact_name' => $contact->name,
                    'profile_image' => $contact->image ? $profileImageUrl : url('/uploads/profile_image/thum/default.png'),
                    'receiver_id' => $contact->id, // 👈 Add receiver_id here
                    'last_message' => $lastMessage ? $lastMessage->message : null,
                    'last_message_time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : null,
                    'last_message_sender_id' => $lastMessage ? $lastMessage->sender_id : null,
                    'unread_count' => $chat->messages()
                        ->where('receiver_id', $userId)
                        ->where('is_read', 0)
                        ->count(),
                ];
            });

        return response()->json([
            'type' => 'success',
            'code' => 'Contacts',
            'status' => 200,
            'message' => 'Contact list fetched successfully',
            'base_url' => url('/'),
            'data' => $chats
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], 500);
    }
}



    public function sendMessage(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'receiver_id' => ['required'],
                'chat_id' => ['required'],
                'message' => ['required','string'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $message = Message::create([
                'chat_id' => $request->chat_id,
                'sender_id' => auth()->id(),
                'receiver_id' => $request->receiver_id,
                'message' => $request->message,
            ]);
            return $this->json_response('success', 'Send Message', 'Send message successfully', 200, $message);
     }catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}
public function getMessages($id)
{
    try{
    $messages = Message::where('chat_id', $id)
        ->with('sender','receiver')
        ->orderBy('created_at', 'asc')
        ->get();
        return $this->json_response('success', 'get Message', 'Get  messages list successfully', 200, $messages);
    }catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
}
}
