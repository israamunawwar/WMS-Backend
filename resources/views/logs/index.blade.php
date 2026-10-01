<x-app-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold text-[#005f8a]">سجل العمليات (Audit Trail)</h2>
        <p class="text-gray-500 text-sm mt-1">مراقبة كافة التغييرات والحركات التي قام بها المستخدمون داخل النظام.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <form method="GET" action="{{ route('logs.index') }}" class="p-4 border-b border-gray-100 flex flex-wrap gap-4 bg-gray-50/50">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث عن مستخدم أو عملية..." class="flex-1 min-w-[200px] border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-[#00a8e8]">
            <input type="date" name="date" value="{{ request('date') }}" class="border-gray-200 rounded-xl text-sm px-4 py-2.5 focus:ring-[#00a8e8] text-gray-500">
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl text-sm transition-colors">تصفية</button>
            @if(request()->hasAny(['search', 'date']))
                <a href="{{ route('logs.index') }}" class="py-2.5 px-2 text-sm font-bold text-gray-400 hover:text-gray-600">مسح</a>
            @endif
        </form>

        <div class="p-6">
            <div class="relative border-r-2 border-gray-100 pr-6 space-y-8">
                @forelse($logs as $log)
                    @php
                        $dot = ['green' => 'bg-green-100 text-green-600', 'red' => 'bg-red-100 text-red-600', 'blue' => 'bg-blue-100 text-blue-600'][$log->tone];
                    @endphp
                    <div class="relative">
                        <span class="absolute -right-8 {{ $dot }} w-6 h-6 rounded-full flex items-center justify-center border-4 border-white">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        </span>
                        <div class="flex justify-between items-start mb-1 gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-gray-800 text-sm">{{ $log->user?->name ?? 'النظام الآلي' }}</span>
                                <span class="bg-gray-100 text-gray-500 text-[10px] px-2 py-0.5 rounded font-bold">{{ $log->section }}</span>
                            </div>
                            <span class="text-xs text-gray-400 font-bold whitespace-nowrap" title="{{ $log->created_at }}">
                                {{ $log->created_at->isToday() ? 'اليوم، ' : ($log->created_at->isYesterday() ? 'أمس، ' : $log->created_at->format('Y-m-d').' ') }}{{ $log->created_at->format('H:i') }}
                            </span>
                        </div>
                        <p class="text-sm {{ $log->tone === 'red' ? 'text-red-600' : 'text-gray-600' }}">{{ $log->description }}</p>
                    </div>
                @empty
                    <p class="text-center text-gray-400 font-bold py-6">لا توجد عمليات مسجلة</p>
                @endforelse
            </div>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100">{{ $logs->links() }}</div>
        @endif
    </div>
</x-app-layout>
