<x-app-layout>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-extrabold text-[#005f8a]">{{ $pageTitle }}</h2>
            <p class="text-gray-500 text-sm mt-1">عرض تفاصيل المواد وإمكانية تصديرها.</p>
        </div>
        
        <div class="flex gap-2">
            <div class="flex gap-2">
    <a href="{{ route('items.export.excel', ['type' => $currentType]) }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-xl shadow-md text-sm flex items-center gap-2">
        <i class="fas fa-file-excel"></i> تصدير Excel
    </a>
    <a href="{{ route('items.export.pdf', ['type' => $currentType]) }}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-xl shadow-md text-sm flex items-center gap-2">
        <i class="fas fa-file-pdf"></i> تصدير PDF
    </a>
</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-right text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4">الرقم</th>
                    <th class="px-6 py-4">الاسم (عربي)</th>
                    <th class="px-6 py-4">الاسم (إنكليزي)</th>
                    <th class="px-6 py-4">الرصيد الأساسي</th>
                    <th class="px-6 py-4 text-[#00a8e8]">الرصيد الحالي</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4 font-bold text-gray-500">{{ $item->id }}</td>
                    <td class="px-6 py-4 font-bold text-gray-800">{{ $item->name_ar ?? 'غير محدد' }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $item->name_en }}</td>
                    <td class="px-6 py-4 text-gray-500">{{ $item->initial_balance }}</td>
                    <td class="px-6 py-4 font-black {{ $item->current_stock <= \App\Models\Setting::lowStockThreshold() ? 'text-red-500' : 'text-green-600' }}">
                        {{ $item->current_stock }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-bold">لا يوجد مواد تطابق هذا البحث</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>