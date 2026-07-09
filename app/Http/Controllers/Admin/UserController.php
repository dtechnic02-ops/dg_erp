<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // 🔥 USER LIST
    public function index(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->role_id == 1, 403);

        $query = User::with('company');

        // 🔍 SEARCH
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // 🚦 STATUS FILTER
        if ($request->filled('status')) {
            $query->where('account_status', $request->status);
        }

        // 👤 ROLE FILTER
        if ($request->filled('role')) {
            $query->where('role_id', $request->role);
        }

        $users = $query->latest()->paginate(10);

        return view('admin.users', compact('users'));
    }

    // 🔥 DELETE USER
    public function delete($id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        $user = User::findOrFail($id);

        // ❗ Prevent self delete
        if ($user->id == auth()->id()) {
            return back()->with('error', 'You cannot delete yourself');
        }

        $user->delete();

        return back()->with('success', 'User Deleted');
    }

    // 🔥 BLOCK USER
    public function block($id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        $user = User::findOrFail($id);

        // ❗ Prevent self block
        if ($user->id == auth()->id()) {
            return back()->with('error', 'You cannot block yourself');
        }

        $user->account_status = 'blocked';
        $user->save();

        return back()->with('success', 'User Blocked');
    }

    // 🔥 UNBLOCK USER
    public function unblock($id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        $user = User::findOrFail($id);

        $user->account_status = 'active';
        $user->save();

        return back()->with('success', 'User Activated');
    }

    // 🔐 RESET PASSWORD
    public function reset($id)
    {
        abort_unless(auth()->user()->role_id == 1, 403);

        $user = User::findOrFail($id);

        // ❗ Prevent self reset
        if ($user->id == auth()->id()) {
            return back()->with('error', 'You cannot reset your own password');
        }

        // 🔐 Secure hashing
        $user->password = Hash::make('123456');
        $user->save();

        return back()->with('success', 'Password reset to 123456');
    }
}