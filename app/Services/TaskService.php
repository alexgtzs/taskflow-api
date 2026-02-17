<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{
    public function listByProject(Project $project, array $filters = []): LengthAwarePaginator
    {
        $query = $project->tasks()->with(['assignee', 'project']);

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate(15);
    }

    public function listAll(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Task::whereIn('project_id', $user->projects()->pluck('id')->toArray())
            ->with(['assignee', 'project']);

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate(15);
    }

    public function store(Project $project, array $data): Task
    {
        $task = $project->tasks()->create($data);

        return $task->load(['assignee', 'project']);
    }

    public function show(Task $task): Task
    {
        return $task->load(['assignee', 'project']);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->load(['assignee', 'project']);
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    private function applyFilters(\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query, array $filters): void
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }
    }
}