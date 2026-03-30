<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::orderBy('created_at', 'desc')
            ->paginate(30);
        return view('content.activity-logs.users-activity-log', compact('logs'));
    }
    public function deleteAll()
    {
        ActivityLog::query()->delete();
        return redirect()->route('user.activity-logs')->with('success', 'All activity logs have been deleted.');
    }
}
