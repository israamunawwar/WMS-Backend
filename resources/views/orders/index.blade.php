<x-app-layout>
    <div x-data="{ 
        viewModal: false, 
        actionModal: false, 
        actionType: '', 
        selectedOrder: { id: '', requester: '', type: '', destination: '', status: '', notes: '', items: [] },
        newOrderModal: {{ $errors->any() && old('items') ? 'true' : 'false' }},
        rows: @js(old('items') ? array_values(old('items')) : [['item_id' => '', 'quantity' => 1]]),
        stock: @js($availableItems->pluck('current_stock', 'id')),
        addRow() { this.rows.push({ item_id: '', quantity: 1 }) },
        removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1) }
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
                <h2 class="text-2xl font-extrabold text-[#005f8a]">{{ $pageTitle ?? 'إدارة الطلبات' }}</h2>
                <p class="text-gray-500 text-sm mt-1">مشاهدة ومعالجة طلبات المواد (الموافقة والرفض مع ذكر السبب).</p>
            </div>
            
            <div class="flex gap-3 items-center">
                <button @click="newOrderModal = true" class="bg-[#00a8e8] hover:bg-[#0073a8] text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition-all flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    طلب جديد
                </button>
                <div class="bg-orange-50 border border-orange-100 px-4 py-2 rounded-xl">
                    <span class="block text-[10px] text-orange-600 font-bold">طلبات بانتظارك</span>
                    <span class="text-lg font-black text-orange-700">{{ $pendingCount ?? 0 }}</span>
                </div>
                <div class="bg-green-50 border border-green-100 px-4 py-2 rounded-xl">
                    <span class="block text-[10px] text-green-600 font-bold">تمت الموافقة</span>
                    <span class="text-lg font-black text-green-700">{{ $approvedCount ?? 0 }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 p-4">
            <form method="GET" action="{{ route('orders.index') }}" class="flex flex-wrap gap-4 items-center w-full">
                <div class="relative flex-1 min-w-[250px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث برقم الطلب..." class="w-full pl-10 pr-4 py-2.5 border-gray-200 rounded-xl focus:ring-[#00a8e8] focus:border-[#00a8e8] text-sm">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <select name="status" onchange="this.form.submit()" class="border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-[#00a8e8] focus:border-[#00a8e8]">
                    <option value="جميع الحالات">جميع الحالات</option>
                    <option value="بانتظار الاعتماد" {{ request('status') == 'بانتظار الاعتماد' ? 'selected' : '' }}>قيد الانتظار</option>
                    <option value="تمت الموافقة" {{ request('status') == 'تمت الموافقة' ? 'selected' : '' }}>تمت الموافقة</option>
                    <option value="مرفوض" {{ request('status') == 'مرفوض' ? 'selected' : '' }}>مرفوض</option>
                </select>
                <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl text-sm transition-colors">
                    تصفية
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4">رقم الطلب</th>
                            <th class="px-6 py-4">المدرب / المستلم</th>
                            <th class="px-6 py-4">الوجهة (المخبر)</th>
                            <th class="px-6 py-4">تاريخ الطلب</th>
                            <th class="px-6 py-4">الحالة</th>
                            <th class="px-6 py-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        
                        @forelse($orders ?? [] as $order)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-black text-[#005f8a]">#ORD-{{ $order->id }}</td>
                            <td class="px-6 py-4 font-bold text-gray-800">{{ $order->user?->name ?? 'غير محدد' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $order->destination ?? 'غير محدد' }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $order->created_at ? $order->created_at->format('Y-m-d') : '-' }}</td>
                            <td class="px-6 py-4">
                                @if($order->status == 'مرفوض')
                                    <span class="text-red-600 font-bold bg-red-50 px-3 py-1 rounded-full text-xs border border-red-100">{{ $order->status }}</span>
                                @elseif($order->status == 'بانتظار الاعتماد' || $order->status == 'جديد')
                                    <span class="text-orange-600 font-bold bg-orange-50 px-3 py-1 rounded-full text-xs border border-orange-100">{{ $order->status }}</span>
                                @else
                                    <span class="text-green-600 font-bold bg-green-50 px-3 py-1 rounded-full text-xs border border-green-100">{{ $order->status }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 flex justify-center gap-2">
                                @php
                                    $details = [
                                        'id' => $order->id,
                                        'requester' => $order->user?->name ?? 'غير محدد',
                                        'type' => $order->priority,
                                        'destination' => $order->destination,
                                        'status' => $order->status,
                                        'notes' => $order->notes,
                                        'items' => $order->items->map(fn ($line) => [
                                            'name' => $line->item?->name_ar ?? $line->item?->name_en ?? 'مادة محذوفة',
                                            'quantity' => $line->quantity,
                                        ])->values(),
                                        'pending' => $order->isAwaitingDecision(),
                                    ];
                                @endphp
                                <button @click="selectedOrder = @js($details), viewModal = true" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-200 transition-colors">التفاصيل</button>

                                @hasanyrole('super_admin|admin')
                                    @if($order->isAwaitingDecision())
                                        <button @click="selectedOrder = @js($details), actionType = 'موافقة', actionModal = true" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-bold hover:bg-green-700 shadow-md shadow-green-100">قبول</button>
                                        <button @click="selectedOrder = @js($details), actionType = 'رفض', actionModal = true" class="px-3 py-1.5 bg-red-100 text-red-600 rounded-lg text-xs font-bold hover:bg-red-200 transition-colors">رفض</button>
                                    @endif
                                @endhasanyrole
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400 font-bold">لا يوجد طلبات تطابق بحثك حالياً</td>
                        </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
                <div class="p-4 border-t border-gray-100">{{ $orders->links() }}</div>
            @endif
        </div>

        <div x-show="newOrderModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-transition>
            <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="newOrderModal = false">
                <h3 class="text-xl font-black text-[#005f8a] mb-4">طلب مواد جديد</h3>
                <form action="{{ route('orders.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">الوجهة (المخبر / القسم)</label>
                            <input type="text" name="destination" value="{{ old('destination') }}" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5" placeholder="مثال: مخبر الشبكات (Lab 3)">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">درجة الحساسية</label>
                            <select name="priority" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5">
                                <option value="عادي" @selected(old('priority') === 'عادي')>عادي</option>
                                <option value="حساس" @selected(old('priority') === 'حساس')>حساس</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-bold text-gray-700">المواد المطلوبة</label>
                            <button type="button" @click="addRow()" class="text-xs font-bold text-[#00a8e8] hover:text-[#0073a8]">+ إضافة مادة</button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(row, i) in rows" :key="i">
                                <div class="flex gap-2 items-center">
                                    <select :name="'items[' + i + '][item_id]'" x-model="row.item_id" required class="flex-1 border-gray-200 rounded-xl text-sm px-3 py-2.5">
                                        <option value="">اختر المادة...</option>
                                        @foreach($availableItems as $item)
                                            <option value="{{ $item->id }}">{{ $item->name_ar ?? $item->name_en }} (المتوفر: {{ $item->current_stock }})</option>
                                        @endforeach
                                    </select>
                                    <input type="number" min="1" :max="stock[row.item_id] || null" :name="'items[' + i + '][quantity]'" x-model="row.quantity" required class="w-24 border-gray-200 rounded-xl text-sm px-3 py-2.5" placeholder="الكمية">
                                    <button type="button" @click="removeRow(i)" class="p-2 text-gray-400 hover:text-red-500" title="حذف" :class="rows.length === 1 && 'opacity-30 cursor-not-allowed'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="newOrderModal = false" class="px-4 py-2 border rounded-xl text-sm font-bold text-gray-500">إلغاء</button>
                        <button type="submit" class="px-4 py-2 bg-[#00a8e8] text-white rounded-xl text-sm font-bold shadow-md hover:bg-[#0073a8]">إرسال الطلب</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="viewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm" x-transition x-cloak>
            <div class="bg-white rounded-[2rem] max-w-2xl w-full p-8 shadow-2xl relative" @click.away="viewModal = false">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-black text-[#005f8a]">تفاصيل الطلب <span x-text="'#' + selectedOrder.id"></span></h3>
                    <button @click="viewModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-2 gap-6 mb-8 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                    <div>
                        <span class="text-[10px] text-gray-400 font-bold uppercase block">الجهة الطالبة</span>
                        <span class="text-sm font-bold text-gray-800" x-text="selectedOrder.requester"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 font-bold uppercase block">درجة الحساسية</span>
                        <span class="text-xs font-black text-red-600" x-text="selectedOrder.type == 'حساس' ? 'عالية جداً - تتطلب تدقيق' : 'عادية'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 font-bold uppercase block">الوجهة (المخبر)</span>
                        <span class="text-sm font-bold text-gray-800" x-text="selectedOrder.destination || 'غير محدد'"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 font-bold uppercase block">الحالة</span>
                        <span class="text-sm font-bold text-gray-800" x-text="selectedOrder.status"></span>
                    </div>
                    <div class="col-span-2" x-show="selectedOrder.notes">
                        <span class="text-[10px] text-gray-400 font-bold uppercase block">ملاحظات القرار</span>
                        <span class="text-sm text-gray-700" x-text="selectedOrder.notes"></span>
                    </div>
                </div>

                <div class="mb-8">
                    <h4 class="font-black text-gray-700 mb-4 border-r-4 border-[#00a8e8] pr-3 text-sm">المواد المطلوبة</h4>
                    <div class="space-y-3">
                        <template x-for="line in selectedOrder.items" :key="line.name">
                            <div class="flex justify-between items-center bg-white border border-gray-100 p-3 rounded-xl shadow-sm">
                                <span class="text-sm font-bold text-gray-700" x-text="line.name"></span>
                                <span class="bg-[#005f8a] text-white px-3 py-1 rounded-lg text-xs font-black" x-text="'× ' + line.quantity"></span>
                            </div>
                        </template>
                        <p x-show="!selectedOrder.items || selectedOrder.items.length === 0" class="text-xs text-gray-400 font-bold">لا توجد مواد مسجلة لهذا الطلب.</p>
                    </div>
                </div>

                @hasanyrole('super_admin|admin')
                    <div class="flex gap-3" x-show="selectedOrder.pending">
                        <button @click="viewModal = false, actionType = 'موافقة', actionModal = true" class="flex-1 bg-green-600 text-white font-black py-3 rounded-xl hover:bg-green-700 shadow-lg shadow-green-100">اعتماد وموافقة</button>
                        <button @click="viewModal = false, actionType = 'رفض', actionModal = true" class="flex-1 bg-red-50 text-red-600 font-black py-3 rounded-xl hover:bg-red-100">رفض الطلب</button>
                    </div>
                @endhasanyrole
            </div>
        </div>

        <div x-show="actionModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4" x-transition x-cloak>
            <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl" @click.away="actionModal = false">
                <div :class="actionType == 'موافقة' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'" class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i :class="actionType == 'موافقة' ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                </div>
                <h3 class="text-lg font-black text-center text-gray-800 mb-2">تأكيد عملية الـ <span x-text="actionType"></span></h3>
                <p class="text-gray-500 text-xs text-center mb-6 leading-relaxed">يرجى كتابة ملاحظاتك للطلب <span x-text="'#' + selectedOrder.id"></span> للتوثيق.</p>
                
                <form :action="'{{ url('orders') }}/' + selectedOrder.id + '/status'" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <input type="hidden" name="action" :value="actionType">
                    
                    <textarea name="notes" required placeholder="اكتب السبب أو ملاحظات إضافية هنا..." class="w-full border-gray-200 rounded-2xl text-sm p-4 h-32 focus:ring-[#00a8e8] mb-6 bg-gray-50"></textarea>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" @click="actionModal = false" class="py-3 text-sm font-bold text-gray-400 hover:text-gray-600">تراجع</button>
                        <button type="submit" :class="actionType == 'موافقة' ? 'bg-green-600' : 'bg-red-600'" class="text-white font-black py-3 rounded-xl shadow-lg transition-transform hover:scale-105" x-text="'تأكيد الـ ' + actionType"></button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>

