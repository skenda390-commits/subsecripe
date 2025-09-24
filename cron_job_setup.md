# Setting up the CRON Job

This project requires a CRON job to run periodically to handle routine tasks such as resetting monthly usage data and managing subscription statuses.

The script designed for this purpose is located at `api/cron_jobs.php`.

## Tasks Performed by the CRON Script

1.  **Monthly Usage Reset**: On the first day of every month, this script checks for all users and ensures a new usage record for the current month is created (or reset to zero). This effectively renews their monthly image/video credits.
2.  **Subscription Expiration**: The script checks for any users whose `subscription_end_date` is in the past. It then downgrades these users to the "Free" plan, ensuring that access to premium features is revoked once the subscription period ends.

## How to Set Up the CRON Job

You need to have command-line access to your server (e.g., via SSH) to set up a CRON job.

1.  Open the crontab file for editing by running the following command in your terminal:
    ```bash
    crontab -e
    ```

2.  Add a new line to this file to schedule the execution of the `cron_jobs.php` script. We recommend running it once daily, shortly after midnight. This ensures that expired subscriptions are handled promptly.

    The following line will execute the script every day at 00:05 (5 minutes past midnight):

    ```
    5 0 * * * /usr/bin/php /path/to/your/project/api/cron_jobs.php >> /path/to/your/project/logs/cron.log 2>&1
    ```

### Breakdown of the Command:

*   `5 0 * * *`: This is the schedule. It means "at minute 5 of hour 0 (midnight) of every day of every month of every day of the week."
*   `/usr/bin/php`: This is the path to your PHP executable. It might be different on your server. You can find the correct path by running `which php` in your terminal.
*   `/path/to/your/project/api/cron_jobs.php`: You **must** replace this with the absolute path to the `cron_jobs.php` file on your server.
*   `>> /path/to/your/project/logs/cron.log 2>&1`: This part is optional but highly recommended. It redirects all output (both standard output and errors) from the script and appends it to a log file. This is extremely useful for debugging and ensuring the CRON job is running correctly. Make sure the `logs` directory exists and is writable.

3.  Save and close the crontab file. The CRON daemon will automatically read the new schedule, and your job will be active.

---
# إعداد مهمة CRON

يتطلب هذا المشروع تشغيل مهمة CRON بشكل دوري لمعالجة المهام الروتينية مثل إعادة تعيين بيانات الاستخدام الشهرية وإدارة حالات الاشتراك.

تم تصميم البرنامج النصي المخصص لهذا الغرض وهو موجود في `api/cron_jobs.php`.

## المهام التي يقوم بها البرنامج النصي

1.  **إعادة تعيين الاستخدام الشهري**: في اليوم الأول من كل شهر، يقوم هذا البرنامج النصي بالتحقق من جميع المستخدمين ويضمن إنشاء سجل استخدام جديد للشهر الحالي (أو إعادة تعيينه إلى الصفر). هذا يجدد بشكل فعال أرصدة الصور/الفيديو الشهرية الخاصة بهم.
2.  **انتهاء صلاحية الاشتراك**: يتحقق البرنامج النصي من وجود أي مستخدمين أصبح تاريخ انتهاء اشتراكهم (`subscription_end_date`) في الماضي. ثم يقوم بإرجاع هؤلاء المستخدمين إلى الخطة "المجانية"، مما يضمن إلغاء الوصول إلى الميزات المميزة بمجرد انتهاء فترة الاشتراك.

## كيفية إعداد مهمة CRON

تحتاج إلى الوصول إلى سطر الأوامر على الخادم الخاص بك (على سبيل المثال، عبر SSH) لإعداد مهمة CRON.

1.  افتح ملف crontab للتحرير عن طريق تشغيل الأمر التالي في الطرفية:
    ```bash
    crontab -e
    ```

2.  أضف سطرًا جديدًا إلى هذا الملف لجدولة تنفيذ البرنامج النصي `cron_jobs.php`. نوصي بتشغيله مرة واحدة يوميًا، بعد منتصف الليل بقليل. هذا يضمن معالجة الاشتراكات المنتهية الصلاحية على الفور.

    سيقوم السطر التالي بتنفيذ البرنامج النصي كل يوم في الساعة 00:05 (خمس دقائق بعد منتصف الليل):

    ```
    5 0 * * * /usr/bin/php /path/to/your/project/api/cron_jobs.php >> /path/to/your/project/logs/cron.log 2>&1
    ```

### شرح الأمر:

*   `5 0 * * *`: هذا هو الجدول الزمني. ويعني "في الدقيقة 5 من الساعة 0 (منتصف الليل) من كل يوم من كل شهر من كل يوم من أيام الأسبوع".
*   `/usr/bin/php`: هذا هو المسار إلى ملف PHP التنفيذي. قد يكون مختلفًا على الخادم الخاص بك. يمكنك العثور على المسار الصحيح عن طريق تشغيل `which php` في الطرفية.
*   `/path/to/your/project/api/cron_jobs.php`: **يجب** استبدال هذا بالمسار المطلق إلى ملف `cron_jobs.php` على الخادم الخاص بك.
*   `>> /path/to/your/project/logs/cron.log 2>&1`: هذا الجزء اختياري ولكنه موصى به بشدة. يقوم بإعادة توجيه جميع المخرجات (سواء المخرجات القياسية أو الأخطاء) من البرنامج النصي وإلحاقها بملف سجل. هذا مفيد للغاية لتصحيح الأخطاء والتأكد من أن مهمة CRON تعمل بشكل صحيح. تأكد من أن مجلد `logs` موجود وقابل للكتابة.

3.  احفظ وأغلق ملف crontab. سيقوم خادم CRON بقراءة الجدول الجديد تلقائيًا، وستكون مهمتك نشطة.