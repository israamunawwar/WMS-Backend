<x-app-layout>
    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-xl text-sm font-bold">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm font-bold">{{ session('error') }}</div>
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

    <div class="mb-6 flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-extrabold text-[#005f8a]">إعدادات النظام</h2>
            <p class="text-gray-500 text-sm mt-1">تكوين الخصائص الأساسية، التصنيفات، والقيم الافتراضية للعمليات.</p>
        </div>
        <button type="submit" form="settings-form" class="bg-[#00a8e8] text-white font-bold py-2.5 px-6 rounded-xl hover:bg-[#0073a8] transition-colors shadow-md">حفظ كافة التعديلات</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <form id="settings-form" method="POST" action="{{ route('settings.update') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            @csrf
            @method('PUT')
            <h3 class="font-black text-gray-800 mb-4 border-r-4 border-[#00a8e8] pr-3">القيم الافتراضية والحدود</h3>
            <div class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">اسم النظام</label>
                    <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required class="w-full border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-[#00a8e8]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">الحد الأدنى للمخزون (الافتراضي)</label>
                    <div class="relative">
                        <input type="number" min="1" name="low_stock_threshold" value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}" required class="w-full border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-[#00a8e8]">
                        <span class="absolute left-4 top-2.5 text-xs text-gray-400 font-bold">وحدة</span>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1">المواد التي يصل رصيدها لهذا الرقم أو أقل (وأكبر من صفر) تُحسب منخفضة المخزون في الداشبورد وقائمة المواد.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">مدة الإعارة الافتراضية (للمدربين)</label>
                    <div class="relative">
                        <input type="number" min="1" name="default_borrow_days" value="{{ old('default_borrow_days', $settings['default_borrow_days']) }}" required class="w-full border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-[#00a8e8]">
                        <span class="absolute left-4 top-2.5 text-xs text-gray-400 font-bold">يوم</span>
                    </div>
                </div>
            </div>
        </form>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-black text-gray-800 mb-4 border-r-4 border-purple-500 pr-3">إدارة تصنيفات المواد</h3>
            <form method="POST" action="{{ route('settings.categories.store') }}" class="flex gap-2 mb-4">
                @csrf
                <input type="text" name="name" required placeholder="إضافة تصنيف جديد..." class="flex-1 border-gray-200 rounded-xl text-sm px-3 py-2">
                <button type="submit" class="bg-gray-100 text-gray-600 font-bold px-4 rounded-xl text-sm hover:bg-gray-200">إضافة</button>
            </form>
            <div class="flex flex-wrap gap-2">
                @forelse($categories as $category)
                    <span class="bg-purple-50 text-purple-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-purple-100 flex items-center gap-2">
                        {{ $category->name }}
                        <span class="text-purple-400 font-normal">({{ $category->items_count }})</span>
                        <form method="POST" action="{{ route('settings.categories.destroy', $category) }}" onsubmit="return confirm('حذف التصنيف «{{ $category->name }}»؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-purple-400 hover:text-red-500" title="حذف">×</button>
                        </form>
                    </span>
                @empty
                    <p class="text-xs text-gray-400 font-bold">لا توجد تصنيفات بعد.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:col-span-2">
            <h3 class="font-black text-gray-800 mb-4 border-r-4 border-orange-500 pr-3">حالات المواد (للاطلاع)</h3>
            <div class="flex flex-wrap gap-3">
                <div class="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="text-sm font-bold text-gray-700">متاح</span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    <span class="text-sm font-bold text-gray-700">قيد الصيانة</span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span class="text-sm font-bold text-gray-700">تالف / متلف</span>
                </div>
            </div>
            <p class="text-[10px] text-gray-400 mt-3">تُحدَّد حالة المادة تلقائياً من سجلات الصيانة والأرصدة.</p>
        </div>

    </div>
</x-app-layout>
