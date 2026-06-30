@component('mail::message')

    # مرحبًا {{ $user->name }}

    تم تجهيز نسخة من بيانات حسابك بنجاح.

    يمكنك تحميل الملف بالضغط على الزر التالي:

    @component('mail::button', ['url' => $downloadUrl])
        تحميل بياناتي
    @endcomponent

    **تنتهي صلاحية رابط التحميل في:**

    {{ $expiresAt }}

    إذا لم تطلب هذا التصدير، يمكنك تجاهل هذه الرسالة.

    شكراً لاستخدامك منصة **HOPE**.

@endcomponent
