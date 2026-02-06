<?php

namespace App\Http\Controllers\Warehouse;

use App\Models\Task;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends BaseController
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::with(['assignee', 'creator'])
            ->orderBy('due_date', 'asc')
            ->orderBy('due_date', 'asc')
            ->paginate(50);

        return Inertia::render('warehouse/tasks/Index', [
            'tasks' => $tasks,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Task::class);

        $users = User::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('warehouse/tasks/Create', [
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Task::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,critical',
            'due_date' => 'nullable|date_format:Y-m-d',
            'assigned_to' => 'nullable|exists:users,id',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $task = Task::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        if ($validated['assigned_to'] ?? null) {
            Notification::create([
                'user_id' => $validated['assigned_to'],
                'type' => 'task_assigned',
                'title' => 'New Task Assigned',
                'message' => 'You have been assigned the task: ' . $task->title,
                'notifiable_id' => $task->id,
                'notifiable_type' => Task::class,
            ]);
        }

        return redirect()->route('warehouse.tasks.show', $task)
            ->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $task->load(['assignee', 'creator']);

        return Inertia::render('warehouse/tasks/Show', [
            'task' => $task,
        ]);
    }

    public function edit(Task $task)
    {
        $this->authorize('update', $task);

        $task->load(['assignee', 'creator']);
        $users = User::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('warehouse/tasks/Edit', [
            'task' => $task,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,critical',
            'due_date' => 'nullable|date_format:Y-m-d',
            'assigned_to' => 'nullable|exists:users,id',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $oldAssignee = $task->assigned_to;
        $task->update($validated);

        // Notify if assignment changed
        if ($oldAssignee !== $validated['assigned_to'] && $validated['assigned_to']) {
            Notification::create([
                'user_id' => $validated['assigned_to'],
                'type' => 'task_assigned',
                'title' => 'Task Reassigned',
                'message' => 'You have been assigned the task: ' . $task->title,
                'notifiable_id' => $task->id,
                'notifiable_type' => Task::class,
            ]);
        }

        return redirect()->route('warehouse.tasks.show', $task)
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('warehouse.tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    public function assignToUser(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $task->update([
            'assigned_to' => $validated['user_id'],
        ]);

        // Send notification to assigned user
        Notification::create([
            'user_id' => $validated['user_id'],
            'type' => 'task_assigned',
            'title' => 'Task Assigned',
            'message' => 'You have been assigned the task: ' . $task->title,
            'notifiable_id' => $task->id,
            'notifiable_type' => Task::class,
        ]);

        return response()->json([
            'message' => 'Task assigned successfully',
            'task' => $task->load('assignee'),
        ]);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $task->update(['status' => $validated['status']]);

        // Send notification if status changed to completed
        if ($validated['status'] === 'completed' && $task->created_by !== auth()->id()) {
            Notification::create([
                'user_id' => $task->created_by,
                'type' => 'task_completed',
                'title' => 'Task Completed',
                'message' => 'Task completed by ' . auth()->user()->name . ': ' . $task->title,
                'notifiable_id' => $task->id,
                'notifiable_type' => Task::class,
            ]);
        }

        return response()->json([
            'message' => 'Status updated successfully',
            'task' => $task,
        ]);
    }


    public function updateColor(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $task->update(['color' => $validated['color']]);

        return response()->json([
            'message' => 'Color updated successfully',
            'task' => $task,
        ]);
    }
}
