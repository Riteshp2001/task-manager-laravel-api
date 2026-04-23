<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\StoreProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projectService
    ) {
    }

    public function index(Request $request)
    {
        $projects = $this->projectService->getVisibleProjects($request->user());

        return ApiResponse::success('Projects fetched successfully.', [
            'projects' => ProjectResource::collection($projects)->resolve(),
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        $project = $this->projectService->create($request->user(), $request->validated());

        return ApiResponse::success('Project created successfully.', [
            'project' => ProjectResource::make($project)->resolve(),
        ], 201);
    }

    public function show(Request $request, Project $project)
    {
        $project = $this->projectService->getVisibleProject($request->user(), $project);

        return ApiResponse::success('Project fetched successfully.', [
            'project' => ProjectResource::make($project)->resolve(),
        ]);
    }
}
