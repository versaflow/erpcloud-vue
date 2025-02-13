<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // Remove the constructor since we're handling middleware in routes

    public function index()
    {
        return Inertia::render('Users/Index', [
            'users' => User::with('department')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create', [
            'departments' => Department::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,agent,user',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'department_id' => $validated['department_id'],
            'is_admin' => $validated['role'] === User::ROLE_ADMIN,
            'is_agent' => $validated['role'] === User::ROLE_AGENT,
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function edit(User $user)
    {
        return Inertia::render('Users/Edit', [
            'user' => $user,
            'departments' => Department::all()
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'department_id' => 'required|exists:departments,id',
            'is_admin' => 'boolean',
            'is_agent' => 'boolean',
        ]);

        $user->update($validated);

        return redirect()->route('users.index');
    }

    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('message', 'Password updated successfully');
    }

    public function destroy(User $user)
    {
        if ($user->getKey() === Auth::id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account');
        }

        $user->delete();
        return redirect()->route('users.index');
    }
}
