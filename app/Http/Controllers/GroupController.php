<?php

namespace App\Http\Controllers;
use App\Models\Group_user;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    /**
     * Display a listing of groups
     */
    public function index()
    {
        $groups = Group::withCount('members')->get();
        $users = User::all();
        return view('content.groups.groups-list', compact('groups', 'users'));
    }

    /**
     * Store a newly created group
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'position' => [
                'required',
                'string',
                'max:100',
                // Rule::unique('groups')->where(fn ($query) => $query->where('campus', $request->campus))
            ],
            // 'campus' => 'required|string|max:100'
        ]);

        $group = Group::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully!',
            'group' => $group
        ]);
    }

    /**
     * Update the specified group
     */
    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'position' => [
                'required',
                'string',
                'max:100',
                // Rule::unique('groups')
                //     ->where(fn ($query) => $query->where('campus', $request->campus))
                //     ->ignore($group->group_id, 'group_id')
            ],
            // 'campus' => 'required|string|max:100'
        ]);

        $group->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Group updated successfully!',
            'group' => $group
        ]);
    }

    /**
     * Delete the specified group
     */
    public function destroy(Group $group)
    {
        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully!'
        ]);
    }
}
