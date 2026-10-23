<section class="panel mcp-setup" data-mcp-setup aria-labelledby="mcp-setup-title">
<div class="mcp-setup-heading"><div><span class="eyebrow">اتصال آماده است</span><h2 id="mcp-setup-title">تنظیمات MCP برای <?= CHtml::encode($this->issued['name']) ?></h2></div><span class="mcp-validity">معتبر تا <?= CHtml::encode(JalaliDate::format($this->issued['expires_at'])) ?></span></div>
<p>کدهای زیر شامل توکن تازهٔ شما هستند و فقط همین بار نمایش داده می‌شوند. پیش از خروج، تنظیمات اتصال را در برنامهٔ AI یا یک فایل خصوصی ذخیره کنید.</p>
<?php if(!$this->enabled): ?><div class="flash flash-error">API هنوز غیرفعال است. بعد از ذخیرهٔ کد اتصال، گزینهٔ «API فعال باشد» را در همین صفحه روشن کنید و وضعیت را ذخیره کنید.</div><?php endif; ?>
<div class="field"><label for="mcp-new-token">توکن جدید</label><div class="mcp-copy-row"><input id="mcp-new-token" type="text" readonly dir="ltr" aria-label="توکن جدید" value="<?= CHtml::encode($this->issued['token']) ?>" autocomplete="off" spellcheck="false"><button type="button" class="btn btn-outline btn-sm" data-mcp-copy="mcp-new-token">کپی توکن</button></div></div>
<p class="muted">این اتصال تا تاریخ بالا و تا زمانی که توکن لغو نشده، API فعال باشد و حساب مالک مجاز باشد کار می‌کند. پس از انقضا یا لغو، توکن تازه بسازید و تنظیمات کلاینت را به‌روز کنید.</p>
<div class="mcp-permissions"><strong>مجوزهای این اتصال:</strong><?php $labels=ApiTokenService::scopes();foreach($this->issued['scopes'] as $scope): ?><span><?= CHtml::encode($labels[$scope]??$scope) ?></span><?php endforeach; ?></div>
<h3>۱. فایل‌های اتصال را روی کامپیوترتان آماده کنید</h3>
<p>سورس پروژه را از <a href="https://github.com/emadgh/yii1-shop" target="_blank" rel="noopener noreferrer">مخزن بازارچه</a> دریافت کنید و Node.js نسخهٔ ۲۰ یا بالاتر را نصب کنید. در پوشهٔ سورس، دستور <code dir="ltr">npm ci</code> را اجرا کنید. فایل اتصال در <code dir="ltr">integrations/ai/mcp-server.mjs</code> قرار دارد.</p>
<p class="muted">MCP روی کامپیوترِ برنامهٔ AI اجرا می‌شود و به API سایت وصل می‌شود؛ روی هاست اشتراکی به Node.js نیاز ندارید. بستهٔ نصب هاست شامل ابزارهای MCP نیست؛ سورس مخزن را دریافت کنید.</p>
<div class="field"><label for="mcp-project-path">مسیر کامل پوشهٔ سورس روی کامپیوتر شما</label><input id="mcp-project-path" data-mcp-project-path dir="ltr" value="<?= CHtml::encode($root) ?>" spellcheck="false" autocomplete="off"><small class="muted">مثلاً C:/projects/yii1-shop یا /Users/you/projects/yii1-shop؛ این مسیر مربوط به کامپیوتر شماست، نه هاست. با تغییرش، کدهای پایین به‌روز می‌شوند.</small></div>
<h3>۲. تنظیمات آماده را در برنامهٔ AI وارد کنید</h3>
<p>در برنامه‌هایی که تنظیمات <code dir="ltr">mcpServers</code> دارند، JSON زیر را با تنظیمات فعلی ادغام کنید. آدرس سایت و توکن از قبل تکمیل شده‌اند؛ فقط مسیر پوشهٔ سورس را در فیلد بالا تنظیم کنید.</p>
<div class="mcp-code-heading"><label for="mcp-direct-config">تنظیمات کامل MCP، شامل توکن</label><button type="button" class="btn btn-outline btn-sm" data-mcp-copy="mcp-direct-config">کپی تنظیمات MCP</button></div>
<textarea id="mcp-direct-config" data-mcp-direct readonly dir="ltr" rows="15" spellcheck="false" autocomplete="off"><?= CHtml::encode($direct) ?></textarea>
<details class="mcp-private"><summary>روش جایگزین: نگهداری توکن در فایل خصوصی</summary>
<p>اگر ترجیح می‌دهید توکن داخل تنظیمات MCP نباشد، فایل خصوصی زیر را روی همان کامپیوتر بسازید؛ آن را داخل پوشهٔ عمومی سایت یا Git نگذارید.</p>
<div class="field"><label for="mcp-private-path">مسیر کامل فایل خصوصی</label><input id="mcp-private-path" data-mcp-private-path dir="ltr" value="<?= CHtml::encode($privatePath) ?>" spellcheck="false" autocomplete="off"></div>
<div class="mcp-code-heading"><label for="mcp-credential">محتوای فایل خصوصی</label><button type="button" class="btn btn-outline btn-sm" data-mcp-copy="mcp-credential">کپی محتوای فایل</button></div><textarea id="mcp-credential" readonly dir="ltr" rows="5" spellcheck="false" autocomplete="off"><?= CHtml::encode($credential) ?></textarea>
<div class="mcp-code-heading"><label for="mcp-private-config">تنظیمات MCP با فایل خصوصی</label><button type="button" class="btn btn-outline btn-sm" data-mcp-copy="mcp-private-config">کپی تنظیمات فایل خصوصی</button></div><textarea id="mcp-private-config" data-mcp-private readonly dir="ltr" rows="14" spellcheck="false" autocomplete="off"><?= CHtml::encode($privateConfig) ?></textarea>
</details>
<h3>۳. اتصال را بررسی کنید</h3>
<p>برنامهٔ AI را دوباره باز کنید یا اتصال MCP را بازخوانی کنید. ابزارهای <code dir="ltr">bazaarche_*</code> باید ظاهر شوند. سپس از دستیار بخواهید: «اتصال بازارچه و مجوزهای دسترسی من را بررسی کن.»</p>
<details><summary>راهنمای رفع مشکل</summary><ul><li>خطای پیدا نشدن Node یا فایل: نصب Node.js و مسیر کامل فایل اتصال روی کامپیوتر را بررسی کنید.</li><li>خطای 401: توکن اشتباه، منقضی یا لغو شده است.</li><li>خطای 403: مجوز عملیات در توکن یا نقش مالک وجود ندارد.</li><li>خطای 503: API از بخش «اتصال مدیریت هوشمند» غیرفعال است.</li><li>برای سایت روی هاست از HTTPS استفاده کنید. آدرس API باید به <code dir="ltr">/api/v1/</code> ختم شود. برنامه‌هایی که فقط MCP از نوع HTTP می‌پذیرند، به این اتصال stdio به‌صورت مستقیم متصل نمی‌شوند.</li></ul></details>
<p class="muted">کدهای دارای توکن را فقط در تنظیمات خصوصی کلاینت نگهداری کنید؛ داخل چت عمومی یا مخزن قرار ندهید.</p>
<p class="mcp-copy-status" data-mcp-copy-status role="status" aria-live="polite"></p>
</section>
