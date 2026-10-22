<?php
// Avoid Yii services here so this document also works during request failures.
$code=isset($error['code']) ? (int)$error['code'] : 500;
if($code<400 || $code>599) $code=500;
$messages=array(
    400=>array('این درخواست به مقصد نرسید.', 'درخواست شما قابل پردازش نیست. از صفحهٔ اصلی دوباره شروع کنید.'),
    403=>array('این مسیر دسترسی می‌خواهد.', 'اجازهٔ ورود به این صفحه را ندارید. می‌توانید به فروشگاه برگردید.'),
    404=>array('انگار مسیر را گم کردیم!', 'صفحه‌ای که دنبالش هستید پیدا نشد؛ اما چیزهای خوب هنوز در بازارچه منتظرند.'),
    405=>array('از این مسیر نمی‌شود رفت.', 'این صفحه با روش ارسال درخواست شما باز نمی‌شود. از صفحهٔ اصلی ادامه دهید.'),
    429=>array('کمی آهسته‌تر، لطفاً.', 'درخواست‌های زیادی دریافت کردیم. کمی صبر کنید و دوباره به بازارچه سر بزنید.'),
    503=>array('کمی مکث، دوباره برمی‌گردیم.', 'بازارچه موقتاً در دسترس نیست. لطفاً کمی بعد دوباره سر بزنید.'),
);
$copy=isset($messages[$code]) ? $messages[$code] : ($code>=500
    ? array('یک وقفهٔ کوچک در مسیر.', 'در نمایش این صفحه مشکلی پیش آمده. لطفاً کمی بعد دوباره تلاش کنید.')
    : array('این مسیر به مقصد نرسید.', 'امکان نمایش این صفحه وجود ندارد. از صفحهٔ اصلی بازارچه ادامه دهید.'));
$script=isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\','/',$_SERVER['SCRIPT_NAME']) : '';
$base=substr($script,-10)==='/index.php' ? substr($script,0,-10) : '';
$base='/'.trim($base,'/');
if($base==='/') $base='';
$escape=function($value) { return htmlspecialchars($value,ENT_QUOTES,'UTF-8'); };
$home=$escape($base.'/');
$shop=$escape($base.'/shop');
$faCode=strtr((string)$code,array('0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹'));
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#f8f8f4">
    <title><?= $escape($copy[0]) ?> | بازارچه</title>
    <style>
    @font-face{font-family:Vazir;src:url('<?= $escape($base) ?>/assets/fonts/Vazir-Regular.woff2') format('woff2');font-weight:400;font-display:swap}
    @font-face{font-family:Vazir;src:url('<?= $escape($base) ?>/assets/fonts/Vazir-Bold.woff2') format('woff2');font-weight:700 900;font-display:swap}
    <?php readfile(__DIR__.'/../../../public/assets/css/error.css'); ?>
    </style>
</head>
<body>
    <a class="skip" href="#error-content">رفتن به محتوا</a>
    <div class="error-page">
        <header class="error-header">
            <a class="brand" href="<?= $home ?>" aria-label="بازارچه، صفحهٔ اصلی"><span class="brand-mark" aria-hidden="true">ب</span><span>بازارچه</span></a>
            <a class="header-link" href="<?= $shop ?>">رفتن به فروشگاه <span aria-hidden="true">↖</span></a>
        </header>
        <main id="error-content" class="error-main" tabindex="-1">
            <div class="error-copy">
                <p class="error-label">گاهی مسیر، کمی عوض می‌شود</p>
                <h1><?= $escape($copy[0]) ?></h1>
                <p class="error-description"><?= $escape($copy[1]) ?></p>
                <div class="error-actions">
                    <a class="button button-primary" href="<?= $home ?>">بازگشت به خانه <span aria-hidden="true">←</span></a>
                    <a class="button button-secondary" href="<?= $shop ?>">گشت‌وگذار در فروشگاه</a>
                </div>
            </div>
            <div class="error-art" aria-hidden="true">
                <span class="error-number" dir="ltr"><?= $code ?></span>
                <svg class="parcel-scene" viewBox="0 0 540 440" xmlns="http://www.w3.org/2000/svg" fill="none">
                    <ellipse cx="270" cy="367" rx="176" ry="28" fill="#e9ece2"/>
                    <ellipse class="parcel-shadow" cx="273" cy="358" rx="85" ry="12" fill="#c8d1bc" opacity=".55"/>
                    <path d="M93 298C34 213 101 134 186 152M348 126C448 94 503 192 447 272" stroke="#c2cbb7" stroke-width="1.5" stroke-dasharray="5 9"/>
                    <g class="leaf leaf-one"><path d="M106 308C61 293 55 258 67 237C93 242 112 265 106 308Z" fill="#a4b18f"/><path d="M106 324L74 253" stroke="#657a53" stroke-width="2" stroke-linecap="round"/></g>
                    <g class="leaf leaf-two"><path d="M434 326C423 285 440 259 472 256C476 286 461 313 434 326Z" fill="#bdc6a9"/><path d="M426 345L459 273" stroke="#7f9167" stroke-width="2" stroke-linecap="round"/></g>
                    <g class="floating-parcel">
                        <path d="M155 213L267 156L386 213L275 274L155 213Z" fill="#e1bf8d" stroke="#ac875b" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M155 213V314L275 373V274L155 213Z" fill="#cfaa76" stroke="#ac875b" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M275 274L386 213V314L275 373V274Z" fill="#e7c797" stroke="#ac875b" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M208 186L325 246V278L346 266V235L229 175" fill="#f5e4bf"/>
                        <path d="M155 213L207 182L171 135L110 166L155 213Z" fill="#ecd2a9" stroke="#ac875b" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M267 156L323 126L437 181L386 213L267 156Z" fill="#f0d8b3" stroke="#ac875b" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M155 213L275 274L241 303L118 242L155 213Z" fill="#e7c797" stroke="#ac875b" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M275 274L386 213L423 247L311 309L275 274Z" fill="#f4dfbd" stroke="#ac875b" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M183 279L216 295V322L183 306V279Z" fill="#f9f2e3"/>
                        <path d="M190 291L208 300M190 299L201 305" stroke="#b99d77" stroke-width="2"/>
                        <path d="M325 310V329M333 306V325M341 302V321" stroke="#ac875b" stroke-width="2"/>
                    </g>
                    <g class="floating-tag">
                        <path d="M247 134V111C247 97 258 86 272 86C286 86 297 97 297 111V134" stroke="#405b41" stroke-width="5" stroke-linecap="round"/>
                        <rect x="236" y="117" width="72" height="59" rx="12" fill="#405b41"/>
                        <path d="M255 147L266 157L288 135" stroke="#f8f8f4" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </g>
                    <g class="spark spark-one" stroke="#8c9f76" stroke-width="2" stroke-linecap="round"><path d="M146 100V116M138 108H154"/></g>
                    <g class="spark spark-two" stroke="#8c9f76" stroke-width="2" stroke-linecap="round"><path d="M399 108V120M393 114H405"/></g>
                </svg>
                <span class="art-caption">حتی مسیرهای اشتباه هم، راه برگشت دارند.</span>
            </div>
        </main>
        <footer class="error-footer"><span>بازارچه؛ انتخاب‌های کوچک، حس خوب زندگی.</span><span>کد خطا <b dir="ltr"><?= $faCode ?></b></span></footer>
    </div>
</body>
</html>
