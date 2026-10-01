<x-app-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold text-[#005f8a]">التقارير والتصدير</h2>
        <p class="text-gray-500 text-sm mt-1">توليد تقارير مخصصة للنظام وتصديرها بصيغ (PDF, Excel).</p>
    </div>

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-sm font-bold">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('reports.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-gray-700 mb-2">نوع التقرير المطلوب</label>
                <select name="type" required class="w-full border-gray-200 rounded-xl text-sm px-4 py-3 focus:ring-[#00a8e8] bg-gray-50">
                    <option value="">اختر نوع التقرير...</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['type'] ?? null) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-2">من تاريخ</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full border-gray-200 rounded-xl text-sm px-4 py-3 focus:ring-[#00a8e8] bg-gray-50">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-2">إلى تاريخ</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full border-gray-200 rounded-xl text-sm px-4 py-3 focus:ring-[#00a8e8] bg-gray-50">
            </div>
            <button type="submit" class="bg-[#00a8e8] hover:bg-[#0073a8] text-white font-bold py-3 px-6 rounded-xl shadow-md transition-colors text-sm">عرض التقرير</button>
        </div>
        <p class="text-[10px] text-gray-400 mt-3">تقرير المخزون يعرض الأرصدة الحالية ولا يتأثر بالتاريخ. باقي التقارير تُفلتر بتاريخ التسجيل.</p>
    </form>

    @if($report)
        @php($exportQuery = array_filter($filters))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex flex-col md:flex-row justify-between md:items-center gap-4">
                <div>
                    <h3 class="text-lg font-black text-gray-800">{{ $report['title'] }}</h3>
                    <p class="text-xs text-gray-500 mt-1">{{ $report['period'] }} — <span class="font-bold">{{ count($report['rows']) }}</span> سجل</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('reports.export', $exportQuery + ['format' => 'pdf']) }}" class="flex items-center gap-2 bg-red-50 text-red-600 hover:bg-red-100 font-bold py-2.5 px-5 rounded-xl transition-colors border border-red-100 text-sm">
                        <i class="fas fa-file-pdf"></i> تصدير كـ PDF
                    </a>
                    <a href="{{ route('reports.export', $exportQuery + ['format' => 'excel']) }}" class="flex items-center gap-2 bg-green-50 text-green-600 hover:bg-green-100 font-bold py-2.5 px-5 rounded-xl transition-colors border border-green-100 text-sm">
                        <i class="fas fa-file-excel"></i> تصدير كـ Excel
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                        <tr>
                            @foreach($report['headings'] as $heading)
                                <th class="px-6 py-4">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse(array_slice($report['rows'], 0, $previewRows) as $row)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                @foreach($row as $cell)
                                    <td class="px-6 py-3 text-gray-700">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['headings']) }}" class="px-6 py-8 text-center text-gray-400 font-bold">لا توجد بيانات ضمن هذه الفترة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(count($report['rows']) > $previewRows)
                <div class="p-4 border-t border-gray-100 text-xs text-gray-500 font-bold text-center">
                    المعاينة تعرض أول {{ $previewRows }} سجل فقط، والتصدير يشمل كل السجلات ({{ count($report['rows']) }}).
                </div>
            @endif
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
            <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 text-[#005f8a]">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <h3 class="text-lg font-black text-gray-800 mb-2">اختر نوع التقرير</h3>
            <p class="text-sm text-gray-500 max-w-md mx-auto">حدد نوع التقرير والفترة الزمنية ثم اضغط «عرض التقرير» لمعاينته، وبعدها صدّره كملف Excel أو PDF للطباعة الرسمية.</p>
        </div>
    @endif
</x-app-layout>
