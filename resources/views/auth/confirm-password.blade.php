<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تأكيد الهوية</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex flex-col sm:justify-center items-center px-4">
        <div class="w-full sm:max-w-md px-8 py-10 bg-white shadow-2xl rounded-[30px] border-t-8 border-[#005f8a]">
            <p class="text-center text-gray-600 font-bold mb-6">هذه منطقة آمنة. يرجى تأكيد كلمة مرورك قبل المتابعة.</p>
            
            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf
                <div>
                    <input id="password" class="block w-full border-gray-200 focus:border-[#00a8e8] rounded-xl px-4 py-3 bg-gray-50" type="password" name="password" required placeholder="كلمة المرور" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <button type="submit" class="w-full mt-6 bg-[#005f8a] hover:bg-[#004d73] text-white font-black py-3 rounded-xl transition-all">
                    تأكيد المتابعة
                </button>
            </form>
        </div>
    </div>
</body>
</html>