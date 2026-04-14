<?php
namespace App\Http\Controllers;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Group_user;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display the user's profile
     *
     * @return \Illuminate\Http\Response
     */
    public function viewProfile()
    {
        $user = auth()->user();

        $activityLogs = ActivityLog::where('user_id', $user->user_id)
            ->latest('created_at')
            ->limit(20)
            ->get();

        $userGroups = Group_user::with([
                'group.members.user.employee',
                'group.members.user',
            ])
            ->where('user_id', $user->user_id)
            ->get()
            ->pluck('group')
            ->filter()
            ->unique('group_id')
            ->values();

        

        return view('content.profile.view-profile', compact('activityLogs', 'userGroups'));
    }

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
     * Fetch HRMIS credentials by account (email).
     */
    public function fetchHrmisCredentials(Request $request)
    {
        $validated = $request->validate([
            'hrmis_account' => 'required|email'
        ]);

        $response = Http::withToken(config('services.hrmis_api.token'))
            ->timeout(10)
            ->post(config('services.hrmis_api.url'), [
                'email' => $validated['hrmis_account'],
            ]);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch HRMIS credentials at this time.'
            ], 422);
        }

        $hrmisData = $response->json('data');

        if (empty($hrmisData) || !is_array($hrmisData)) {
            return response()->json([
                'success' => false,
                'message' => 'HRMIS account not found.'
            ], 404);
        }

        $departmentId = data_get($hrmisData, 'department.id');
        $departmentName = data_get($hrmisData, 'department.DepartmentName');

        if (!empty($departmentId) && !empty($departmentName)) {
            Department::updateOrCreate(
                ['department_id' => $departmentId],
                [
                    'department_name' => $departmentName,
                    'campus' => $hrmisData['Campus'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'first_name' => $hrmisData['FirstName'] ?? '',
                'last_name' => $hrmisData['LastName'] ?? '',
                'email' => $hrmisData['Email'] ?? $validated['hrmis_account'],
                'campus' => $hrmisData['Campus'] ?? '',
                'department_id' => $departmentId,
                'department_name' => $departmentName,
            ]
        ]);
    }

    /**
     * Register account from HRMIS credentials and assign a local role.
     */
    public function registerAccount(Request $request)
    {
        $validated = $request->validate([
            'hrmis_account' => 'required|email',
            'role' => 'nullable|in:admin,superadmin,user'
        ]);

        $response = Http::withToken(config('services.hrmis_api.token'))
            ->timeout(10)
            ->post(config('services.hrmis_api.url'), [
                'email' => $validated['hrmis_account'],
            ]);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch HRMIS credentials at this time.'
            ], 422);
        }

        $hrmisData = $response->json('data');

        if (empty($hrmisData) || !is_array($hrmisData)) {
            return response()->json([
                'success' => false,
                'message' => 'HRMIS account not found.'
            ], 404);
        }

        $email = $hrmisData['Email'] ?? $validated['hrmis_account'];

        if (User::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'An account with this HRMIS email already exists.'
            ], 422);
        }

        $departmentId = data_get($hrmisData, 'department.id');
        $departmentName = data_get($hrmisData, 'department.DepartmentName');

        if (!empty($departmentId) && !empty($departmentName)) {
            Department::updateOrCreate(
                ['department_id' => $departmentId],
                [
                    'department_name' => $departmentName,
                    'campus' => $hrmisData['Campus'] ?? null,
                ]
            );
        }

        $user = User::create([
            'email' => $email,
            'password' => bcrypt(Str::random(16)),
            'google_id' => null
        ]);

        Employee::create([
            'user_id' => $user->user_id,
            'firstname' => $hrmisData['FirstName'] ?? '',
            'lastname' => $hrmisData['LastName'] ?? '',
            'campus' => $hrmisData['Campus'] ?? '',
            'department_id' => $departmentId,
            'role' => $validated['role'] ?? 'user'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account registered successfully from HRMIS credentials.'
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
        // Prevent users from deleting their own account
        if (auth()->user()->user_id === $user->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.'
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

    /**
     * Display the edit profile form
     *
     * @return \Illuminate\Http\Response
     */
    public function editProfile()
    {
        $departments = Department::all();
        return view('content.profile.edit-profile', compact('departments'));
    }

    /**
     * Update the user's profile
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')
            ],
            'firstname' => 'required|string|min:2|max:50',
            'lastname' => 'required|string|min:2|max:50',
            'contact_number' => 'nullable|string|max:20',
            'department_id' => 'nullable|integer|exists:departments,department_id',
            'position' => 'nullable|string|max:100',
        ]);

        // Update user email
        if ($user->email !== $validated['email']) {
            $user->update(['email' => $validated['email']]);
        }

        // Update employee information
        if ($user->employee) {
            $user->employee->update([
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'contact_number' => $validated['contact_number'],
                'department_id' => $validated['department_id'],
                'position' => $validated['position'],
            ]);

            // Update user name
            $user->update([
                'name' => $validated['firstname'] . ' ' . $validated['lastname']
            ]);
            
        }
        

        return redirect()->route('profile.view')->with('success', 'Profile updated successfully!');
    }
}