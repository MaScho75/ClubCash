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

const CACHE_NAME = 'clubcash-v1';
// Relative asset list — wird zur Install-Zeit in absolute URLs aufgelöst
const ASSETS_REL = [
  './',
  './index.html',
  '../style.css',
  '../farben.css',
  '../daten/config.json',
  '../daten/produkte.json',
  '../daten/kunden.json',
  '../daten/externe.json',
  './fonts/carlito-v3-latin-regular.woff2',
  './lib/jquery-3.6.0.min.js',
  '../grafik/ClubCashLogo-gelbblauschwarz.svg',
  '../grafik/ClubCashLogo-gelbblauweiss.svg'
];

self.addEventListener('install', event => {
  // During install try to fetch and cache all assets, but tolerate individual failures
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(async cache => {
      // Auflösen der relativen Pfade in absolute URLs basierend auf dem SW-Scope
      const ASSETS = ASSETS_REL.map(p => new URL(p, self.registration.scope).href);
      const results = await Promise.allSettled(ASSETS.map(async url => {
        try {
          const req = new Request(url, {cache: 'no-store'});
          const resp = await fetch(req);
          if (resp && resp.ok) {
            await cache.put(url, resp.clone());
            return {url, status: 'cached'};
          }
          throw new Error(`Bad response for ${url}: ${resp && resp.status}`);
        } catch (err) {
          console.log('Cache error for:', url, err);
          return {url, status: 'failed', error: String(err)};
        }
      }));
      // Optionally log summary
      const failed = results.filter(r => r.status === 'rejected' || (r.value && r.value.status === 'failed'));
      if (failed.length) console.log('Some assets failed to cache during install:', failed.map(f => f.value ? f.value.url : f.reason));
    })
  );
});

// Activate: remove old caches and take control immediately
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  // Wenn es sich um die config.json handelt: versuche Netzwerk zuerst, dann Cache
  if (event.request.url.includes('config.json')) {
    event.respondWith(
      fetch(event.request, {
        cache: 'no-store',
        headers: {
          'Cache-Control': 'no-cache, must-revalidate',
          'Pragma': 'no-cache'
        }
      })
      .then(response => {
        // Erfolgreiche Antwort vom Server - Cache aktualisieren (ohne Query)
        const responseClone = response.clone();
        caches.open(CACHE_NAME).then(cache => {
          // Speichere unter Pfad ohne Such-Parameter, damit ignoreSearch funktioniert
          const urlNoSearch = new URL(event.request.url).origin + new URL(event.request.url).pathname;
          cache.put(urlNoSearch, responseClone).catch(() => {});
        });
        return response;
      })
      .catch(() => {
        // Bei Offline-Zugriff oder Fehler - aus Cache laden (ignoreSearch)
        return caches.match(event.request, {ignoreSearch: true});
      })
    );
    return;
  }

  // Standard Cache-Strategie: zuerst Cache (ignoreSearch), dann Netzwerk, bei Erfolg Cache aktualisieren
  // Navigation-Fallback: falls der Browser eine Seite anfragt und offline ist,
  // liefere die gecachte index.html damit die App sichtbar bleibt.
  if (event.request.mode === 'navigate') {
    event.respondWith(
      caches.match('./index.html', {ignoreSearch: true}).then(cachedNav => {
        if (cachedNav) return cachedNav;
        return fetch(event.request).catch(() => caches.match('./index.html', {ignoreSearch: true}));
      })
    );
    return;
  }
  // Wenn wir online sind: Network-first für gleiche Origin (immer frische Dateien)
  try {
    const requestUrl = new URL(event.request.url);
    const sameOrigin = requestUrl.origin === self.location.origin;

    if (event.request.method === 'GET' && sameOrigin && self.navigator && self.navigator.onLine) {
      event.respondWith(
        fetch(event.request).then(networkResponse => {
          if (networkResponse && networkResponse.ok) {
            const responseClone = networkResponse.clone();
            caches.open(CACHE_NAME).then(cache => {
              const urlNoSearch = requestUrl.origin + requestUrl.pathname;
              cache.put(urlNoSearch, responseClone).catch(() => {});
            });
          }
          return networkResponse;
        }).catch(() => {
          // Netzwerk fehlgeschlagen -> aus Cache (ignoreSearch)
          return caches.match(event.request, {ignoreSearch: true});
        })
      );
      return;
    }
  } catch (err) {
    // Falls URL Verarbeitung fehlschlägt, fallbacks unten nutzen
    console.log('SW fetch URL parse error', err);
  }

  // Default: Cache-first (ignoreSearch) für andere Fälle
  event.respondWith(
    caches.match(event.request, {ignoreSearch: true}).then(cachedResponse => {
      if (cachedResponse) return cachedResponse;
      return fetch(event.request).then(networkResponse => {
        if (event.request.method === 'GET' && networkResponse && networkResponse.ok) {
          const responseClone = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => {
            const urlNoSearch = new URL(event.request.url).origin + new URL(event.request.url).pathname;
            cache.put(urlNoSearch, responseClone).catch(() => {});
          }).catch(() => {});
        }
        return networkResponse;
      }).catch(() => caches.match(event.request, {ignoreSearch: true}));
    })
  );
});
