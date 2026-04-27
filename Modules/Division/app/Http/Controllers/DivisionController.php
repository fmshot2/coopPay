<?php

namespace Modules\Division\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithJson;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Division\Models\Division;
use Throwable;

class DivisionController extends Controller
{
    use RespondsWithJson;
    public function index(Request $request): Response|JsonResponse|RedirectResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');
            $status = $request->input('status');

            $divisionsQuery = Division::query()
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($status !== null && $status !== 'all', function ($query) use ($status) {
                    $query->where('is_active', $status === 'active');
                });

            $divisions = $divisionsQuery->latest()
                ->paginate($perPage)
                ->withQueryString()
                ->through(fn($division) => [
                    'id'          => $division->id,
                    'name'        => $division->name,
                    'description' => $division->description,
                    'is_active'   => $division->is_active,
                    'members_count' => $division->members()->count(),
                    'created_at'  => $division->created_at->format('M d, Y'),
                ]);

            return $this->respond('Admin/Divisions/Index', [
                'divisions' => $divisions,
                'filters'  => $request->only(['per_page', 'search', 'status']),
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load divisions.');
        }
    }

    public function create(): Response|JsonResponse|RedirectResponse
    {
        try {
            return $this->respond('Admin/Divisions/Create');
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load division creation form.');
        }
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'name'        => ['required', 'string', 'max:255', 'unique:divisions,name'],
                'description' => ['nullable', 'string', 'max:1000'],
                'is_active'   => ['boolean'],
            ]);

            Division::create([
                'name'        => $request->name,
                'description' => $request->description,
                'is_active'   => $request->boolean('is_active', true),
            ]);

            return $this->respondSuccess("Division {$request->name} created successfully.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to create division.');
        }
    }

    public function show(Division $division): Response|JsonResponse|RedirectResponse
    {
        try {
            $division->loadCount('members');

            return $this->respond('Admin/Divisions/Show', [
                'division' => [
                    'id'            => $division->id,
                    'name'          => $division->name,
                    'description'   => $division->description,
                    'is_active'     => $division->is_active,
                    'members_count' => $division->members_count,
                    'created_at'    => $division->created_at->format('M d, Y'),
                    'updated_at'    => $division->updated_at->format('M d, Y'),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load division.');
        }
    }

    public function edit(Division $division): Response|JsonResponse|RedirectResponse
    {
        try {
            return $this->respond('Admin/Divisions/Edit', [
                'division' => [
                    'id'          => $division->id,
                    'name'        => $division->name,
                    'description' => $division->description,
                    'is_active'   => $division->is_active,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to load division for editing.');
        }
    }

    public function update(Request $request, Division $division): JsonResponse|RedirectResponse
    {
        try {
            $request->validate([
                'name'        => ['required', 'string', 'max:255', 'unique:divisions,name,' . $division->id],
                'description' => ['nullable', 'string', 'max:1000'],
                'is_active'   => ['boolean'],
            ]);

            $division->update([
                'name'        => $request->name,
                'description' => $request->description,
                'is_active'   => $request->boolean('is_active', $division->is_active),
            ]);

            return $this->respondSuccess("Division {$division->name} updated successfully.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to update division.');
        }
    }

    public function destroy(Division $division): JsonResponse|RedirectResponse
    {
        try {
            $divisionName = $division->name;
            $division->delete();

            return $this->respondSuccess("Division {$divisionName} deleted successfully.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to delete division.');
        }
    }

    public function toggleActive(Division $division): JsonResponse|RedirectResponse
    {
        try {
            $division->update(['is_active' => !$division->is_active]);
            $status = $division->is_active ? 'activated' : 'deactivated';
            return $this->respondSuccess("Division {$division->name} has been {$status}.");
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Failed to toggle division status.');
        }
    }
}
