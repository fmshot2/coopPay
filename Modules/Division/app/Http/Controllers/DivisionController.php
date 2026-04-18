<?php

namespace Modules\Division\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Division\Models\Division;

class DivisionController extends Controller
{
    public function index(Request $request): Response
    {
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

        return Inertia::render('Admin/Divisions/Index', [
            'divisions' => $divisions,
            'filters'  => $request->only(['per_page', 'search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Divisions/Create');
    }

    public function store(Request $request)
    {
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

        return redirect()
            ->route('admin.divisions.index')
            ->with('success', "Division {$request->name} created successfully.");
    }

    public function show(Division $division): Response
    {
        $division->loadCount('members');

        return Inertia::render('Admin/Divisions/Show', [
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
    }

    public function edit(Division $division): Response
    {
        return Inertia::render('Admin/Divisions/Edit', [
            'division' => [
                'id'          => $division->id,
                'name'        => $division->name,
                'description' => $division->description,
                'is_active'   => $division->is_active,
            ],
        ]);
    }

    public function update(Request $request, Division $division)
    {
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

        return redirect()
            ->route('admin.divisions.show', $division->id)
            ->with('success', "Division {$division->name} updated successfully.");
    }

    public function destroy(Division $division)
    {
        $divisionName = $division->name;
        $division->delete();

        return redirect()
            ->route('admin.divisions.index')
            ->with('success', "Division {$divisionName} deleted successfully.");
    }

    public function toggleActive(Division $division)
    {
        $division->update(['is_active' => !$division->is_active]);
        $status = $division->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Division {$division->name} has been {$status}.");
    }
}
