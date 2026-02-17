<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Task\StoreTaskRequest;
use App\Http\Requests\Api\V1\Task\UpdateTaskRequest;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService
    ) {}

    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $tasks = $this->taskService->listByProject(
            $project,
            $request->only(['status', 'priority', 'assigned_to'])
        );

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $task = $this->taskService->store($project, $request->validated());

        return response()->json([
            'message' => 'Task created successfully.',
            'data' => new TaskResource($task),
        ], 201);
    }

    public function show(Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task = $this->taskService->show($task);

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->taskService->update($task, $request->validated());

        return response()->json([
            'message' => 'Task updated successfully.',
            'data' => new TaskResource($task),
        ]);
    }

    public function destroy(Project $project, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->taskService->delete($task);

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    public function globalIndex(Request $request): AnonymousResourceCollection
    {
        $tasks = $this->taskService->listAll(
            $request->user(),
            $request->only(['status', 'priority', 'assigned_to'])
        );

        return TaskResource::collection($tasks);
    }
}