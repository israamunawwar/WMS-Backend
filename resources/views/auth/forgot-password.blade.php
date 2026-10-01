<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>استعادة كلمة المرور</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="absolute top-0 w-full h-64 bg-[#005f8a] shadow-lg z-0 rounded-b-[50px]"></div>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative z-10 px-4">
        <div class="w-full sm:max-w-md px-8 py-10 bg-white shadow-2xl rounded-[30px] border border-gray-100">
            <div class="text-center mb-6">
                <h2 class="text-xl font-black text-[#005f8a]">استعادة الوصول</h2>
                <p class="text-sm text-gray-600 mt-3 leading-relaxed">
                    نسيت كلمة مرورك؟ لا بأس. أدخل بريدك وسنرسل لك رابطاً لتعيين كلمة مرور جديدة.
                </p>
            </div>

            <x-auth-session-status class="mb-4 font-bold text-green-600 text-center" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div>
                    <label class="block font-bold text-sm text-gray-700 mr-1">البريد الإلكتروني</label>
                    <input id="email" class="block mt-1.5 w-full border-gray-200 focus:border-[#00a8e8] rounded-xl px-4 py-3 bg-gray-50 text-right" type="email" name="email" :value="old('email')" required autofocus />
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 font-bold text-xs" />
                </div>

                <button type="submit" class="w-full mt-8 bg-[#005f8a] hover:bg-[#004d73] text-white font-black py-3.5 rounded-xl shadow-lg transition-all">
                    إرسال رابط استعادة كلمة السر
                </button>
            </form>
        </div>
    </div>
</body>
</html>