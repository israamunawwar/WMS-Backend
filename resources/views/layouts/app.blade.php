<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'نظام إدارة المستودع') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900" x-data="{ sidebarOpen: false }">

    <div class="flex h-screen overflow-hidden">
        
        <aside class="flex flex-col w-64 bg-[#005f8a] text-white transition-transform transform fixed inset-y-0 right-0 z-50 md:relative md:translate-x-0 shadow-xl" 
               :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full md:translate-x-0'">
            
            <div class="flex items-center justify-center h-16 bg-[#004d73] shadow-md px-2">
                <div class="w-11 h-11 bg-white rounded-full overflow-hidden shadow-sm flex-shrink-0 border-2 border-[#00a8e8] flex items-center justify-center">
                    <img src="{{ asset('images/logo.png') }}" alt="شعار المعهد" class="w-full h-full object-cover">
                </div>
                <h1 class="text-base font-bold mr-3 hidden md:block whitespace-nowrap">مستودع الـ IT</h1>
            </div>
            
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto text-sm">
                
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2.5 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#004d73] shadow-inner border-r-4 border-[#00a8e8]' : 'hover:bg-[#0073a8]' }}">
                    <span class="{{ request()->routeIs('dashboard') ? 'font-bold text-white' : 'font-semibold text-gray-200 hover:text-white' }}">لوحة الإحصائيات</span>
                </a>
                
                <a href="{{ route('items.index') }}" class="flex items-center px-4 py-2.5 rounded-lg transition-colors {{ request()->routeIs('items.*') ? 'bg-[#004d73] shadow-inner border-r-4 border-[#00a8e8]' : 'hover:bg-[#0073a8]' }}">
                    <span class="{{ request()->routeIs('items.*') ? 'font-bold text-white' : 'font-semibold text-gray-200 hover:text-white' }}">المواد والمستودع</span>
                </a>

                <a href="{{ route('categories.index') }}" class="flex items-center px-4 py-2.5 rounded-lg transition-colors {{ request()->routeIs('categories.*') ? 'bg-[#004d73] shadow-inner border-r-4 border-[#00a8e8]' : 'hover:bg-[#0073a8]' }}">
                    <span class="{{ request()->routeIs('categories.*') ? 'font-bold text-white' : 'font-semibold text-gray-200 hover:text-white' }}">الأصناف</span>
                </a>
                
                @role('super_admin')
                <a href="{{ route('users.index') }}" class="flex items-center px-4 py-2.5 rounded-lg transition-colors {{ request()->routeIs('users.index') ? 'bg-[#004d73] shadow-inner border-r-4 border-[#00a8e8]' : 'hover:bg-[#0073a8]' }}">
                    <span class="{{ request()->routeIs('users.index') ? 'font-bold text-white' : 'font-semibold text-gray-200 hover:text-white' }}">إدارة المستخدمين</span>
                </a>
                @endrole
                
                <a href="{{ route('orders.index') }}" class="flex items-center px-4 py-2.5 rounded-lg transition-colors {{ request()->routeIs('orders.index') ? 'bg-[#004d73] shadow-inner border-r-4 border-[#00a8e8]' : 'hover:bg-[#0073a8]' }}">
                    <span class="{{ request()->routeIs('orders.index') ? 'font-bold text-white' : 'font-semibold text-gray-200 hover:text-white' }}">إدارة الطلبات</span>
                </a>
                
                @hasanyrole('super_admin|admin')
               <a href="{{ route('inventory.index') }}" class="flex items-center px-4 py-2.5 rounded-lg transition-colors {{ request()->routeIs('inventory.index') ? 'bg-[#004d73] shadow-inner border-r-4 border-[#00a8e8]' : 'hover:bg-[#0073a8]' }}">
                    <span class="{{ request()->routeIs('inventory.index') ? 'font-bold text-white' : 'font-semibold text-gray-200 hover:text-white' }}">الجرد السنوي</span>
                </a>
                @endhasanyrole
                
                @hasanyrole('super_admin|admin')
                <a href="{{ route('maintenance.index') }}" class="flex items-center px-4 py-2.5 rounded-lg transition-colors {{ request()->routeIs('maintenance.index') ? 'bg-[#004d73] shadow-inner border-r-4 border-[#00a8e8]' : 'hover:bg-[#0073a8]' }}">
                    <span class="{{ request()->routeIs('maintenance.index') ? 'font-bold text-white' : 'font-semibold text-gray-200 hover:text-white' }}">الصيانة والتوالف</span>
                </a>
                @endhasanyrole
                
                @hasanyrole('super_admin|admin')
               <a href="{{ route('reports.index') }}" class="flex items-center px-4 py-2.5 rounded-lg transition-colors {{ request()->routeIs('reports.index') ? 'bg-[#004d73] shadow-inner border-r-4 border-[#00a8e8]' : 'hover:bg-[#0073a8]' }}">
                    <span class="{{ request()->routeIs('reports.index') ? 'font-bold text-white' : 'font-semibold text-gray-200 hover:text-white' }}">التقارير والتصدير</span>
                </a>
                @endhasanyrole
                
                @hasanyrole('super_admin|admin')
                <a href="{{ route('logs.index') }}" class="flex items-center px-4 py-2.5 rounded-lg transition-colors {{ request()->routeIs('logs.index') ? 'bg-[#004d73] shadow-inner border-r-4 border-[#00a8e8]' : 'hover:bg-[#0073a8]' }}">
                    <span class="{{ request()->routeIs('logs.index') ? 'font-bold text-white' : 'font-semibold text-gray-200 hover:text-white' }}">سجل العمليات</span>
                </a>
                @endhasanyrole
                
                @hasanyrole('super_admin|admin')
                <a href="{{ route('settings.index') }}" class="flex items-center px-4 py-2.5 rounded-lg transition-colors {{ request()->routeIs('settings.index') ? 'bg-[#004d73] shadow-inner border-r-4 border-[#00a8e8]' : 'hover:bg-[#0073a8]' }}">
                    <span class="{{ request()->routeIs('settings.index') ? 'font-bold text-white' : 'font-semibold text-gray-200 hover:text-white' }}">إعدادات النظام</span>
                </a>
                @endhasanyrole
            </nav>
        </aside>

        <div class="flex flex-col flex-1 w-full">
            <header class="flex items-center justify-between h-16 px-6 bg-white border-b border-gray-200 shadow-sm">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 md:hidden focus:outline-none">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="flex-1"></div>
                <div class="flex items-center gap-3">
                    <span class="font-bold text-sm text-[#00a8e8]">{{ Auth::user()->name ?? 'رئيس القسم' }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="تسجيل الخروج" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-full transition-colors focus:outline-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 md:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>