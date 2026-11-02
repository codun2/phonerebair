/* موبايل فيكس — Service Worker: يفتح اللوحة ويعرض آخر بيانات محفوظة دون اتصال.
 * - ملفات التطبيق (_nuxt) تُخزَّن مسبقاً عند التثبيت (القائمة تُحقن وقت البناء).
 * - صفحات اللوحة SPA: عند الانقطاع تُخدم الصدفة المحفوظة.
 * - طلبات GET للـ API: الشبكة أولاً، ثم النسخة المحفوظة مع ترويسة X-Pr-Cache.
 * - الكتابة (POST/PUT/DELETE) لا تمرّ من هنا — يتولّاها صندوق الانتظار في التطبيق. */
const PRECACHE = self.__PRECACHE__ || { version: 'dev', files: [] }
const SHELL = 'pr-shell-' + PRECACHE.version
const RUNTIME = 'pr-runtime'
const API = 'pr-api'
const API_TIMEOUT = 7000
let apiPausedUntil = 0

self.addEventListener('install', (e) => {
  e.waitUntil((async () => {
    const c = await caches.open(SHELL)
    // الصدفة: كل صفحات /admin و /super ترجع نفس HTML (ssr:false)
    for (const u of ['/admin', '/super', '/login', '/manifest.webmanifest', '/favicon.svg', '/icon-192.png', '/icon-512.png']) {
      try { const r = await fetch(u, { cache: 'reload' }); if (r.ok) await c.put(u, r) } catch {}
    }
    // ملفات البناء على دفعات حتى لا يفشل التثبيت بملف واحد
    const files = PRECACHE.files || []
    for (let i = 0; i < files.length; i += 20) {
      await Promise.all(files.slice(i, i + 20).map(async (u) => {
        try { const r = await fetch(u); if (r.ok) await c.put(u, r) } catch {}
      }))
    }
    self.skipWaiting()
  })())
})

self.addEventListener('activate', (e) => {
  e.waitUntil((async () => {
    for (const k of await caches.keys()) {
      if (k.startsWith('pr-shell-') && k !== SHELL) await caches.delete(k)
    }
    await self.clients.claim()
  })())
})

self.addEventListener('message', (e) => {
  if (e.data === 'CLEAR_API' || e.data === 'RESET_API') {
    // خروج: امسح وأوقف التخزين لحظات (طلبات المستخدم السابق الجارية)؛ دخول: امسح فقط
    if (e.data === 'CLEAR_API') apiPausedUntil = Date.now() + 3000
    e.waitUntil(caches.delete(API))
  }
})

const isNav = (req) => req.mode === 'navigate'
const isApp = (p) => p === '/admin' || p.startsWith('/admin/') || p === '/super' || p.startsWith('/super/')

async function networkFirstPage(req, url) {
  const c = await caches.open(SHELL)
  try {
    const r = await fetch(req)
    if (r.ok && isApp(url.pathname)) c.put(url.pathname.startsWith('/super') ? '/super' : '/admin', r.clone())
    else if (r.ok) (await caches.open(RUNTIME)).put(url.pathname, r.clone())
    return r
  } catch {
    if (isApp(url.pathname)) {
      const shell = await c.match(url.pathname.startsWith('/super') ? '/super' : '/admin')
      if (shell) return shell
    }
    const hit = (await c.match(url.pathname)) || (await caches.match(url.pathname)) || (await c.match('/admin'))
    return hit || new Response('<h1 dir="rtl" style="font-family:sans-serif;text-align:center;margin-top:20vh">لا يوجد اتصال بالإنترنت</h1>', { headers: { 'Content-Type': 'text/html; charset=utf-8' } })
  }
}

function withTimeout(p, ms) {
  return new Promise((res, rej) => {
    const t = setTimeout(() => rej(new Error('timeout')), ms)
    p.then((v) => { clearTimeout(t); res(v) }, (e) => { clearTimeout(t); rej(e) })
  })
}

async function networkFirstApi(req) {
  const c = await caches.open(API)
  try {
    const r = await withTimeout(fetch(req), API_TIMEOUT)
    if (r.ok && Date.now() > apiPausedUntil) c.put(req, r.clone())
    else if (r.status === 401) await caches.delete(API)
    return r
  } catch (err) {
    const hit = (await c.match(req)) || (await c.match(req, { ignoreSearch: true }))
    if (!hit) throw err
    const h = new Headers(hit.headers)
    h.set('X-Pr-Cache', 'fallback')
    return new Response(await hit.blob(), { status: 200, headers: h })
  }
}

async function cacheFirst(req) {
  const hit = await caches.match(req)
  if (hit) return hit
  const r = await fetch(req)
  if (r.ok || r.type === 'opaque') (await caches.open(RUNTIME)).put(req, r.clone())
  return r
}

async function staleWhileRevalidate(req) {
  const c = await caches.open(RUNTIME)
  const hit = await c.match(req)
  const net = fetch(req).then((r) => { if (r.ok || r.type === 'opaque') c.put(req, r.clone()); return r }).catch(() => hit)
  return hit || net
}

self.addEventListener('fetch', (e) => {
  const req = e.request
  if (req.method !== 'GET') return
  const url = new URL(req.url)

  if (url.origin === self.location.origin) {
    if (url.pathname.startsWith('/api/')) {
      if (url.pathname === '/api/logout' || url.pathname === '/api/login') return
      return e.respondWith(networkFirstApi(req))
    }
    if (url.pathname.startsWith('/storage/')) return e.respondWith(staleWhileRevalidate(req))
    if (isNav(req)) return e.respondWith(networkFirstPage(req, url))
    if (url.pathname.startsWith('/_nuxt/')) return e.respondWith(cacheFirst(req))
    return e.respondWith(staleWhileRevalidate(req))
  }

  // الخطوط والأيقونات (Google Fonts / jsDelivr)
  if (/fonts\.(googleapis|gstatic)\.com$|cdn\.jsdelivr\.net$/.test(url.hostname)) {
    return e.respondWith(staleWhileRevalidate(req))
  }
})
