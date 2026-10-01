<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تأكيد الحساب</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex flex-col sm:justify-center items-center px-4">
        <div class="w-full sm:max-w-md px-8 py-10 bg-white shadow-2xl rounded-[30px] text-center border-b-8 border-[#00a8e8]">
            <h2 class="text-2xl font-black text-[#005f8a] mb-4">شكراً لتسجيلك!</h2>
            <p class="text-gray-600 font-bold leading-relaxed mb-8">
                يرجى الضغط على الرابط الذي أرسلناه لبريدك الإلكتروني لتفعيل حسابك والبدء باستخدام النظام.
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="mb-6 font-bold text-sm text-green-600 bg-green-50 p-3 rounded-lg">
                    تم إرسال رابط تفعيل جديد إلى بريدك بنجاح.
                </div>
            @endif

            <div class="flex flex-col gap-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="w-full bg-[#005f8a] text-white font-black py-3 rounded-xl hover:bg-[#004d73]">
                        إعادة إرسال البريد
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-red-500 font-bold text-sm hover:underline">
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>