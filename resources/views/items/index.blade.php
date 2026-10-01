<x-app-layout>
    @php
        $oldForm = old('_form');
        $exportQuery = array_filter(['type' => $currentType !== 'all' ? $currentType : null, 'search' => request('search'), 'category' => request('category')]);
        $lowThreshold = \App\Models\Setting::lowStockThreshold();
    @endphp

    <div x-data="{
        addModal: {{ $errors->any() && $oldForm === 'add' ? 'true' : 'false' }},
        editModal: {{ $errors->any() && $oldForm === 'edit' ? 'true' : 'false' }},
        restockModal: {{ $errors->any() && $oldForm === 'restock' ? 'true' : 'false' }},
        deleteModal: false,
        selected: @js($oldForm && old('_id') ? ['id' => old('_id'), 'name' => old('_name'), 'name_en' => old('name_en'), 'name_ar' => old('name_ar'), 'barcode' => old('barcode'), 'category_id' => old('category_id'), 'location_id' => old('location_id')] : ['id' => '', 'name' => '', 'name_en' => '', 'name_ar' => '', 'barcode' => '', 'category_id' => '', 'location_id' => ''])
    }">

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

        <div class="mb-6 flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <div>
                <h2 class="text-2xl font-extrabold text-[#005f8a]">{{ $pageTitle }}</h2>
                <p class="text-gray-500 text-sm mt-1">عرض تفاصيل المواد وإدارتها وإمكانية تصديرها.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @hasanyrole('super_admin|admin')
                    <button @click="addModal = true" class="bg-[#00a8e8] hover:bg-[#0073a8] text-white font-bold py-2 px-4 rounded-xl shadow-md text-sm flex items-center gap-2">
                        <i class="fas fa-plus"></i> إضافة مادة
                    </button>
                @endhasanyrole
                <a href="{{ route('items.export.excel', $exportQuery) }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-xl shadow-md text-sm flex items-center gap-2">
                    <i class="fas fa-file-excel"></i> تصدير Excel
                </a>
                <a href="{{ route('items.export.pdf', $exportQuery) }}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-xl shadow-md text-sm flex items-center gap-2">
                    <i class="fas fa-file-pdf"></i> تصدير PDF
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('items.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 p-4 flex flex-wrap gap-4 items-center">
            @if($currentType !== 'all')
                <input type="hidden" name="type" value="{{ $currentType }}">
            @endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث بالاسم أو الباركود..." class="flex-1 min-w-[220px] border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-[#00a8e8] focus:border-[#00a8e8]">
            <select name="category" onchange="this.form.submit()" class="border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-[#00a8e8] focus:border-[#00a8e8]">
                <option value="">كل الأصناف</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl text-sm transition-colors">تصفية</button>
            @if(request()->hasAny(['search', 'category', 'type']))
                <a href="{{ route('items.index') }}" class="text-sm font-bold text-gray-400 hover:text-gray-600">مسح</a>
            @endif
        </form>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4">الرقم</th>
                            <th class="px-6 py-4">الباركود</th>
                            <th class="px-6 py-4">الاسم (عربي)</th>
                            <th class="px-6 py-4">الاسم (إنكليزي)</th>
                            <th class="px-6 py-4">الصنف</th>
                            <th class="px-6 py-4">الموقع</th>
                            <th class="px-6 py-4">الرصيد الأساسي</th>
                            <th class="px-6 py-4 text-[#00a8e8]">الرصيد الحالي</th>
                            @hasanyrole('super_admin|admin')
                                <th class="px-6 py-4 text-center">الإجراءات</th>
                            @endhasanyrole
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($items as $item)
                            @php
                                $data = [
                                    'id' => $item->id,
                                    'name' => $item->name_ar ?? $item->name_en,
                                    'name_en' => $item->name_en,
                                    'name_ar' => $item->name_ar,
                                    'barcode' => $item->barcode,
                                    'category_id' => $item->category_id,
                                    'location_id' => $item->location_id,
                                ];
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-500">{{ $item->id }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $item->barcode ?? '-' }}</td>
                                <td class="px-6 py-4 font-bold text-gray-800">{{ $item->name_ar ?? 'غير محدد' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $item->name_en }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $item->category?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $item->location?->label ?? '-' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $item->initial_balance }}</td>
                                <td class="px-6 py-4 font-black {{ $item->current_stock <= $lowThreshold ? 'text-red-500' : 'text-green-600' }}">{{ $item->current_stock }}</td>
                                @hasanyrole('super_admin|admin')
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center items-center gap-1">
                                            <button @click="selected = @js($data); restockModal = true" title="توريد كمية" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-xl transition-colors"><i class="fas fa-box-open"></i></button>
                                            <button @click="selected = @js($data); editModal = true" title="تعديل" class="p-2 text-gray-500 hover:text-[#005f8a] hover:bg-gray-100 rounded-xl transition-colors"><i class="fas fa-pen"></i></button>
                                            <button @click="selected = @js($data); deleteModal = true" title="حذف" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                @endhasanyrole
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-gray-400 font-bold">لا يوجد مواد تطابق هذا البحث</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())
                <div class="p-4 border-t border-gray-100">{{ $items->links() }}</div>
            @endif
        </div>

        @hasanyrole('super_admin|admin')
            {{-- إضافة مادة --}}
            <div x-show="addModal" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-transition>
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="addModal = false">
                    <h3 class="text-xl font-black text-[#005f8a] mb-4">إضافة مادة جديدة</h3>
                    <form action="{{ route('items.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="_form" value="add">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">الاسم (عربي)</label>
                                <input type="text" name="name_ar" value="{{ $oldForm === 'add' ? old('name_ar') : '' }}" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">الاسم (إنكليزي) *</label>
                                <input type="text" name="name_en" value="{{ $oldForm === 'add' ? old('name_en') : '' }}" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">الباركود</label>
                                <input type="text" name="barcode" value="{{ $oldForm === 'add' ? old('barcode') : '' }}" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">الرصيد الافتتاحي *</label>
                                <input type="number" min="0" name="initial_balance" value="{{ $oldForm === 'add' ? old('initial_balance', 0) : 0 }}" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">الصنف *</label>
                                <select name="category_id" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                                    <option value="">اختر الصنف...</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected($oldForm === 'add' && old('category_id') == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">موقع التخزين</label>
                                <select name="location_id" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                                    <option value="">بدون موقع</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" @selected($oldForm === 'add' && old('location_id') == $location->id)>{{ $location->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if($categories->isEmpty())
                            <p class="text-xs text-orange-600 font-bold">لا توجد أصناف. أضيفي صنفاً من صفحة الإعدادات أولاً.</p>
                        @endif
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="addModal = false" class="px-4 py-2 border rounded-xl text-sm font-bold text-gray-500">إلغاء</button>
                            <button type="submit" class="px-4 py-2 bg-[#00a8e8] text-white rounded-xl text-sm font-bold shadow-md">حفظ المادة</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- تعديل مادة --}}
            <div x-show="editModal" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-transition>
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="editModal = false">
                    <h3 class="text-xl font-black text-[#005f8a] mb-1">تعديل بيانات المادة</h3>
                    <p class="text-[11px] text-gray-400 mb-4">الأرصدة لا تُعدَّل من هنا: تتغير بالطلبات والجرد والتوريد.</p>
                    <form :action="'{{ url('items') }}/' + selected.id" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_form" value="edit">
                        <input type="hidden" name="_id" :value="selected.id">
                        <input type="hidden" name="_name" :value="selected.name">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">الاسم (عربي)</label>
                                <input type="text" name="name_ar" :value="selected.name_ar" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">الاسم (إنكليزي) *</label>
                                <input type="text" name="name_en" :value="selected.name_en" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-1">الباركود</label>
                                <input type="text" name="barcode" :value="selected.barcode" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">الصنف *</label>
                                <select name="category_id" x-model="selected.category_id" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">موقع التخزين</label>
                                <select name="location_id" x-model="selected.location_id" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                                    <option value="">بدون موقع</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="editModal = false" class="px-4 py-2 border rounded-xl text-sm font-bold text-gray-500">إلغاء</button>
                            <button type="submit" class="px-4 py-2 bg-[#005f8a] text-white rounded-xl text-sm font-bold shadow-md">تحديث البيانات</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- توريد كمية --}}
            <div x-show="restockModal" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-transition>
                <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl" @click.away="restockModal = false">
                    <h3 class="text-xl font-black text-green-800 mb-1">توريد كمية جديدة</h3>
                    <p class="text-xs text-gray-500 mb-4">المادة: <span class="font-bold text-gray-800" x-text="selected.name"></span></p>
                    <form :action="'{{ url('items') }}/' + selected.id + '/restock'" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="_form" value="restock">
                        <input type="hidden" name="_id" :value="selected.id">
                        <input type="hidden" name="_name" :value="selected.name">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">الكمية الواردة *</label>
                            <input type="number" min="1" name="quantity" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">ملاحظة (مورّد، رقم فاتورة...)</label>
                            <input type="text" name="note" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="restockModal = false" class="px-4 py-2 border rounded-xl text-sm font-bold text-gray-500">إلغاء</button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-xl text-sm font-bold shadow-md">تأكيد التوريد</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- حذف مادة --}}
            <div x-show="deleteModal" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-transition>
                <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl text-center" @click.away="deleteModal = false">
                    <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center mb-4 mx-auto text-xl">⚠️</div>
                    <h3 class="text-lg font-black text-red-900 mb-2">حذف المادة</h3>
                    <p class="text-gray-500 text-sm mb-6">هل تريد حذف <span class="font-bold text-gray-800" x-text="selected.name"></span> نهائياً؟<br><span class="text-[11px]">لا يمكن حذف مادة لها طلبات أو جرد أو صيانة مسجلة.</span></p>
                    <form :action="'{{ url('items') }}/' + selected.id" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="flex gap-2">
                            <button type="button" @click="deleteModal = false" class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-bold text-gray-600">تراجع</button>
                            <button type="submit" class="flex-1 py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold shadow-md">حذف نهائي</button>
                        </div>
                    </form>
                </div>
            </div>
        @endhasanyrole

    </div>
</x-app-layout>
