// أنس توك — Service Worker: يخلّي السبحة تفتح حتى من غير نت
const V = 'anastalk-v3';
const SHELL = ['./', 'index.html', 'icon-192.png', 'icon-512.png', 'mark.svg', 'install.js'];
self.addEventListener('install', e => {
  e.waitUntil(caches.open(V).then(c => c.addAll(SHELL)).then(() => self.skipWaiting()));
});
self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(ks => Promise.all(ks.filter(k => k !== V).map(k => caches.delete(k)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', e => {
  const req = e.request, url = new URL(req.url);
  if (req.method !== 'GET' || url.pathname.endsWith('api.php')) return; // العداد الجماعي دايمًا من النت
  if (req.mode === 'navigate' || url.origin === location.origin) {
    // النت الأول، ولو مفيش نت نفتح النسخة المحفوظة
    e.respondWith(fetch(req).then(r => { const cp = r.clone(); caches.open(V).then(c => c.put(req, cp)); return r; })
      .catch(() => caches.match(req, {ignoreSearch: true}).then(r => r || caches.match('index.html'))));
    return;
  }
  if (/fonts\.(googleapis|gstatic)\.com$/.test(url.hostname)) {
    e.respondWith(caches.open(V).then(c => c.match(req).then(hit => {
      const net = fetch(req).then(r => { c.put(req, r.clone()); return r; }).catch(() => hit);
      return hit || net;
    })));
  }
});

// الإشعارات
self.addEventListener('push', e => {
  let m = {};
  try { m = e.data ? e.data.json() : {}; } catch (_) { m = {body: e.data && e.data.text()}; }
  const title = m.title || 'أنس توك · وقت الاستغفار';
  e.waitUntil(self.registration.showNotification(title, {
    body: m.body || 'أستغفر الله العظيم وأتوب إليه 🤍',
    icon: 'icon-192.png', badge: 'badge-96.png', dir: 'rtl', lang: 'ar',
    tag: m.tag || 'reminder', renotify: true,
    data: {url: new URL(m.url || './?source=push', self.registration.scope).href}
  }));
});
self.addEventListener('notificationclick', e => {
  e.notification.close();
  const url = (e.notification.data && e.notification.data.url) || self.registration.scope;
  e.waitUntil(clients.matchAll({type: 'window', includeUncontrolled: true}).then(ws => {
    for (const w of ws) if (w.url.startsWith(self.registration.scope) && 'focus' in w) return w.focus();
    return clients.openWindow(url);
  }));
});
