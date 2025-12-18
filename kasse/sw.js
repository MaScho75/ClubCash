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
const ASSETS = [
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
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return Promise.all(
          ASSETS.map(url => {
            return fetch(url)
              .then(response => {
                if (!response.ok) {
                  throw new Error(`Failed to fetch ${url}`);
                }
                return cache.put(url, response);
              })
              .catch(err => {
                console.log('Cache error for:', url, err);
              });
          })
        );
      })
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
  event.respondWith(
    caches.match(event.request, {ignoreSearch: true}).then(cachedResponse => {
      if (cachedResponse) return cachedResponse;
      return fetch(event.request).then(networkResponse => {
        // Bei erfolgreicher Antwort im Hintergrund in den Cache legen (ohne Query-String)
        if (event.request.method === 'GET' && networkResponse && networkResponse.ok) {
          const responseClone = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => {
            const urlNoSearch = new URL(event.request.url).origin + new URL(event.request.url).pathname;
            cache.put(urlNoSearch, responseClone).catch(() => {});
          });
        }
        return networkResponse;
      }).catch(() => {
        // Netzwerk fehlgeschlagen -> versuche Cache erneut (ignoreSearch)
        return caches.match(event.request, {ignoreSearch: true});
      });
    })
  );
});
