# WA Queue — Enterprise WhatsApp Queue Management

Laravel 12 + Vue 3 + PrimeVue + SQLite + Multi-Tenancy

## الموقع

`C:\xampp\htdocs\wa-queue`

## المتطلبات

- PHP 8.2+
- Composer
- Node.js 18+
- [Laravel Herd](https://herd.laravel.com) أو XAMPP

**لا حاجة لـ MySQL** — المشروع يستخدم SQLite بالكامل.

## قاعدة البيانات (SQLite)

| الملف | الغرض |
|-------|--------|
| `database/database.sqlite` | القاعدة المركزية (tenants, domains, jobs) |
| `database/tenantdemo` | قاعدة المستأجر demo (senders, queue, settings) |

## التشغيل السريع

```bash
cd C:\xampp\htdocs\wa-queue

# أول مرة فقط
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # Linux/Mac
# Windows: New-Item database\database.sqlite -ItemType File

php artisan migrate
php artisan wa:setup-local wa-queue.test
npm run build
```

افتح: **https://wa-queue.test** (داشبورد الزبون demo)

## لوحة الإدارة المركزية (أنت — مدير المنصة)

```
https://central.wa-queue.test/admin
```

أو محلياً:

```
http://localhost/admin
```

| الصفحة | الوظيفة |
|--------|---------|
| **مراقبة الزبائن** | كل الزبائن + أرقام WhatsApp + حالة الربط |
| **إدارة الزبائن** | إضافة زبون + دومين جديد |

### إضافة زبون من الواجهة
1. افتح `/admin/tenants`
2. **زبون جديد** → اسم الشركة + الدومين (مثل `acme.wa-queue.test`)
3. يُنشأ تلقائياً: قاعدة SQLite + جداول + دومين
4. الزبون يدخل من: `https://acme.wa-queue.test`

### فحص ربط WhatsApp
- **فحص الكل الآن** — يتحقق من TextMeBot API لكل الأرقام
- **فحص الربط** — لزبون واحد

## Workers (للإرسال)

```bash
php artisan wa:dispatch --loop
php artisan queue:work
```

## إنشاء مستأجر جديد

```bash
php artisan tinker
>>> $t = App\Models\Tenant::create(['id' => 'acme']);
>>> $t->domains()->create(['domain' => 'acme.wa-queue.test']);
# يُنشئ database/tenantacme تلقائياً ويهاجرها
```

## API — إضافة رسالة للطابور

```http
POST https://wa-queue.test/api/v1/queue
Content-Type: application/json

{
  "phone": "+9647501234567",
  "message": "اختبار",
  "source": "support",
  "event": "test"
}
```

## المعمارية الكاملة

[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
