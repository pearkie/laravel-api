<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Task;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Task::class);
        // both of these lines are the same
        // return TaskResource::collection(Task::all());
        // return Task::all()->toResourceCollection(); // cleaner version
        return request()->user()
            ->tasks()
            ->get()
            ->toResourceCollection();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        if (request()->user()->cannot('create', Task::class)) {
            abort(403, "This action is unauthorized.");
        }

        // $task = Task::create($request->validated() + ['user_id' => request()->user()->id]);
        $task = $request->user()->tasks()->create($request->validated());

        return $task->toResource();
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        Gate::authorize('view', $task);
        // all syntax are valid
        // return new TaskResource($task);
        // return TaskResource::make($task); // static method
        return $task->toResource(); // cleaner version
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        if ($request->user()->cannot('update', $task)) {
            abort(403, "This action is unauthorized.");
        }

        $task->update($request->validated());

        return $task->toResource();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        if (request()->user()->cannot('delete', $task)) {
            abort(403, "This action is unauthorized.");
        }

        $task->delete();

        return response()->noContent(); // 204 No Content
    }
}
