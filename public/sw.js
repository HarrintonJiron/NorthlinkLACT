const CACHE_VERSION = 'northlink-lact-v3'
const FIELD_CACHE = 'northlink-field-ready-v1'

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_VERSION).then((cache) => cache.addAll([
    '/favicon.ico',
    '/manifest.webmanifest',
  ])))
  self.skipWaiting()
})

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((key) => ![CACHE_VERSION, FIELD_CACHE].includes(key)).map((key) => caches.delete(key))
    ))
  )
  self.clients.claim()
})

self.addEventListener('message', (event) => {
  if (event.data?.type !== 'CLEAR_PRIVATE_DATA') {
    return
  }

  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((key) => key.startsWith('northlink-')).map((key) => caches.delete(key))
    ))
  )
})

const isStaticAsset = (request) => {
  const url = new URL(request.url)

  return url.pathname.startsWith('/build/')
    || url.pathname === '/favicon.ico'
    || url.pathname === '/manifest.webmanifest'
}

self.addEventListener('fetch', (event) => {
  const request = event.request

  if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
    return
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .catch(async () => {
          const fieldCache = await caches.open(FIELD_CACHE)
          const pathname = new URL(request.url).pathname

          if (/^\/sumni\/\d+\/?$/.test(pathname)) {
            const exactMatch = await fieldCache.match(request, { ignoreSearch: true })
            if (exactMatch) return exactMatch
          }

          return new Response('Esta ruta no fue descargada. Conéctate y usa “Preparar jornada offline”.', {
            status: 503,
            headers: { 'Content-Type': 'text/plain; charset=utf-8' },
          })
        })
    )
    return
  }

  if (!isStaticAsset(request)) {
    event.respondWith(fetch(request))
    return
  }

  event.respondWith(
    caches.match(request).then((cached) => cached || fetch(request).then((response) => {
      if (response.ok) {
        const copy = response.clone()
        caches.open(CACHE_VERSION).then((cache) => cache.put(request, copy))
      }

      return response
    }))
  )
})
