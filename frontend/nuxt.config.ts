import { readdirSync, readFileSync, writeFileSync, existsSync } from 'node:fs'
import { join } from 'node:path'
import { createHash } from 'node:crypto'

// يجمع ملفات البناء (_nuxt) ويحقنها في sw.js لتُخزَّن مسبقاً — والنسخة تتغيّر مع كل بناء فيتحدّث الـ SW تلقائياً
function injectPrecache(publicDir: string) {
  const sw = join(publicDir, 'sw.js')
  if (!existsSync(sw)) return
  const files: string[] = []
  const walk = (dir: string, rel: string) => {
    for (const e of readdirSync(dir, { withFileTypes: true })) {
      if (e.isDirectory()) walk(join(dir, e.name), rel + e.name + '/')
      else if (!e.name.endsWith('.map')) files.push(rel + e.name)
    }
  }
  if (existsSync(join(publicDir, '_nuxt'))) walk(join(publicDir, '_nuxt'), '/_nuxt/')
  files.sort()
  const version = createHash('sha1').update(files.join('|')).digest('hex').slice(0, 12)
  const src = readFileSync(sw, 'utf8').replace(/^self\.__PRECACHE__ = .*\n/, '')
  writeFileSync(sw, `self.__PRECACHE__ = ${JSON.stringify({ version, files })}\n` + src)
}

export default defineNuxtConfig({
  devtools: { enabled: false },
  css: ['~/assets/css/main.css'],
  // لوحات التحكم تطبيق SPA: نفس الصدفة لكل المسارات فتفتح دون اتصال من الـ Service Worker
  routeRules: {
    '/admin': { ssr: false },
    '/admin/**': { ssr: false },
    '/super': { ssr: false },
    '/super/**': { ssr: false },
    '/sw.js': { headers: { 'Cache-Control': 'no-cache', 'Service-Worker-Allowed': '/' } },
    '/manifest.webmanifest': { headers: { 'Content-Type': 'application/manifest+json' } },
  },
  hooks: {
    'nitro:build:public-assets'(nitro) {
      injectPrecache(nitro.options.output.publicDir)
    },
  },
  app: {
    head: {
      title: 'موبايل فيكس — صيانة الجوالات',
      htmlAttrs: { lang: 'ar', dir: 'rtl' },
      meta: [
        { name: 'theme-color', content: '#185FA5' },
        { name: 'mobile-web-app-capable', content: 'yes' },
        { name: 'apple-mobile-web-app-capable', content: 'yes' },
        { name: 'apple-mobile-web-app-title', content: 'موبايل فيكس' },
      ],
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
        { rel: 'manifest', href: '/manifest.webmanifest' },
        { rel: 'apple-touch-icon', href: '/apple-touch-icon.png' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap' },
        { rel: 'stylesheet', href: 'https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css' },
      ],
    },
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8097/api',
    },
  },
})
