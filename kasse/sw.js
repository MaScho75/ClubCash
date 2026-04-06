/*
 * This file is part of ClubCash.
 *
 * ClubCash is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * ClubCash is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with ClubCash. If not, see <https://www.gnu.org/licenses/>.
 */

const CACHE_NAME = 'clubcash-v1.4.0';
const ASSETS = [
  './',
  './index.html',
  './login.php',
  './config-public.php',
  './style-kasse.css',
  '../farben.css',
  './daten.php?file=produkte.json',
  './daten.php?file=kunden.json',
  './daten.php?file=externe.json',
  '../grafik/carlito-v3-latin-regular.woff2',
  './lib/jquery-3.6.0.min.js',
  '../grafik/ClubCashLogo-gelbblauschwarz.svg',
  '../grafik/ClubCashLogo-gelbblauweiss.svg'
];

// Installation: Cache needed files (fault tolerant)
self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(async cache => {
      const absoluteAssets = ASSETS.map(asset => new URL(asset, self.registration.scope).href);
      await Promise.allSettled(
        absoluteAssets.map(async assetUrl => {
          try {
            const request = new Request(assetUrl, { cache: 'reload' });
            const response = await fetch(request);
            if (!response || !response.ok) {
              throw new Error(`HTTP ${response ? response.status : 'no-response'}`);
            }
            await cache.put(request, response.clone());
          } catch (error) {
            console.warn('[SW] Precache failed for asset:', assetUrl, error);
          }
        })
      );
    })
  );
});

// Activation: delete old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch: Network First, then Cache
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    (async () => {
      // Navigations-Requests: Offline-Reload robust machen
      if (event.request.mode === 'navigate') {
        try {
          const networkResponse = await fetch(event.request);
          if (networkResponse && networkResponse.ok && !networkResponse.redirected) {
            const cache = await caches.open(CACHE_NAME);
            await cache.put(event.request, networkResponse.clone());
          }
          return networkResponse;
        } catch {
          const indexHtmlUrl = new URL('./index.html', self.registration.scope).href;
          const cachedIndexHtml = await caches.match(indexHtmlUrl);
          if (cachedIndexHtml) return cachedIndexHtml;

          const cachedNavigation = await caches.match(event.request);
          if (cachedNavigation) return cachedNavigation;

          const rootUrl = new URL('./', self.registration.scope).href;
          const cachedRoot = await caches.match(rootUrl);
          if (cachedRoot) return cachedRoot;

          return new Response('Offline and no cached page available.', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: { 'Content-Type': 'text/plain; charset=UTF-8' }
          });
        }
      }

      // Sonstige GET-Requests
      try {
        const response = await fetch(event.request);
        if (response && response.ok) {
          const cache = await caches.open(CACHE_NAME);
          await cache.put(event.request, response.clone());
        }
        return response;
      } catch {
        const cached = await caches.match(event.request);
        if (cached) return cached;

        return new Response('Offline and no cached resource available.', {
          status: 503,
          statusText: 'Service Unavailable',
          headers: { 'Content-Type': 'text/plain; charset=UTF-8' }
        });
      }
    })()
  );
});
