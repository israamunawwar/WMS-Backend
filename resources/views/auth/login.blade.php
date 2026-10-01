<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول - نظام المستودع</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">

    <div class="absolute top-0 w-full h-80 bg-[#005f8a] shadow-lg z-0 rounded-b-[50px]"></div>

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative z-10 px-4">
        
        <div class="mb-6 bg-white rounded-full shadow-xl border-4 border-[#004d73] w-28 h-28 flex items-center justify-center overflow-hidden">
            <img src="{{ asset('images/logo.png') }}" alt="شعار المعهد" class="w-full h-full object-cover">
        </div>

        <div class="w-full sm:max-w-md px-8 py-10 bg-white shadow-2xl overflow-hidden rounded-[30px] border border-gray-100">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-black text-[#005f8a]">معهد دمشق المتوسط</h2>
                <p class="text-gray-500 font-bold mt-1">نظام إدارة المستودع - قسم الـ IT</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div>
                    <label class="block font-bold text-sm text-gray-700 mr-1">البريد الإلكتروني</label>
                    <input id="email" class="block mt-1.5 w-full border-gray-200 focus:border-[#00a8e8] focus:ring-[#00a8e8] rounded-xl shadow-sm px-4 py-3 bg-gray-50" type="email" name="email" :value="old('email')" required autofocus placeholder="example@it.edu" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 font-bold text-xs" />
                </div>

                <div class="mt-5" x-data="{ show: false }">
                    <label class="block font-bold text-sm text-gray-700 mr-1">كلمة المرور</label>
                    <div class="relative mt-1.5">
                        <input id="password" 
                               class="block w-full border-gray-200 focus:border-[#00a8e8] focus:ring-[#00a8e8] rounded-xl shadow-sm px-4 py-3 bg-gray-50" 
                               :type="show ? 'text' : 'password'" 
                               name="password" required />
                        
                        <button type="button" @click="show = !show" class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 hover:text-[#005f8a]">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg x-show="show" style="display: none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.978 9.978 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-6">
                    <label class="flex items-center">
                        <input type="checkbox" class="rounded border-gray-300 text-[#005f8a] shadow-sm focus:ring-[#005f8a]" name="remember">
                        <span class="mr-2 text-sm text-gray-600">تذكرني</span>
                    </label>
                    <a class="text-sm font-bold text-[#005f8a] hover:underline" href="{{ route('password.request') }}">نسيت كلمة السر؟</a>
                </div>

                <button type="submit" class="w-full mt-8 bg-[#005f8a] hover:bg-[#004d73] text-white font-black py-3.5 rounded-xl shadow-lg transition-all transform hover:-translate-y-1">
                    تسجيل الدخول
                </button>
            </form>
        </div>
    </div>
</body>
</html>