<?php

namespace App\Http\Controllers\Backend;

use Carbon\Carbon;
use App\Models\SmsApiInfo;
use App\Models\WorkSession;
use Illuminate\Http\Request;
use App\Models\TaskAssignment;
use App\Http\Controllers\Controller;
use App\Services\TaskAssignmentService;
use App\Http\Requests\TaskAssignmentRequest;

class TaskAssignmentController extends Controller
{
    protected TaskAssignmentService $service;

    public function __construct(TaskAssignmentService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $user = auth()->user();

        $assignments = $this->service->getAllAssignments();

        // If not admin, only return tasks assigned to this user
        if ($user->role !== 'admin') {
            $assignments = $assignments->where('employee_id', $user->id);
        }

        // if ($user->role === 'staff') {
        //     $assignments = $assignments->where('id', $user->id);
        // }

        return response()->json($assignments->values());
    }

    public function store(TaskAssignmentRequest $request)
    {
        $assignment = $this->service->createAssignment($request->validated());
        return response()->json($assignment);
    }

    public function update(TaskAssignmentRequest $request, TaskAssignment $assignment)
    {
        $updated = $this->service->updateAssignment($assignment, $request->validated());
        return response()->json($updated);
    }

    public function updateByTaskId(Request $request, $task_id)
    {
        // Fetch assignment and linked task
        $assignment = TaskAssignment::where('task_id', $task_id)->firstOrFail();
        $task = $assignment->task;

        // Load shop relation
        $task->load('shop');

        // Bangladesh timezone
        $bdNow = now()->timezone('Asia/Dhaka')->toDateTimeString();

        // ---------------------------
        // Prepare data for update
        // ---------------------------
        $updateAssignment = [
            'status' => $request->status ?? $assignment->status,
            'start_time' => $request->start_time ?? $assignment->start_time,
        ];

        // ✅ status-based timestamps
        if ($request->status === 'Assigned') {
            $updateAssignment['assigned_at'] = $bdNow;
        }

        if ($request->status === 'Complete') {
            $updateAssignment['completed_at'] = $bdNow;
        }

        if ($request->status === 'Reissue') {
            $updateAssignment['assigned_at'] = $bdNow;
            $updateAssignment['completed_at'] = null;
        }

        $updateTask = [
            'status' => $request->status ?? $task->status,
        ];

        // ---------------------------
        // COMPLETE NOTE HISTORY
        // ---------------------------
        if ($request->has('complete_note') && $request->complete_note) {
            $existing = json_decode($assignment->complete_note ?? '[]', true);
            $existing[] = [
                'note' => $request->complete_note,
                'submitted_at' => $bdNow,
            ];
            $updateAssignment['complete_note'] = json_encode($existing);

            $existingTask = json_decode($task->complete_note ?? '[]', true);
            $existingTask[] = [
                'note' => $request->complete_note,
                'submitted_at' => $bdNow,
            ];
            $updateTask['complete_note'] = json_encode($existingTask);
        }

        // ---------------------------
        // REISSUE COMMENT HISTORY
        // ---------------------------
        if ($request->has('reissue_comment') && $request->reissue_comment) {
            $existing = json_decode($assignment->reissue_comment ?? '[]', true);
            $existing[] = [
                'comment' => $request->reissue_comment,
                'submitted_at' => $bdNow,
            ];
            $updateAssignment['reissue_comment'] = json_encode($existing);

            $existingTask = json_decode($task->reissue_comment ?? '[]', true);
            $existingTask[] = [
                'comment' => $request->reissue_comment,
                'submitted_at' => $bdNow,
            ];
            $updateTask['reissue_comment'] = json_encode($existingTask);
        }

        // ---------------------------
        // CANCELLED NOTE
        // ---------------------------
        if ($request->has('cancelled_note') && $request->cancelled_note) {
            $updateAssignment['cancelled_note'] = $request->cancelled_note;
            $updateTask['cancelled_note'] = $request->cancelled_note;
        }

        // ---------------------------
        // APPROVED NOTE
        // ---------------------------
        if ($request->has('approved_note') && $request->approved_note) {
            $updateAssignment['approved_note'] = $request->approved_note;
            $updateTask['approved_note'] = $request->approved_note;
        }

        // ---------------------------
        // UPDATE DATABASE
        // ---------------------------
        $assignment->update($updateAssignment);
        $task->update($updateTask);

        // Refresh task to get latest values
        $task->refresh();
        $task->load('shop'); // ensure shop is loaded

        // ---------------------------
        // SEND SMS IF TASK APPROVED
        // ---------------------------
        if ($task->status === 'Approved') {
            $shop = $task->shop;

            if ($shop && $shop->oparetor_number) { // correct spelling
                $number = $shop->oparetor_number;
                // Refined SMS message
                $message = "Congratulations!\n\nDear {$task->shop_name},\n"
                        . "Your task '{$task->title}' has been successfully completed.\n"
                        . "Please check it at your convenience.";

                try {
                    $this->sendSms($number, $message);
                } catch (\Exception $e) {
                    \Log::error("Failed to send SMS: " . $e->getMessage());
                }
            } else {
                \Log::warning("Shop not found or operator number missing for task {$task->id}");
            }
        }

        return response()->json([
            'message' => 'Task updated successfully',
            'assignment' => $assignment->load(['task', 'employee', 'assigner']),
        ]);
    }

    /**
     * Helper method to send SMS
     */
    private function sendSms(string $number, string $message)
    {
        $smsApi = SmsApiInfo::first();
        if (!$smsApi) {
            \Log::warning("SMS API not configured.");
            return false;
        }

        $data = [
            'Username' => $smsApi->userName,
            'Password' => $smsApi->password,
            'From'     => $smsApi->from,
            'To'       => $number,
            'Message'  => $message,
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $smsApi->smsLinkUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            \Log::info("SMS sent to {$number}: {$message} | Response: {$response}");
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to send SMS to {$number}: " . $e->getMessage());
            return false;
        }
    }

    public function destroy(TaskAssignment $assignment)
    {
        $this->service->deleteAssignment($assignment);
        return response()->json(['message' => 'Assignment deleted successfully']);
    }

    // Start work
    public function startWork($taskId)
    {
        // ✅ Find by task_id, not by id
        $assignment = TaskAssignment::where('task_id', $taskId)->firstOrFail();

        // Stop any existing active sessions for this assignment
        WorkSession::where('task_assignment_id', $assignment->id)
            ->where('status', 'active')
            ->update(['status' => 'stopped', 'stop_time' => now()]);

        // Create a new session
        $session = WorkSession::create([
            'task_assignment_id' => $assignment->id,
            'start_time' => now(),
            'status' => 'active',
        ]);

        // Update assignment status
        $assignment->update(['status' => 'Working']);

        return response()->json([
            'assignment' => $assignment->load('workSessions'),
            'session' => $session,
        ]);
    }

    public function stopWork($taskId)
    {
        $assignment = TaskAssignment::where('task_id', $taskId)->firstOrFail();

        $session = WorkSession::where('task_assignment_id', $assignment->id)
            ->where('status', 'active')
            ->firstOrFail();

        $stopTime = now('UTC');
        $startTime = Carbon::parse($session->start_time)->setTimezone('UTC');

        // Calculate total duration in seconds
        $durationSeconds = $startTime->diffInSeconds($stopTime);

        // Convert to decimal minutes
        $durationMinutes = round($durationSeconds / 60, 2); // e.g., 0.5 min for 30 sec

        // Format for display (hours, minutes, seconds)
        $hours = intdiv($durationSeconds, 3600);
        $minutes = intdiv($durationSeconds % 3600, 60);
        $seconds = $durationSeconds % 60;

        if ($hours > 0) {
            $durationFormatted = sprintf('%d hour%s %d minute%s %d second%s',
                $hours, $hours > 1 ? 's' : '',
                $minutes, $minutes > 1 ? 's' : '',
                $seconds, $seconds > 1 ? 's' : ''
            );
        } elseif ($minutes > 0) {
            $durationFormatted = sprintf('%d minute%s %d second%s',
                $minutes, $minutes > 1 ? 's' : '',
                $seconds, $seconds > 1 ? 's' : ''
            );
        } else {
            $durationFormatted = sprintf('%d second%s',
                $seconds, $seconds > 1 ? 's' : ''
            );
        }

        $session->update([
            'stop_time' => $stopTime,
            'duration_minutes' => $durationMinutes,
            'duration_display' => $durationFormatted,
            'status' => 'stopped',
        ]);

        return response()->json([
            'session' => $session,
            'assignment' => $assignment,
            'duration_formatted' => $durationFormatted,
        ]);
    }

    // Fetch session history
    public function workHistory($taskId)
    {
        // ✅ Find by task_id, not by id
        $assignment = TaskAssignment::where('task_id', $taskId)->firstOrFail();
        return response()->json($assignment->workSessions()->orderBy('start_time')->get());
    }
}
