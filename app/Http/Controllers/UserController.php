<?php

namespace App\Http\Controllers;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\Response
     */
      public function index()
    {
        // Get all users with their employee and department info
        $users = User::with(['employee.department'])->get();

        // Get unique campus values from employees table
        $campuses = Employee::whereNotNull('campus')
            ->distinct()
            ->pluck('campus')
            ->filter()
            ->sort()
            ->values();

        // Get all departments for the add user modal
        $departments = Department::all();

        return view('content.users.users-list', compact('users', 'campuses', 'departments'));
    }

    /**
     * Change user role in employee table
     *
     * @param User $user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeRole(User $user, Request $request)
    {
        $request->validate([
            'role' => 'required|in:admin,superadmin,user'
        ]);

        // Check if user has employee record
        if (!$user->employee) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have an employee record'
            ], 404);
        }

        // Update role in employee table
        $user->employee->update([
            'role' => $request->role
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'new_role' => $request->role
        ]);
    }

    /**
     * Create a user with credentials from Employee and User models
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTestUser(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'campus' => 'required|string|max:50',
            'department_id' => 'required|integer|exists:departments,department_id',
            'role' => 'required|in:admin,superadmin,user'
        ]);

        // Create user with credentials from User model
        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'google_id' => null
        ]);

        // Create employee record with credentials from Employee model
        Employee::create([
            'user_id' => $user->user_id,
            'firstname' => $request->first_name,
            'lastname' => $request->last_name,
            'campus' => $request->campus,
            'department_id' => $request->department_id,
            'role' => $request->role
        ]);

        return response()->json([
            'success' => true,
            'message' => "User '{$user->name}' created successfully!\n\nEmail: {$request->email}\nPassword: {$request->password}\n\nRole: {$request->role}\nCampus: {$request->campus}"
        ]);
    }

    /**
     * Update a user (non-Google accounts only)
     */
    public function update(Request $request, User $user)
    {
        if (!is_null($user->google_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Google-auth users cannot be edited.'
            ], 403);
        }

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')
            ],
            'password' => 'nullable|string|min:6',
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'campus' => 'required|string|max:50',
            'department_id' => 'required|integer|exists:departments,department_id',
            'role' => 'required|in:admin,superadmin,user'
        ]);

        $user->update([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $validated['email'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => bcrypt($validated['password'])]);
        }

        Employee::updateOrCreate(
            ['user_id' => $user->user_id],
            [
                'firstname' => $validated['first_name'],
                'lastname' => $validated['last_name'],
                'campus' => $validated['campus'],
                'department_id' => $validated['department_id'],
                'role' => $validated['role']
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.'
        ]);
    }

    /**
     * Delete a user (non-Google accounts only)
     */
    public function destroy(User $user)
    {
        if (!is_null($user->google_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Google-auth users cannot be deleted.'
            ], 403);
        }

        try {
            // Disable foreign key checks temporarily
            \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // 1. Delete received documents for this user
            \App\Models\ReceivedDocument::where('user_id', $user->user_id)->delete();

            // 2. Delete all SentDocuments where this user is a recipient (via recipient records)
            $recipientIds = \App\Models\Recipient::where('user_id', $user->user_id)
                ->pluck('recipient_id')
                ->toArray();
            
            if (!empty($recipientIds)) {
                \App\Models\SentDocument::whereIn('recipient_id', $recipientIds)->delete();
            }

            // 3. Delete recipient records where this user is a recipient
            \App\Models\Recipient::where('user_id', $user->user_id)->delete();

            // 4. Delete all documents sent by this user (with all related data)
            $documents = \App\Models\Document::where('user_id', $user->user_id)->get();
            foreach ($documents as $document) {
                $routes = \App\Models\DocumentRoute::where('document_id', $document->document_id)->get();
                foreach ($routes as $route) {
                    // Delete ReceivedDocuments first (they reference SentDocuments via sent_id)
                    \App\Models\ReceivedDocument::where('route_id', $route->route_id)->delete();
                    
                    // Delete SentDocuments (now safe after ReceivedDocuments are gone)
                    \App\Models\SentDocument::where('route_id', $route->route_id)->delete();
                    
                    // Delete Recipients
                    \App\Models\Recipient::where('route_id', $route->route_id)->delete();
                    
                    // Delete DocumentRoute
                    $route->delete();
                }
                
                // Delete Document
                $document->delete();
            }

            // 5. Delete group memberships for this user
            \App\Models\Group_user::where('user_id', $user->user_id)->delete();

            // 6. Delete employee record
            if ($user->employee) {
                $user->employee->delete();
            }

            // 7. Delete user account
            $user->delete();

            // Re-enable foreign key checks
            \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return response()->json([
                'success' => true,
                'message' => 'User and all associated data deleted successfully.'
            ]);
        } catch (\Exception $e) {
            // Re-enable foreign key checks in case of error
            \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            return response()->json([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], 500);
        }
    }
}
