// أنس توك — مواعيد الفجر حسب مكان الشخص (بتستخدم مكتبة adhan، رخصة MIT)
(function () {
  var A = window.adhan;
  var CITIES = [
    ['القاهرة', 30.0444, 31.2357, 'Africa/Cairo'], ['الإسكندرية', 31.2001, 29.9187, 'Africa/Cairo'],
    ['أسوان', 24.0889, 32.8998, 'Africa/Cairo'], ['مكة المكرمة', 21.4225, 39.8262, 'Asia/Riyadh'],
    ['المدينة المنورة', 24.4672, 39.6111, 'Asia/Riyadh'], ['الرياض', 24.7136, 46.6753, 'Asia/Riyadh'],
    ['جدة', 21.4858, 39.1925, 'Asia/Riyadh'], ['دبي', 25.2048, 55.2708, 'Asia/Dubai'],
    ['أبوظبي', 24.4539, 54.3773, 'Asia/Dubai'], ['الدوحة', 25.2854, 51.531, 'Asia/Qatar'],
    ['الكويت', 29.3759, 47.9774, 'Asia/Kuwait'], ['المنامة', 26.2285, 50.586, 'Asia/Bahrain'],
    ['مسقط', 23.588, 58.3829, 'Asia/Muscat'], ['عمّان', 31.9454, 35.9284, 'Asia/Amman'],
    ['القدس', 31.7683, 35.2137, 'Asia/Jerusalem'], ['بيروت', 33.8938, 35.5018, 'Asia/Beirut'],
    ['دمشق', 33.5138, 36.2765, 'Asia/Damascus'], ['بغداد', 33.3152, 44.3661, 'Asia/Baghdad'],
    ['الخرطوم', 15.5007, 32.5599, 'Africa/Khartoum'], ['طرابلس', 32.8872, 13.1913, 'Africa/Tripoli'],
    ['تونس', 36.8065, 10.1815, 'Africa/Tunis'], ['الجزائر', 36.7538, 3.0588, 'Africa/Algiers'],
    ['الدار البيضاء', 33.5731, -7.5898, 'Africa/Casablanca'], ['إسطنبول', 41.0082, 28.9784, 'Europe/Istanbul'],
    ['كراتشي', 24.8607, 67.0011, 'Asia/Karachi'], ['جاكرتا', -6.2088, 106.8456, 'Asia/Jakarta'],
    ['كوالالمبور', 3.139, 101.6869, 'Asia/Kuala_Lumpur'], ['لندن', 51.5074, -0.1278, 'Europe/London'],
    ['باريس', 48.8566, 2.3522, 'Europe/Paris'], ['برلين', 52.52, 13.405, 'Europe/Berlin'],
    ['نيويورك', 40.7128, -74.006, 'America/New_York'], ['تورنتو', 43.6532, -79.3832, 'America/Toronto'],
    ['لوس أنجلوس', 34.0522, -118.2437, 'America/Los_Angeles'], ['سيدني', -33.8688, 151.2093, 'Australia/Sydney']
  ];
  var METHOD_NAMES = {
    Egyptian: 'الهيئة المصرية العامة للمساحة', UmmAlQura: 'تقويم أم القرى', Dubai: 'دبي', Kuwait: 'الكويت',
    Qatar: 'قطر', Karachi: 'جامعة العلوم الإسلامية بكراتشي', Turkey: 'رئاسة الشؤون الدينية التركية',
    NorthAmerica: 'الجمعية الإسلامية لأمريكا الشمالية', Singapore: 'سنغافورة / ماليزيا / إندونيسيا',
    Tehran: 'طهران', MuslimWorldLeague: 'رابطة العالم الإسلامي'
  };
  function methodFor(tz) {
    tz = tz || '';
    if (/^Africa\/(Cairo|Khartoum)/.test(tz)) return 'Egyptian';
    if (/^Asia\/(Riyadh|Aden)/.test(tz)) return 'UmmAlQura';
    if (/^Asia\/(Dubai|Muscat)/.test(tz)) return 'Dubai';
    if (/^Asia\/Kuwait/.test(tz)) return 'Kuwait';
    if (/^Asia\/(Qatar|Bahrain)/.test(tz)) return 'Qatar';
    if (/^Asia\/(Karachi|Kolkata|Calcutta|Dhaka|Kabul)/.test(tz)) return 'Karachi';
    if (/^Europe\/Istanbul/.test(tz)) return 'Turkey';
    if (/^America\//.test(tz)) return 'NorthAmerica';
    if (/^Asia\/(Singapore|Kuala_Lumpur|Jakarta|Makassar|Jayapura|Brunei)/.test(tz)) return 'Singapore';
    if (/^Asia\/Tehran/.test(tz)) return 'Tehran';
    return 'MuslimWorldLeague';
  }
  var deviceTz = (function () { try { return Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) { return ''; } })();
  function cityPlace(c) { return {name: c[0], lat: c[1], lng: c[2], tz: c[3], method: methodFor(c[3])}; }
  function defaultPlace() {
    for (var i = 0; i < CITIES.length; i++) if (CITIES[i][3] === deviceTz) return cityPlace(CITIES[i]);
    return cityPlace(CITIES[3]); // مكة
  }
  function getPlace() {
    try { var p = JSON.parse(localStorage.getItem('anastalk-place') || 'null'); if (p && isFinite(p.lat)) return p; } catch (e) {}
    return defaultPlace();
  }
  function setPlace(p) { try { localStorage.setItem('anastalk-place', JSON.stringify(p)); } catch (e) {} }
  function locate() {
    return new Promise(function (res, rej) {
      if (!navigator.geolocation) return rej(new Error('nogeo'));
      navigator.geolocation.getCurrentPosition(function (pos) {
        var p = {name: 'موقعك الحالي', lat: +pos.coords.latitude.toFixed(4), lng: +pos.coords.longitude.toFixed(4),
                 tz: deviceTz, method: methodFor(deviceTz), gps: true};
        setPlace(p); res(p);
      }, rej, {enableHighAccuracy: false, timeout: 15000, maximumAge: 6 * 3600e3});
    });
  }
  function fajrOn(p, day) {
    var params = A.CalculationMethod[p.method] ? A.CalculationMethod[p.method]() : A.CalculationMethod.MuslimWorldLeague();
    return new A.PrayerTimes(new A.Coordinates(p.lat, p.lng), day, params).fajr;
  }
  // مواعيد الفجر الجاية (بداية من دلوقتي) لعدد أيام
  function upcoming(p, days) {
    var out = [], now = Date.now(), d = new Date();
    d.setHours(12, 0, 0, 0); d.setDate(d.getDate() - 1);
    for (var i = 0; out.length < days && i < days + 3; i++) {
      var t = fajrOn(p, new Date(d.getTime() + i * 864e5));
      if (t && t.getTime() > now) out.push(t);
    }
    return out;
  }
  function fmtTime(t, p) {
    var o = {hour: 'numeric', minute: '2-digit'};
    if (p && p.tz) o.timeZone = p.tz;
    try { return t.toLocaleTimeString('ar-EG', o); } catch (e) { delete o.timeZone; return t.toLocaleTimeString('ar-EG', o); }
  }
  function fmtLeft(ms) {
    var m = Math.max(0, Math.round(ms / 60000)), h = Math.floor(m / 60); m = m % 60;
    function n(x, one, two, many) { return x === 1 ? one : x === 2 ? two : (x >= 3 && x <= 10 ? x + ' ' + many : x + ' ' + one); }
    if (!h) return 'فاضل ' + n(m, 'دقيقة', 'دقيقتين', 'دقايق');
    return 'فاضل ' + n(h, 'ساعة', 'ساعتين', 'ساعات') + (m ? ' و' + n(m, 'دقيقة', 'دقيقتين', 'دقايق') : '');
  }
  // الاشتراك في إشعار الفجر بيستخدم نفس الـ Service Worker بتاع السبحة
  var BASE = (location.pathname.indexOf('/sebha-app/') === 0 || location.hostname === 'sebha.anastalk.com') ? '' : '/sebha-app/';
  function swReg() {
    return navigator.serviceWorker.getRegistration(BASE ? '/sebha-app/' : undefined).then(function (r) {
      return r || navigator.serviceWorker.register(BASE + 'sw.js', {updateViaCache: 'none'});
    }).then(function (r) {
      return r.update().catch(function () {}).then(function () {
        var w = r.installing || r.waiting; if (!w && r.active) return r;
        return new Promise(function (ok) { var x = r.installing || r.waiting; if (!x) return ok(r);
          x.addEventListener('statechange', function () { if (x.state === 'activated') ok(r); }); setTimeout(function () { ok(r); }, 6000); });
      });
    });
  }
  function u8(b) { b = b.replace(/-/g, '+').replace(/_/g, '/'); b += '='.repeat((4 - b.length % 4) % 4);
    var r = atob(b), a = new Uint8Array(r.length); for (var i = 0; i < r.length; i++) a[i] = r.charCodeAt(i); return a; }
  function post(body) {
    return fetch(BASE + 'push/subscribe.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body)})
      .then(function (r) { return r.json(); });
  }
  function fajrPayload(p) {
    var ts = upcoming(p, 30).map(function (t) { return Math.round(t.getTime() / 1000); });
    return {times: ts, place: p.gps ? '' : p.name, time_label: ts.length ? fmtTime(new Date(ts[0] * 1000), p) : ''};
  }
  function isOn() { try { return localStorage.getItem('anastalk-fajr-on') === '1'; } catch (e) { return false; } }
  function setOnFlag(v) { try { localStorage.setItem('anastalk-fajr-on', v ? '1' : '0'); } catch (e) {} }
  function enable(p) {
    return swReg().then(function (reg) {
      return Notification.requestPermission().then(function (perm) {
        if (perm !== 'granted') throw new Error('denied');
        return reg.pushManager.getSubscription().then(function (s) {
          if (s) return s;
          return fetch(BASE + 'push/subscribe.php', {cache: 'no-store'}).then(function (r) { return r.json(); })
            .then(function (k) { return reg.pushManager.subscribe({userVisibleOnly: true, applicationServerKey: u8(k.key)}); });
        });
      });
    }).then(function (sub) {
      return post({sub: sub.toJSON(), tz: deviceTz, set: {fajr: true}, fajr: fajrPayload(p)});
    }).then(function (r) { setOnFlag(true); return r; });
  }
  function disable() {
    return swReg().then(function (reg) { return reg.pushManager.getSubscription(); }).then(function (s) {
      setOnFlag(false);
      if (!s) return;
      return post({sub: s.toJSON(), set: {fajr: false}}).then(function (r) { if (r && r.removed) return s.unsubscribe(); });
    });
  }
  // كل ما الصفحة تتفتح نحدّث مواعيد الـ 30 يوم الجايين على السيرفر
  function refresh() {
    if (!isOn() || !('serviceWorker' in navigator) || !('PushManager' in window)) return;
    swReg().then(function (reg) { return reg.pushManager.getSubscription(); }).then(function (s) {
      if (s) post({sub: s.toJSON(), tz: deviceTz, set: {fajr: true}, fajr: fajrPayload(getPlace()), quiet: true}).catch(function () {});
    }).catch(function () {});
  }
  window.AnasFajr = {CITIES: CITIES, METHOD_NAMES: METHOD_NAMES, getPlace: getPlace, setPlace: setPlace, cityPlace: cityPlace,
    locate: locate, upcoming: upcoming, fmtTime: fmtTime, fmtLeft: fmtLeft, enable: enable, disable: disable,
    isOn: isOn, refresh: refresh, swReg: swReg, BASE: BASE};
})();
