<x-app-layout>
    <div x-data="{
        reportModal: {{ $errors->any() && old('item_id') !== null ? 'true' : 'false' }},
        actionModal: false,
        decision: '',
        selected: { id: '', name: '', status: '' },
        labels: {
            repair: { title: 'إرسال للصيانة', color: 'blue', field: 'جهة الصيانة المعتمدة', submit: 'تأكيد الإرسال للصيانة' },
            replace: { title: 'استبدال من المخزون', color: 'orange', field: 'ملاحظات / سبب الاستبدال', submit: 'تأكيد الاستبدال' },
            scrap: { title: 'إتلاف نهائي', color: 'red', field: 'ملاحظات / سبب الإتلاف', submit: 'تأكيد الإتلاف' },
            fix: { title: 'تحديث: تم الإصلاح', color: 'green', field: 'ملاحظات (اختياري)', submit: 'تأكيد الإصلاح' }
        },
        open(decision, item) { this.decision = decision; this.selected = item; this.actionModal = true; }
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

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-extrabold text-[#005f8a]">إدارة الصيانة والتوالف</h2>
                <p class="text-gray-500 text-sm mt-1">مراقبة المواد المتعطلة واتخاذ القرارات الإدارية (إصلاح، إتلاف، أو استبدال).</p>
            </div>
            <button @click="reportModal = true" class="bg-[#00a8e8] hover:bg-[#0073a8] text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition-all flex items-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                الإبلاغ عن عطل
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-5 rounded-2xl shadow-sm border-b-4 border-red-500 flex justify-between items-center">
                <div>
                    <span class="text-xs font-bold text-gray-400 block mb-1">مواد تالفة / خارج الخدمة</span>
                    <span class="text-2xl font-black text-gray-800">{{ $damagedCount }}</span>
                </div>
                <div class="w-12 h-12 bg-red-50 text-red-500 rounded-full flex items-center justify-center"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border-b-4 border-orange-500 flex justify-between items-center">
                <div>
                    <span class="text-xs font-bold text-gray-400 block mb-1">مواد قيد الصيانة</span>
                    <span class="text-2xl font-black text-gray-800">{{ $repairingCount }}</span>
                </div>
                <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg></div>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border-b-4 border-blue-500 flex justify-between items-center">
                <div>
                    <span class="text-xs font-bold text-gray-400 block mb-1">بانتظار قرار اللجنة</span>
                    <span class="text-2xl font-black text-gray-800">{{ $pendingCount }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex gap-2">
                <a href="{{ route('maintenance.index') }}" class="px-4 py-2 text-sm font-bold rounded-lg {{ $filter === 'all' ? 'bg-[#005f8a] text-white shadow-md' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200' }}">الكل</a>
                <a href="{{ route('maintenance.index', ['filter' => 'pending']) }}" class="px-4 py-2 text-sm font-bold rounded-lg {{ $filter === 'pending' ? 'bg-[#005f8a] text-white shadow-md' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200' }}">بانتظار القرار</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4">الرمز</th>
                            <th class="px-6 py-4">اسم المادة</th>
                            <th class="px-6 py-4">وصف العطل</th>
                            <th class="px-6 py-4">الحالة الحالية</th>
                            <th class="px-6 py-4 text-center">اتخاذ قرار (لجنة التقنية)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($maintenances as $m)
                            @php
                                $name = $m->item?->name_ar ?? $m->item?->name_en ?? 'مادة محذوفة';
                                $badge = match ($m->status) {
                                    'pending' => 'bg-red-50 text-red-600 border-red-100',
                                    'repairing' => 'bg-orange-50 text-orange-600 border-orange-100',
                                    'fixed' => 'bg-green-50 text-green-600 border-green-100',
                                    default => 'bg-gray-100 text-gray-600 border-gray-200',
                                };
                                $info = ['id' => $m->id, 'name' => $name, 'status' => $m->status];
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-500">{{ $m->item?->barcode ?? '#'.$m->item_id }}</td>
                                <td class="px-6 py-4 font-bold text-gray-800">{{ $name }}</td>
                                <td class="px-6 py-4 text-gray-600 max-w-xs truncate" title="{{ $m->description }}">{{ $m->description }}</td>
                                <td class="px-6 py-4">
                                    <span class="{{ $badge }} text-xs font-black px-3 py-1 rounded-full border">{{ $m->status_label }}</span>
                                    @if($m->status === 'fixed' && $m->cost > 0)
                                        <span class="text-xs text-gray-400 mr-1">({{ number_format($m->cost) }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center items-center gap-2">
                                        @if($m->status === 'pending')
                                            <button @click="open('repair', @js($info))" class="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold hover:bg-blue-100 border border-blue-200">إرسال للصيانة</button>
                                            <button @click="open('replace', @js($info))" class="px-3 py-1.5 bg-orange-50 text-orange-600 rounded-lg text-xs font-bold hover:bg-orange-100 border border-orange-200">استبدال من المخزون</button>
                                            <button @click="open('scrap', @js($info))" class="px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-xs font-bold hover:bg-red-100 border border-red-200">إتلاف نهائي</button>
                                        @elseif($m->status === 'repairing')
                                            <button @click="open('fix', @js($info))" class="px-3 py-1.5 bg-green-50 text-green-600 rounded-lg text-xs font-bold hover:bg-green-100 border border-green-200">تم الإصلاح</button>
                                            <button @click="open('scrap', @js($info))" class="px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-xs font-bold hover:bg-red-100 border border-red-200">إتلاف نهائي</button>
                                        @else
                                            <span class="text-xs text-gray-400 font-bold">{{ $m->notes ?: 'تم إغلاق السجل' }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-bold">لا توجد سجلات صيانة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($maintenances->hasPages())
                <div class="p-4 border-t border-gray-100">{{ $maintenances->links() }}</div>
            @endif
        </div>

        <div x-show="reportModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-transition>
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl" @click.away="reportModal = false">
                <h3 class="text-xl font-black text-[#005f8a] mb-4">الإبلاغ عن عطل</h3>
                <form action="{{ route('maintenance.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">المادة</label>
                        <select name="item_id" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                            <option value="">اختر المادة...</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>{{ $item->name_ar ?? $item->name_en }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">وصف العطل</label>
                        <textarea name="description" required class="w-full border-gray-200 rounded-xl text-sm p-3 h-24 bg-gray-50" placeholder="مثال: شاشة مكسورة، لا تقلع...">{{ old('description') }}</textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="reportModal = false" class="px-4 py-2 border rounded-xl text-sm font-bold text-gray-500">إلغاء</button>
                        <button type="submit" class="px-4 py-2 bg-[#00a8e8] text-white rounded-xl text-sm font-bold shadow-md">تسجيل العطل</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="actionModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-transition>
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl" @click.away="actionModal = false">
                <h3 class="text-xl font-black mb-2 text-gray-800" x-text="'قرار: ' + (labels[decision]?.title ?? '')"></h3>
                <p class="text-xs text-gray-500 mb-4">المادة المحددة: <span class="font-bold text-gray-800" x-text="selected.name"></span></p>

                <form :action="'{{ url('maintenance') }}/' + selected.id" method="POST">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="decision" :value="decision">

                    <div class="mb-4 space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1" x-text="labels[decision]?.field"></label>
                            <textarea name="notes" :required="decision !== 'fix'" class="w-full border-gray-200 rounded-xl text-sm p-3 h-20 bg-gray-50" placeholder="أدخل التفاصيل..."></textarea>
                        </div>
                        <div x-show="decision === 'fix'">
                            <label class="block text-xs font-bold text-gray-700 mb-1">تكلفة الإصلاح</label>
                            <input type="number" name="cost" min="0" step="0.01" :disabled="decision !== 'fix'" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5 bg-gray-50" placeholder="0">
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" @click="actionModal = false" class="flex-1 py-2.5 border rounded-xl text-sm font-bold text-gray-500">إلغاء</button>
                        <button type="submit" class="flex-1 py-2.5 bg-[#005f8a] text-white rounded-xl text-sm font-black shadow-md" x-text="labels[decision]?.submit"></button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
