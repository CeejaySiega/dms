<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Models\Group_user;
use Illuminate\Http\Request;

class AssignUserController extends Controller
{
    /**
     * Show the assign users page for a group
     */
    public function show(Group $group)
    {
        $users = User::with('employee')->get();
        $members = $group->members()->with('user')->get();
        
        // Load all group memberships for each user
        foreach ($users as $user) {
            $user->group_memberships = Group_user::where('user_id', $user->user_id)->count();
            $user->group_list = Group_user::where('user_id', $user->user_id)
                ->with('group')
                ->get()
                ->pluck('group')
                ->all();
        }

        return view('content.groups.assign-users', compact('group', 'users', 'members'));
    }

    /**
     * Get members of a group
     */
    public function getMembers(Group $group)
    {
        $members = $group->members()->with('user')->get();

        return response()->json([
            'success' => true,
            'members' => $members
        ]);
    }

    /**
     * Assign users to a group
     */
    public function assignUsers(Request $request, Group $group)
    {
        // Decrypt user_ids from request
        if ($request->has('user_ids')) {
            $decryptedUserIds = array_map(function($id) {
                return decryptId($id);
            }, $request->input('user_ids'));
            $request->merge(['user_ids' => $decryptedUserIds]);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,user_id'
        ]);

        $assigned = [];
        $skipped = [];

        foreach ($validated['user_ids'] as $userId) {
            // Check if user is already a member of THIS group
            $existingMember = Group_user::where('group_id', $group->group_id)
                ->where('user_id', $userId)
                ->exists();

            if ($existingMember) {
                $skipped[] = $userId;
            } else {
                Group_user::create([
                    'group_id' => $group->group_id,
                    'user_id' => $userId
                ]);
                $assigned[] = $userId;
            }
        }

        $message = count($assigned) . ' user(s) assigned successfully!';
        if (!empty($skipped)) {
            $message .= ' ' . count($skipped) . ' user(s) were already assigned to this group.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'assigned' => $assigned,
            'skipped' => $skipped
        ]);
    }

    /**
     * Remove users from a group
     */
    public function removeUsers(Request $request, Group $group)
    {
        // Decrypt user_ids and user_id from request
        if ($request->has('user_ids')) {
            $decryptedUserIds = array_map(function($id) {
                return decryptId($id);
            }, $request->input('user_ids'));
            $request->merge(['user_ids' => $decryptedUserIds]);
        }
        if ($request->has('user_id')) {
            $request->merge(['user_id' => decryptId($request->input('user_id'))]);
        }

        $validated = $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,user_id',
            'user_id' => 'nullable|exists:users,user_id'
        ]);

        $userIds = $validated['user_ids'] ?? [];
        
        // Support single user_id parameter for individual removal
        if (!empty($validated['user_id'])) {
            $userIds[] = $validated['user_id'];
        }

        if (empty($userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No users selected for removal.'
            ], 422);
        }

        $deleted = Group_user::where('group_id', $group->group_id)
            ->whereIn('user_id', $userIds)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted . ' user(s) removed successfully!',
            'removed_count' => $deleted
        ]);
    }

    /**
     * Bulk assign users to multiple groups
     */
    public function bulkAssign(Request $request)
    {
        // Decrypt group_ids and user_ids from request
        if ($request->has('group_ids')) {
            $decryptedGroupIds = array_map(function($id) {
                return decryptId($id);
            }, $request->input('group_ids'));
            $request->merge(['group_ids' => $decryptedGroupIds]);
        }
        if ($request->has('user_ids')) {
            $decryptedUserIds = array_map(function($id) {
                return decryptId($id);
            }, $request->input('user_ids'));
            $request->merge(['user_ids' => $decryptedUserIds]);
        }

        $validated = $request->validate([
            'group_ids' => 'required|array',
            'group_ids.*' => 'exists:groups,group_id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,user_id'
        ]);

        $results = [];

        foreach ($validated['group_ids'] as $groupId) {
            $group = Group::find($groupId);
            $assigned = 0;

            foreach ($validated['user_ids'] as $userId) {
                $existingMember = Group_user::where('group_id', $groupId)
                    ->where('user_id', $userId)
                    ->exists();

                if (!$existingMember) {
                    Group_user::create([
                        'group_id' => $groupId,
                        'user_id' => $userId
                    ]);
                    $assigned++;
                }
            }

            $results[] = [
                'group_id' => $groupId,
                'group_name' => $group->position,
                'assigned_count' => $assigned
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Users assigned to groups successfully!',
            'results' => $results
        ]);
    }

    /**
     * Remove user from all groups
     */
    public function removeFromAllGroups(Request $request, User $user)
    {
        $validated = $request->validate([
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:groups,group_id'
        ]);

        $query = Group_user::where('user_id', $user->user_id);

        // If specific groups provided, remove from those only
        if (!empty($validated['group_ids'])) {
            $query->whereIn('group_id', $validated['group_ids']);
        }

        $deleted = $query->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted . ' group assignment(s) removed!',
            'removed_count' => $deleted
        ]);
    }

    /**
     * Get user's assigned groups
     */
    public function getUserGroups(User $user)
    {
        $groups = $user->groups()->get();

        return response()->json([
            'success' => true,
            'user_id' => $user->user_id,
            'groups' => $groups
        ]);
    }
}
