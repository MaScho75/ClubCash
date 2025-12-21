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
          const resp = await fetch(url);
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
  // Network-First Strategie für alle Ressourcen:
  // Bei Online: Lade immer frisch vom Server und aktualisiere Cache
  // Bei Offline: Verwende gecachte Version als Fallback
  event.respondWith(
    fetch(event.request)
      .then(networkResponse => {
        // Erfolgreiche Netzwerkantwort - Cache aktualisieren
        if (networkResponse && networkResponse.ok && event.request.method === 'GET') {
          const responseClone = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => {
            const urlNoSearch = new URL(event.request.url).origin + new URL(event.request.url).pathname;
            cache.put(urlNoSearch, responseClone).catch(() => {});
          }).catch(() => {});
        }
        return networkResponse;
      })
      .catch(() => {
        // Netzwerk fehlgeschlagen (Offline) -> Cache-Fallback
        return caches.match(event.request, {ignoreSearch: true});
      })
  );
});
