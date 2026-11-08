// أنس توك — رسالة "حمّل التطبيق" اللي بتظهر للي يفتح الموقع
(function () {
  var standalone = (window.matchMedia && matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
  if (standalone) return;

  var KEY = 'anastalk-install-dismissed';
  try { var t = +localStorage.getItem(KEY); if (t && Date.now() - t < 3 * 864e5) return; } catch (e) {}

  var ua = navigator.userAgent || '';
  var isIOS = /iPhone|iPad|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isAndroid = /Android/.test(ua);
  var inApp = /Instagram|FBAN|FBAV|FB_IAB|Line\/|TikTok|musical_ly|Snapchat|Twitter/i.test(ua);
  var deferred = null;
  var ICON = (window.ANASTALK_ICON || '/sebha-app/icon-192.png');

  var css = '' +
    '.at-sheet{position:fixed;left:12px;right:12px;bottom:12px;z-index:9999;max-width:480px;margin:0 auto;direction:rtl;' +
    'font-family:Cairo,system-ui,sans-serif;color:#F2E9D2;background:#24403C;border:1px solid rgba(230,203,184,.35);' +
    'border-radius:22px;padding:14px 14px 14px 12px;box-shadow:0 18px 50px rgba(0,0,0,.45);' +
    'transform:translateY(140%);transition:transform .45s cubic-bezier(.2,.9,.3,1.2)}' +
    '.at-sheet.on{transform:none}' +
    '.at-row{display:flex;align-items:center;gap:12px}' +
    '.at-row img{width:54px;height:54px;border-radius:14px;flex:none;box-shadow:0 4px 14px rgba(0,0,0,.3)}' +
    '.at-txt{flex:1;min-width:0}' +
    '.at-txt b{display:block;font-size:16px;font-weight:800;line-height:1.3}' +
    '.at-txt span{display:block;font-size:12.5px;color:#E6CBB8;opacity:.9;line-height:1.5}' +
    '.at-btn{flex:none;border:0;border-radius:999px;padding:10px 18px;font:800 15px Cairo,system-ui,sans-serif;' +
    'color:#F2E9D2;background:#AC3032;cursor:pointer;box-shadow:0 6px 16px rgba(172,48,50,.35)}' +
    '.at-x{position:absolute;top:-10px;left:-6px;width:28px;height:28px;border-radius:50%;border:1px solid rgba(230,203,184,.35);' +
    'background:#1D312F;color:#E6CBB8;font-size:16px;line-height:26px;cursor:pointer;padding:0}' +
    '.at-help{display:none;margin-top:12px;padding:12px 14px;border-radius:14px;background:rgba(29,49,47,.8);font-size:14px;line-height:1.9}' +
    '.at-help.on{display:block}' +
    '.at-help .k{display:inline-block;padding:0 8px;border-radius:8px;background:rgba(230,203,184,.18);color:#F2E9D2;font-weight:700}' +
    '.at-copy{margin-top:8px;border:1px solid rgba(230,203,184,.4);background:transparent;color:#E6CBB8;border-radius:999px;' +
    'padding:6px 14px;font:700 13px Cairo,system-ui,sans-serif;cursor:pointer}';

  function help() {
    if (inApp) {
      return 'الأول افتح الصفحة في المتصفح: دوس على <span class="k">⋯</span> أو <span class="k">⋮</span> فوق، واختار ' +
        '<span class="k">' + (isIOS ? 'فتح في Safari' : 'فتح في المتصفح') + '</span>، وبعدين دوس «تحميل» تاني.' +
        '<br><button class="at-copy" type="button">انسخ اللينك</button>';
    }
    if (isIOS) {
      return 'دوس على زرار المشاركة <span class="k">⬆︎</span> تحت في Safari، وبعدين اختار ' +
        '<span class="k">إضافة إلى الشاشة الرئيسية</span> ثم <span class="k">إضافة</span>.';
    }
    if (isAndroid) {
      return 'من قائمة المتصفح <span class="k">⋮</span> فوق، اختار <span class="k">تثبيت التطبيق</span> أو ' +
        '<span class="k">إضافة إلى الشاشة الرئيسية</span>.';
    }
    return 'من شريط العنوان فوق، دوس على أيقونة التثبيت <span class="k">⊕</span>، أو من قائمة المتصفح اختار <span class="k">تثبيت أنس توك</span>.';
  }

  var el;
  function build() {
    if (el) return;
    var st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);
    el = document.createElement('div');
    el.className = 'at-sheet'; el.setAttribute('role', 'dialog'); el.setAttribute('aria-label', 'حمّل تطبيق أنس توك');
    el.innerHTML = '<button class="at-x" type="button" aria-label="إغلاق">×</button>' +
      '<div class="at-row"><img src="' + ICON + '" alt="">' +
      '<div class="at-txt"><b>حمّل تطبيق أنس توك</b><span>السبحة على شاشتك الرئيسية، تفتح بضغطة واحدة 🤍</span></div>' +
      '<button class="at-btn" type="button">تحميل</button></div>' +
      '<div class="at-help">' + help() + '</div>';
    document.body.appendChild(el);
    el.querySelector('.at-x').onclick = function () {
      try { localStorage.setItem(KEY, Date.now()); } catch (e) {}
      el.classList.remove('on');
    };
    el.querySelector('.at-btn').onclick = function () {
      if (deferred) {
        deferred.prompt();
        deferred.userChoice.then(function (c) { if (c.outcome === 'accepted') el.classList.remove('on'); deferred = null; });
      } else {
        el.querySelector('.at-help').classList.toggle('on');
      }
    };
    var cp = el.querySelector('.at-copy');
    if (cp) cp.onclick = function () {
      var u = location.origin + '/';
      (navigator.clipboard ? navigator.clipboard.writeText(u) : Promise.reject()).then(function () { cp.textContent = 'اتنسخ ✓'; })
        .catch(function () { prompt('انسخ اللينك:', u); });
    };
    setTimeout(function () { el.classList.add('on'); }, 60);
  }

  window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); deferred = e; build(); });
  window.addEventListener('appinstalled', function () { if (el) el.classList.remove('on'); });
  // على آيفون والمتصفحات اللي مبتبعتش beforeinstallprompt نظهر الرسالة بعد ثانية ونص
  function later() { setTimeout(build, 1500); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', later); else later();
})();
