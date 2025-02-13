<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index()
    {
        return Inertia::render('Departments/Index', [
            'departments' => Department::withCount('users')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments'
        ]);

        Department::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id
        ]);

        $department->update($validated);

        return redirect()->back();
    }

    public function destroy(Department $department)
    {
        if ($department->users()->count() > 0) {
            return redirect()->back()->withErrors(['error' => 'Cannot delete department with assigned users']);
        }

        $department->delete();
        return redirect()->back();
    }
}
