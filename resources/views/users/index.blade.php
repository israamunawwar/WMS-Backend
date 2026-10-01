<x-app-layout>
    <div x-data="{ 
        addModal: false, 
        editModal: false, 
        roleModal: false, 
        passwordModal: false, 
        deleteModal: false,
        selectedUser: { id: '', name: '', email: '', role: '', is_active: 1 }
    }">

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-xl text-sm font-bold">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm font-bold">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm font-bold">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-extrabold text-[#005f8a]">إدارة المستخدمين</h2>
                <p class="text-gray-500 text-sm mt-1">يمكنك إضافة، تعديل، تعطيل الحسابات، أو إعادة تعيين كلمات المرور وتغيير الأدوار.</p>
            </div>
            
            <button @click="addModal = true" class="bg-[#00a8e8] hover:bg-[#0073a8] text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition-all transform hover:-translate-y-1 flex items-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                إضافة حساب جديد
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            
            <div class="p-4 border-b border-gray-100 flex justify-end items-center bg-gray-50/50">
                <form method="GET" action="{{ route('users.index') ?? '#' }}" class="relative w-full max-w-sm">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث عن اسم، بريد..." class="w-full pl-10 pr-4 py-2 border-gray-200 rounded-lg focus:ring-[#00a8e8] focus:border-[#00a8e8] text-sm">
                    <button type="submit" class="absolute left-3 top-2.5 text-gray-400 hover:text-[#00a8e8]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4">المستخدم</th>
                            <th class="px-6 py-4">البريد الإلكتروني</th>
                            <th class="px-6 py-4">الدور الحالي</th>
                            <th class="px-6 py-4">الحالة</th>
                            <th class="px-6 py-4 text-center">الإجراءات والعمليات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($users ?? [] as $user)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full {{ $user->hasRole('super_admin') ? 'bg-orange-500' : ($user->hasRole('admin') ? 'bg-[#005f8a]' : 'bg-purple-600') }} text-white flex items-center justify-center font-bold text-sm">
                                        {{ mb_substr($user->name, 0, 1) }}
                                    </div>
                                    <span class="font-bold text-gray-800">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                @if($user->hasRole('super_admin'))
                                    <span class="bg-orange-50 text-orange-700 text-xs font-bold px-3 py-1 rounded-full border border-orange-200">رئيس قسم</span>
                                @elseif($user->hasRole('admin'))
                                    <span class="bg-blue-50 text-[#005f8a] text-xs font-bold px-3 py-1 rounded-full border border-blue-200">أمين مستودع</span>
                                @else
                                    <span class="bg-purple-50 text-purple-700 text-xs font-bold px-3 py-1 rounded-full border border-purple-200">مدرب</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($user->is_active)
                                    <span class="flex items-center gap-1.5 text-green-600 font-bold text-xs">
                                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> نشط
                                    </span>
                                @else
                                    <span class="flex items-center gap-1.5 text-gray-500 font-bold text-xs">
                                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span> معطل
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center items-center gap-2">
                                    @php $userRole = $user->roles->first()?->name ?? 'trainer'; @endphp
                                    
                                    <button @click="selectedUser = {id: '{{ $user->id }}', name: '{{ addslashes($user->name) }}', email: '{{ addslashes($user->email) }}', role: '{{ $userRole }}', is_active: {{ $user->is_active ? 1 : 0 }} }, editModal = true" class="p-2 text-gray-500 hover:text-[#005f8a] hover:bg-gray-100 rounded-xl transition-colors" title="تعديل البيانات الأساسية">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    
                                    @role('super_admin')
                                    <button @click="selectedUser = {id: '{{ $user->id }}', name: '{{ addslashes($user->name) }}', role: '{{ $userRole }}', is_active: {{ $user->is_active ? 1 : 0 }} }, roleModal = true" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors" title="تغيير صلاحية الدور">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2v-5M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </button>
                                    @endrole

                                    <button @click="selectedUser = {id: '{{ $user->id }}', name: '{{ addslashes($user->name) }}', is_active: {{ $user->is_active ? 1 : 0 }} }, passwordModal = true" class="p-2 text-gray-500 hover:text-orange-600 hover:bg-orange-50 rounded-xl transition-colors" title="إعادة تعيين كلمة السر">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                    </button>

                                    <button @click="selectedUser = {id: '{{ $user->id }}', name: '{{ addslashes($user->name) }}', is_active: {{ $user->is_active ? 1 : 0 }} }, deleteModal = true" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors" title="تعطيل أو حذف الحساب">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 font-bold">لا توجد حسابات مسجلة</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="addModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-transition x-cloak>
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl" @click.away="addModal = false">
                <h3 class="text-lg font-black text-[#005f8a] mb-4">إضافة حساب جديد</h3>
                <form action="{{ route('users.store') ?? '#' }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">الاسم الكامل</label>
                        <input type="text" name="name" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">البريد الإلكتروني الجامعي</label>
                        <input type="email" name="email" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5" placeholder="username@it.edu">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">تحديد صلاحية الدور</label>
                        <select name="role" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                            <option value="trainer">مدرب</option>
                            @role('super_admin')
                                <option value="admin">أمين مستودع</option>
                                <option value="super_admin">رئيس قسم</option>
                            @endrole
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">كلمة المرور الافتراضية</label>
                        <input type="password" name="password" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="addModal = false" class="px-4 py-2 border rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-50">إلغاء</button>
                        <button type="submit" class="px-4 py-2 bg-[#00a8e8] text-white rounded-xl text-sm font-bold hover:bg-[#0073a8]">حفظ الحساب</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="editModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-transition x-cloak>
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl" @click.away="editModal = false">
                <h3 class="text-lg font-black text-[#005f8a] mb-4">تعديل بيانات الحساب</h3>
                <form :action="'{{ url('users') }}/' + selectedUser.id" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">الاسم الكامل</label>
                        <input type="text" name="name" :value="selectedUser.name" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">البريد الإلكتروني</label>
                        <input type="email" name="email" :value="selectedUser.email" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="editModal = false" class="px-4 py-2 border rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-50">إلغاء</button>
                        <button type="submit" class="px-4 py-2 bg-[#005f8a] text-white rounded-xl text-sm font-bold hover:bg-[#0073a8]">تحديث البيانات</button>
                    </div>
                </form>
            </div>
        </div>

        @role('super_admin')
        <div x-show="roleModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-transition x-cloak>
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl" @click.away="roleModal = false">
                <h3 class="text-lg font-black text-indigo-900 mb-2">تغيير دور الصلاحية</h3>
                <p class="text-gray-500 text-xs mb-4">المستخدم الحالي: <span class="font-bold text-gray-800" x-text="selectedUser.name"></span></p>
                <form :action="'{{ url('users') }}/' + selectedUser.id + '/role'" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">اختر الدور الجديد</label>
                        <select name="role" x-model="selectedUser.role" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                            <option value="trainer">مدرب</option>
                            <option value="admin">أمين مستودع</option>
                            <option value="super_admin">رئيس قسم</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="roleModal = false" class="px-4 py-2 border rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-50">إلغاء</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700">تحديث الصلاحية</button>
                    </div>
                </form>
            </div>
        </div>
        @endrole

        <div x-show="passwordModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-transition x-cloak>
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl" @click.away="passwordModal = false">
                <h3 class="text-lg font-black text-orange-900 mb-2">إعادة تعيين كلمة السر</h3>
                <p class="text-gray-500 text-xs mb-4">توليد كلمة سر جديدة للحساب: <span class="font-bold text-gray-800" x-text="selectedUser.name"></span></p>
                <form :action="'{{ url('users') }}/' + selectedUser.id + '/password'" method="POST" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">كلمة المرور الجديدة</label>
                        <input type="password" name="password" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5" placeholder="••••••••">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="passwordModal = false" class="px-4 py-2 border rounded-xl text-sm font-bold text-gray-500 hover:bg-gray-50">إلغاء</button>
                        <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-xl text-sm font-bold hover:bg-orange-600">تغيير كلمة السر</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-transition x-cloak>
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl" @click.away="deleteModal = false">
                <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center mb-4 mx-auto text-xl">⚠️</div>
                <h3 class="text-lg font-black text-center text-red-900 mb-2">إدارة حالة الحساب</h3>
                <p class="text-gray-500 text-sm text-center mb-6">أنت على وشك تعديل حالة حساب المستخدم: <br><span class="font-bold text-gray-800" x-text="selectedUser.name"></span></p>
                
                <div class="grid grid-cols-2 gap-3">
                    <form :action="'{{ url('users') }}/' + selectedUser.id + '/status'" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full py-2.5 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors"
                                x-text="selectedUser.is_active ? 'تعطيل الحساب مؤقتاً' : 'تفعيل الحساب'">
                        </button>
                    </form>
                    
                    <form :action="'{{ url('users') }}/' + selectedUser.id" method="POST" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('هل أنت متأكد من حذف الحساب نهائياً؟')" class="w-full py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 transition-all shadow-md shadow-red-100">
                            حذف نهائي
                        </button>
                    </form>
                </div>
                <button @click="deleteModal = false" class="w-full mt-3 py-2 text-xs font-bold text-gray-400 hover:text-gray-600 text-center">إلغاء العملية</button>
            </div>
        </div>

    </div>
</x-app-layout>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">