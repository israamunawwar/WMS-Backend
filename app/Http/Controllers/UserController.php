<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // أمين المستودع يرى المدربين فقط
        if (! $this->isHead()) {
            $query->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'admin']));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                                       ->orWhere('email', 'like', "%{$search}%"));
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
            // أمين المستودع يستطيع إنشاء حسابات المدربين فقط
            'role' => ['required', 'string', 'exists:roles,name', Rule::in($this->assignableRoles())],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        ActivityLog::record('user_create', "أنشأ حساب المستخدم \"{$user->name}\" بدور {$request->role}");

        return back()->with('success', 'تم إضافة المستخدم بنجاح.');
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeTarget($user);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        ActivityLog::record('user_update', "حدّث بيانات المستخدم \"{$user->name}\"");

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

        ActivityLog::record('user_role', "غيّر دور المستخدم \"{$user->name}\" إلى {$request->role}");

        return back()->with('success', 'تم تحديث صلاحية المستخدم بنجاح.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeTarget($user);

        $request->validate([
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::record('user_password', "أعاد تعيين كلمة مرور المستخدم \"{$user->name}\"");

        return back()->with('success', 'تم إعادة تعيين كلمة المرور بنجاح.');
    }

    public function toggleStatus(User $user)
    {
        $this->authorizeTarget($user);

        if (auth()->id() === $user->id) {
            return back()->with('error', 'لا يمكنك تعطيل حسابك الخاص.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        ActivityLog::record('user_status', ($user->is_active ? 'فعّل' : 'عطّل')." حساب المستخدم \"{$user->name}\"");

        return back()->with('success', 'تم تغيير حالة الحساب بنجاح.');
    }

    public function destroy(User $user)
    {
        $this->authorizeTarget($user);

        if (auth()->id() === $user->id) {
            return back()->with('error', 'لا يمكنك حذف حسابك الخاص.');
        }

        if ($this->isLastSuperAdmin($user)) {
            return back()->with('error', 'لا يمكن حذف آخر رئيس قسم في النظام.');
        }

        $user->delete();

        ActivityLog::record('user_delete', "حذف حساب المستخدم \"{$user->name}\"");

        return back()->with('success', 'تم حذف المستخدم بنجاح.');
    }

    private function isHead(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    /** الأدوار التي يحق للمستخدم الحالي إسنادها. */
    private function assignableRoles(): array
    {
        return $this->isHead() ? Role::pluck('name')->all() : ['trainer'];
    }

    /** أمين المستودع لا يتعامل مع حسابات رئيس القسم أو أمناء المستودع الآخرين. */
    private function authorizeTarget(User $user): void
    {
        abort_if(! $this->isHead() && $user->hasAnyRole(['super_admin', 'admin']), 403);
    }

    private function isLastSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin')
            && User::role('super_admin')->count() <= 1;
    }
}