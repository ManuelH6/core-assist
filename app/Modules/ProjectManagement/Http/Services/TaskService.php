<?php

namespace App\Modules\ProjectManagement\Http\Services;

use App\Modules\ProjectManagement\Http\Models\Task;

class TaskService
{
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task;
    }

    public function delete(Task $task): bool
    {
        return (bool) $task->delete();
    }

    public function changeStatus(Task $task, string $status): Task
    {
        $task->updateStatus($status);

        return $task;
    }
}
