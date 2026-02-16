<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $user->projects()->with('owner')->withCount('tasks');

        if(isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(15);
    }

    public function store(User $user, array $data): Project
    {
        $project = $user->projects()->create($data);
        return $project;
    }

    public function show(Project $project): Project
    {
        return $project->load('owner')->loadCount('tasks');
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);
        return $project;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}