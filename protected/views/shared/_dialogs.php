<div id="page-progress" role="progressbar" aria-label="در حال بارگذاری"><span></span></div>
<div id="toast-region" role="status" aria-live="polite"></div>
<dialog id="message-dialog" class="message-dialog"><form method="dialog"><h2 id="message-title"></h2><p id="message-body"></p><input id="message-input" aria-label="مقدار" hidden><div class="dialog-actions"><button value="ok" class="btn btn-primary">تأیید</button><button value="cancel" class="btn btn-outline" data-message-cancel>انصراف</button></div></form></dialog>
<?php if(AccessService::isAdmin()): ?>
<dialog id="media-dialog" class="media-dialog"><header class="media-header"><div><span class="eyebrow">کتابخانه مشترک</span><h2>انتخاب رسانه</h2></div><button type="button" class="icon-button" data-close-media aria-label="بستن">×</button></header>
<div class="media-toolbar"><input type="search" id="media-search" placeholder="جستجو در نام فایل‌ها…" aria-label="جستجوی رسانه"><label class="btn btn-primary">آپلود فایل<input type="file" id="media-upload" multiple hidden accept="image/jpeg,image/png,image/webp,application/pdf,text/plain"></label></div>
<div id="media-scopes" class="media-scope-tabs" role="tablist" aria-label="بخش رسانه" hidden><button type="button" role="tab" data-media-scope="all" aria-selected="false">تمام رسانه‌های سایت</button><button type="button" role="tab" data-media-scope="catalogs" aria-selected="true">آپلودهای کاتالوگ</button></div>
<div id="media-drop" class="media-drop" tabindex="0">فایل‌ها را اینجا رها کنید یا از دکمه آپلود استفاده کنید<small>تصویر ← WebP در سه اندازه · PDF و TXT · حداکثر ۱۰ مگابایت</small></div>
<div id="media-feedback" role="status" aria-live="polite"></div><div class="media-library-scroll"><div id="media-grid" class="media-grid"></div><button type="button" id="media-more" class="btn btn-outline" hidden>نمایش بیشتر</button></div>
<footer class="media-footer"><span id="media-count">۰ فایل انتخاب شده</span><div><button type="button" class="btn btn-outline" data-close-media>انصراف</button><button type="button" class="btn btn-primary" id="media-apply">استفاده از انتخاب‌ها</button></div></footer></dialog>
<?php endif; ?>
