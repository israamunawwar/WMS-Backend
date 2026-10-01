<x-app-layout>
    @php
        $isOpen = $session && $session->status === \App\Models\InventorySession::OPEN;
        $isCompleted = $session && $session->status === \App\Models\InventorySession::COMPLETED;
        $total = $rows->count();
        $progress = $total > 0 ? round($counted / $total * 100) : 0;
    @endphp

    <div x-data="{
        newSessionModal: false,
        approveModal: false,
        discrepancyModal: false
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
                <h2 class="text-2xl font-extrabold text-[#005f8a]">الجرد السنوي</h2>
                <p class="text-gray-500 text-sm mt-1">إدارة جلسات الجرد، مطابقة الأرصدة، واعتماد الفروقات (النواقص والزيادات).</p>
            </div>

            @unless($session)
                <button @click="newSessionModal = true" class="bg-[#00a8e8] hover:bg-[#0073a8] text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition-all flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    إنشاء جلسة جرد جديدة
                </button>
            @endunless
        </div>

        @if($session)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex flex-col md:flex-row justify-between md:items-center gap-3 mb-4">
                    <h3 class="font-black text-gray-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full {{ $isOpen ? 'bg-green-500 animate-pulse' : 'bg-orange-500' }}"></span>
                        {{ $session->title }}
                        <span class="text-xs font-bold px-2 py-1 rounded-lg {{ $isOpen ? 'bg-green-50 text-green-600' : 'bg-orange-50 text-orange-600' }}">
                            {{ $isOpen ? 'الجرد جارٍ' : 'بانتظار الاعتماد' }}
                        </span>
                    </h3>

                    @role('super_admin')
                        <div class="flex gap-2">
                            @if($isOpen)
                                <button @click="discrepancyModal = true" @disabled($counted < $total)
                                        class="px-4 py-2 bg-orange-50 text-orange-600 font-bold text-xs rounded-lg hover:bg-orange-100 border border-orange-200 disabled:opacity-40 disabled:cursor-not-allowed">
                                    معالجة الفروقات ({{ $discrepancies->count() }})
                                </button>
                            @endif
                            @if($isCompleted)
                                <button @click="approveModal = true" class="px-4 py-2 bg-[#005f8a] text-white font-bold text-xs rounded-lg hover:bg-[#004d73] shadow-md">
                                    اعتماد نتيجة الجرد نهائياً
                                </button>
                            @endif
                        </div>
                    @endrole
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <span class="text-xs font-bold text-gray-500 block mb-1">إجمالي المواد المجرودة</span>
                        <span class="text-2xl font-black text-[#005f8a]">{{ $counted }} / {{ $total }}</span>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <span class="text-xs font-bold text-gray-500 block mb-1">نسبة الإنجاز</span>
                        <div class="flex items-center gap-2">
                            <div class="w-full bg-gray-200 rounded-full h-2.5"><div class="bg-green-500 h-2.5 rounded-full" style="width: {{ $progress }}%"></div></div>
                            <span class="text-sm font-bold text-green-600">{{ $progress }}%</span>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <span class="text-xs font-bold text-gray-500 block mb-1">المسؤول عن الجلسة</span>
                        <span class="text-sm font-bold text-gray-800">{{ $session->creator?->name ?? 'غير محدد' }}</span>
                    </div>
                </div>

                @if($isCompleted && $session->decision)
                    <div class="mt-4 bg-orange-50 border border-orange-100 rounded-xl p-4 text-sm">
                        <span class="block text-xs font-bold text-orange-600 mb-1">قرار رئيس القسم بشأن الفروقات</span>
                        <span class="text-gray-700">{{ $session->decision }}</span>
                    </div>
                @endif
            </div>

            @if($isOpen)
                <h3 class="font-black text-gray-700 mb-3 text-lg">تسجيل الكميات الفعلية</h3>
                <form action="{{ route('inventory.count') }}" method="POST" class="mb-8">
                    @csrf
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto max-h-[28rem] overflow-y-auto">
                            <table class="w-full text-right text-sm whitespace-nowrap">
                                <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200 sticky top-0">
                                    <tr>
                                        <th class="px-6 py-4">رمز المادة</th>
                                        <th class="px-6 py-4">اسم المادة</th>
                                        <th class="px-6 py-4">الرصيد الدفتري (النظام)</th>
                                        <th class="px-6 py-4">الرصيد الفعلي (الجرد)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($rows as $row)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-3 font-bold text-gray-500">{{ $row->item->barcode ?? '#'.$row->item_id }}</td>
                                            <td class="px-6 py-3 font-bold text-gray-800">{{ $row->item->name_ar ?? $row->item->name_en }}</td>
                                            <td class="px-6 py-3 font-bold text-gray-500">{{ $row->system_quantity }}</td>
                                            <td class="px-6 py-3">
                                                <input type="number" min="0" name="counts[{{ $row->id }}]" value="{{ old('counts.'.$row->id, $row->actual_quantity) }}"
                                                       class="w-28 border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:ring-[#00a8e8] focus:border-[#00a8e8]">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 border-t border-gray-100 flex justify-end bg-gray-50/50">
                            <button type="submit" class="px-5 py-2.5 bg-[#00a8e8] text-white rounded-xl text-sm font-bold shadow-md hover:bg-[#0073a8]">حفظ الكميات المجرودة</button>
                        </div>
                    </div>
                </form>
            @endif

            <h3 class="font-black text-gray-700 mb-3 text-lg">سجل فروقات الجرد الحالية</h3>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">رمز المادة</th>
                                <th class="px-6 py-4">اسم المادة</th>
                                <th class="px-6 py-4">الرصيد الدفتري (النظام)</th>
                                <th class="px-6 py-4">الرصيد الفعلي (الجرد)</th>
                                <th class="px-6 py-4">الفرق</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($discrepancies as $row)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-500">{{ $row->item->barcode ?? '#'.$row->item_id }}</td>
                                    <td class="px-6 py-4 font-bold text-gray-800">{{ $row->item->name_ar ?? $row->item->name_en }}</td>
                                    <td class="px-6 py-4 font-bold text-gray-500">{{ $row->system_quantity }}</td>
                                    <td class="px-6 py-4 font-bold text-[#005f8a]">{{ $row->actual_quantity }}</td>
                                    <td class="px-6 py-4">
                                        @if($row->difference < 0)
                                            <span class="bg-red-50 text-red-600 px-2 py-1 rounded-lg text-xs font-black border border-red-100">{{ $row->difference }} (نقص)</span>
                                        @else
                                            <span class="bg-green-50 text-green-600 px-2 py-1 rounded-lg text-xs font-black border border-green-100">+{{ $row->difference }} (زيادة)</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-bold">لا توجد فروقات مسجلة حتى الآن</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center mb-8">
                <p class="text-gray-500 font-bold">لا توجد جلسة جرد نشطة حالياً.</p>
                <p class="text-gray-400 text-xs mt-1">ابدأ جلسة جديدة لتجميد الأرصدة الحالية وتسجيل الكميات الفعلية.</p>
            </div>
        @endif

        <h3 class="font-black text-gray-700 mb-3 text-lg">جلسات الجرد المعتمدة سابقاً</h3>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4">اسم الجلسة</th>
                            <th class="px-6 py-4">المسؤول</th>
                            <th class="px-6 py-4">المعتمد</th>
                            <th class="px-6 py-4">تاريخ الاعتماد</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($history as $past)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-800">{{ $past->title }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $past->creator?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $past->approver?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $past->updated_at->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400 font-bold">لا توجد جلسات معتمدة بعد</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($history->hasPages())
                <div class="p-4 border-t border-gray-100">{{ $history->links() }}</div>
            @endif
        </div>

        <div x-show="newSessionModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-transition>
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl" @click.away="newSessionModal = false">
                <h3 class="text-xl font-black text-[#005f8a] mb-4">إنشاء جلسة جرد جديدة</h3>
                <form action="{{ route('inventory.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">اسم الجلسة / العام الدراسي</label>
                        <input type="text" name="name" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5" placeholder="مثال: جرد نهاية العام 2026">
                    </div>
                    <p class="text-xs text-gray-500 leading-relaxed">سيتم أخذ لقطة من أرصدة كل المواد الحالية كرصيد دفتري للمقارنة مع الجرد الفعلي.</p>
                    <div class="flex justify-end gap-2 pt-4">
                        <button type="button" @click="newSessionModal = false" class="px-4 py-2 border rounded-xl text-sm font-bold text-gray-500">إلغاء</button>
                        <button type="submit" class="px-4 py-2 bg-[#00a8e8] text-white rounded-xl text-sm font-bold shadow-md">بدء الجرد وتجميد الأرصدة</button>
                    </div>
                </form>
            </div>
        </div>

        @role('super_admin')
            <div x-show="discrepancyModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-transition>
                <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border-t-8 border-orange-500" @click.away="discrepancyModal = false">
                    <h3 class="text-xl font-black text-orange-900 mb-2">اعتماد وتسوية الفروقات</h3>
                    <p class="text-xs text-gray-500 mb-4 leading-relaxed">ستؤدي هذه العملية إلى تعديل الأرصدة في النظام بناءً على الجرد الفعلي. هل تم التحقق من أسباب النقص والزيادة؟</p>
                    <form action="{{ route('inventory.resolve') }}" method="POST">
                        @csrf
                        <textarea name="decision" required placeholder="أدخل قرار رئيس القسم بشأن الفروقات (مثال: تحميل المسؤولية، أو إدخال كفائض)..." class="w-full border-gray-200 rounded-xl text-sm p-3 h-24 mb-4 bg-gray-50"></textarea>
                        <div class="flex gap-2">
                            <button type="button" @click="discrepancyModal = false" class="flex-1 py-2.5 border rounded-xl text-sm font-bold text-gray-500">إلغاء</button>
                            <button type="submit" class="flex-1 py-2.5 bg-orange-500 text-white rounded-xl text-sm font-black shadow-md">تسوية وتعديل الأرصدة</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="approveModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-transition>
                <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl text-center" @click.away="approveModal = false">
                    <div class="w-16 h-16 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-lg font-black text-gray-800 mb-2">إغلاق واعتماد الجلسة</h3>
                    <p class="text-xs text-gray-500 mb-6">بمجرد الاعتماد سيتم إغلاق الجلسة وترصيد المواد للعام القادم بشكل نهائي.</p>
                    <form action="{{ route('inventory.close') }}" method="POST">
                        @csrf
                        <div class="flex gap-2">
                            <button type="button" @click="approveModal = false" class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-bold text-gray-600">تراجع</button>
                            <button type="submit" class="flex-1 py-2.5 bg-[#005f8a] text-white rounded-xl text-sm font-black shadow-md">اعتماد وإغلاق</button>
                        </div>
                    </form>
                </div>
            </div>
        @endrole

    </div>
</x-app-layout>
