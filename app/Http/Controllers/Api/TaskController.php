<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectService;
use App\Services\TaskService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        protected ProjectService $projectService,
        protected TaskService $taskService
    ) {
    }

    public function index(Request $request, Project $project)
    {
        $project = $this->projectService->getVisibleProject($request->user(), $project);
        $tasks = $this->taskService->getVisibleTasks($request->user(), $project);

        return ApiResponse::success('Tasks fetched successfully.', [
            'tasks' => TaskResource::collection($tasks)->resolve(),
        ]);
    }

    public function store(StoreTaskRequest $request, Project $project)
    {
        $task = $this->taskService->create($request->user(), $project, $request->validated());

        return ApiResponse::success('Task created successfully.', [
            'task' => TaskResource::make($task)->resolve(),
        ], 201);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        $task = $this->taskService->updateStatus(
            $request->user(),
            $task,
            $request->validated()['status']
        );

        return ApiResponse::success('Task status updated successfully.', [
            'task' => TaskResource::make($task)->resolve(),
        ]);
    }
}
