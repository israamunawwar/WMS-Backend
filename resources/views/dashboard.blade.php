<x-app-layout>
    <div class="mb-6 border-b pb-3">
        <h2 class="text-2xl font-extrabold text-[#00a8e8]">نظرة عامة على النظام</h2>
        <p class="text-gray-500 mt-1 text-sm">إحصائيات تفصيلية للمستودع، الطلبات، والمدربين.</p>
    </div>

    <div class="mb-8">
        <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 ml-2 text-[#00a8e8]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            إحصائيات المخزون
        </h3>
       <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            
            <a href="{{ route('items.index', ['type' => 'all']) }}" class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-[#00a8e8] transition-all transform hover:-translate-y-1">
                <p class="text-xs font-bold text-gray-500">عدد المواد الكلي</p>
                <p class="text-2xl font-black text-[#00a8e8] mt-1">{{ $totalItems }}</p>
            </a>
            
            <a href="{{ route('categories.index') }}" class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-indigo-300 transition-all transform hover:-translate-y-1">
                <p class="text-xs font-bold text-gray-500">عدد الأصناف</p>
                <p class="text-2xl font-black text-indigo-500 mt-1">{{ $totalCategories }}</p>
            </a>

            <a href="{{ route('items.index', ['type' => 'low_stock']) }}" class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-300 transition-all transform hover:-translate-y-1">
                <p class="text-xs font-bold text-gray-500">منخفضة المخزون</p>
                <p class="text-2xl font-black text-orange-500 mt-1">{{ $lowStockItems }}</p>
            </a>

            <a href="{{ route('items.index', ['type' => 'most_used']) }}" class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-green-300 transition-all transform hover:-translate-y-1">
                <p class="text-xs font-bold text-gray-500">أكثر المواد استخداماً</p>
                <p class="text-sm font-bold text-green-600 mt-2">{{ $mostUsedItemName }}</p>
            </a>

            <a href="{{ route('items.index', ['type' => 'damaged']) }}" class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-red-300 transition-all transform hover:-translate-y-1">
                <p class="text-xs font-bold text-gray-500">مواد تالفة</p>
                <p class="text-2xl font-black text-red-500 mt-1">{{ $damagedItems }}</p>
            </a>

            <a href="{{ route('items.index', ['type' => 'missing']) }}" class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-400 transition-all transform hover:-translate-y-1">
                <p class="text-xs font-bold text-gray-500">مواد ناقصة</p>
                <p class="text-2xl font-black text-gray-600 mt-1">{{ $missingItems }}</p>
            </a>
        </div>
    </div>

    <div class="mb-8">
        <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 ml-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            إحصائيات الطلبات
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <a href="{{ route('orders.index', ['filter' => 'pending']) }}" class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-300 transition-all transform hover:-translate-y-1">
                <p class="text-xs font-bold text-gray-500">طلبات بانتظار الاعتماد</p>
                <p class="text-2xl font-black text-orange-500 mt-1">{{ $pendingOrders }}</p>
            </a>

            <a href="{{ route('orders.index', ['filter' => 'today']) }}" class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-green-300 transition-all transform hover:-translate-y-1">
                <p class="text-xs font-bold text-gray-500">طلبات اليوم</p>
                <p class="text-2xl font-black text-green-500 mt-1">{{ $todayOrders }}</p>
            </a>
            
            <a href="{{ route('orders.index', ['filter' => 'month']) }}" class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-[#00a8e8] transition-all transform hover:-translate-y-1">
                <p class="text-xs font-bold text-gray-500">طلبات هذا الشهر</p>
                <p class="text-2xl font-black text-[#00a8e8] mt-1">{{ $monthOrders }}</p>
            </a>

            <a href="{{ route('orders.index', ['filter' => 'rejected']) }}" class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-red-300 transition-all transform hover:-translate-y-1">
                <p class="text-xs font-bold text-gray-500">الطلبات المرفوضة</p>
                <p class="text-2xl font-black text-red-500 mt-1">{{ $rejectedOrders }}</p>
            </a>
        </div>
    </div>

    <div class="mb-4">
        <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 ml-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            إحصائيات المدربين والمخابر
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <p class="text-xs font-bold text-gray-500">أكثر المدربين طلباً</p>
                <p class="text-lg font-black text-purple-600 mt-1">{{ $topTrainer }}</p>
            </div>
            
            <div class="block bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <p class="text-xs font-bold text-gray-500">أكثر المخابر استخداماً</p>
                <p class="text-lg font-black text-purple-600 mt-1">{{ $topLab }}</p>
            </div>
        </div>
    </div>

</x-app-layout>