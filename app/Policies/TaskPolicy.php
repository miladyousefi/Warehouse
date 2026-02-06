<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('task.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasPermissionTo('task.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('task.create');
    }

    public function update(User $user, Task $task): bool
    {
        // Can update if creator or admin
        return $user->hasPermissionTo('task.edit') && 
               ($user->id === $task->created_by || $user->hasRole('admin'));
    }

    public function delete(User $user, Task $task): bool
    {
        // Can delete if creator or admin
        return $user->hasPermissionTo('task.delete') && 
               ($user->id === $task->created_by || $user->hasRole('admin'));
    }

    public function assign(User $user): bool
    {
        return $user->hasPermissionTo('task.assign');
    }
}
