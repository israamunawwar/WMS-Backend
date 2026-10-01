<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تعيين كلمة المرور الجديدة</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="absolute top-0 w-full h-64 bg-[#005f8a] shadow-lg z-0 rounded-b-[50px]"></div>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative z-10 px-4">
        <div class="w-full sm:max-w-md px-8 py-10 bg-white shadow-2xl rounded-[30px] border border-gray-100">
            <h2 class="text-2xl font-black text-[#005f8a] text-center mb-8">تحديث كلمة المرور</h2>

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="space-y-5">
                    <div>
                        <label class="block font-bold text-sm text-gray-700">البريد الإلكتروني</label>
                        <input id="email" class="block mt-1 w-full border-gray-200 rounded-xl px-4 py-3 bg-gray-50 text-gray-500" type="email" name="email" :value="old('email', $request->email)" required readonly />
                    </div>

                    <div>
                        <label class="block font-bold text-sm text-gray-700">كلمة المرور الجديدة</label>
                        <input id="password" class="block mt-1 w-full border-gray-200 focus:border-[#00a8e8] rounded-xl px-4 py-3 bg-gray-50" type="password" name="password" required />
                    </div>

                    <div>
                        <label class="block font-bold text-sm text-gray-700">تأكيد كلمة المرور</label>
                        <input id="password_confirmation" class="block mt-1 w-full border-gray-200 focus:border-[#00a8e8] rounded-xl px-4 py-3 bg-gray-50" type="password" name="password_confirmation" required />
                    </div>
                </div>

                <button type="submit" class="w-full mt-8 bg-[#005f8a] hover:bg-[#004d73] text-white font-black py-3.5 rounded-xl shadow-lg">
                    تحديث كلمة السر الآن
                </button>
            </form>
        </div>
    </div>
</body>
</html>