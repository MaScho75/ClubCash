const CACHE_NAME = 'clubcash-tanken-v2';
const ASSETS = [
  './tanken.php',
  './style-portal.css',
  './farben.css',
  './tanken-manifest.json',
  './grafik/ClubCashLogo-gelbblauschwarz.svg',
  './grafik/tanken-app-icon-192.png',
  './grafik/tanken-app-icon-512.png'
];

self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
  );
});

self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames =>
      Promise.all(
        cacheNames
          .filter(cacheName => cacheName !== CACHE_NAME)
          .map(cacheName => caches.delete(cacheName))
      )
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') {
    return;
  }

  const requestUrl = new URL(event.request.url);
  if (requestUrl.origin !== self.location.origin) {
    return;
  }

  const networkRequest = new Request(event.request, { cache: 'no-store' });

  event.respondWith(
    fetch(networkRequest).then(networkResponse => {
      if (networkResponse && networkResponse.ok) {
        const responseClone = networkResponse.clone();
        caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
      }

      return networkResponse;
    }).catch(() =>
      caches.match(event.request).then(cachedResponse => {
        if (cachedResponse) {
          return cachedResponse;
        }

        if (event.request.mode === 'navigate') {
          return caches.match('./tanken.php');
        }

        throw new Error('Netzwerk und Cache nicht verfügbar.');
      })
    )
  );
});
