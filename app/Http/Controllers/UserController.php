<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        $users = $query->with('roles')->orderBy('id', 'desc')->get();
        
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', Password::defaults()],
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        return back()->with('success', 'تم إضافة المستخدم بنجاح.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return back()->with('success', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($request->role !== 'super_admin' && $this->isLastSuperAdmin($user)) {
            return back()->with('error', 'لا يمكن تغيير دور آخر رئيس قسم في النظام.');
        }

        $user->syncRoles([$request->role]);

        return back()->with('success', 'تم تحديث صلاحية المستخدم بنجاح.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'تم إعادة تعيين كلمة المرور بنجاح.');
    }

    public function toggleStatus(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'لا يمكنك تعطيل حسابك الخاص.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return back()->with('success', 'تم تغيير حالة الحساب بنجاح.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'لا يمكنك حذف حسابك الخاص.');
        }

        if ($this->isLastSuperAdmin($user)) {
            return back()->with('error', 'لا يمكن حذف آخر رئيس قسم في النظام.');
        }

        $user->delete();

        return back()->with('success', 'تم حذف المستخدم بنجاح.');
    }

    private function isLastSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin')
            && User::role('super_admin')->count() <= 1;
    }
}