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

const CACHE_NAME = 'clubcash-v13'; // Cache-Version anpassen bei Updates
const urlsToCache = [
  `/kasse/`,
  `/kasse/index.html`,
  `/style.css`,
  `/farben.css`,
  `/config.js`,
  `/grafik/ClubCashLogo-gelbblauschwarz.svg`,
  `/grafik/ClubCashLogo-gelbblauweiss.svg`,
  `/daten/produkte.json`,
  `/daten/kunden.json`
];

// Hilfsfunktion: URL ohne Query-Parameter zurückgeben
function stripQueryString(url) {
  const u = new URL(url);
  u.search = '';
  return u.toString();
}

// Installation: Cache mit wichtigen Dateien füllen
self.addEventListener('install', event => {
  console.log('[ServiceWorker] Installieren und Cache füllen');
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return Promise.all(
        urlsToCache.map(url =>
          fetch(url).then(response => {
            if (!response.ok) throw new Error(`Fehler beim Laden von ${url}: ${response.statusText}`);
            return cache.put(url, response);
          }).catch(err => {
            console.warn(`[ServiceWorker] Konnte ${url} nicht cachen:`, err);
          })
        )
      );
    })
  );
});

// Aktivierung: Alte Caches löschen
self.addEventListener('activate', event => {
  console.log('[ServiceWorker] Aktivieren und alte Caches löschen');
  event.waitUntil(
    caches.keys().then(cacheNames =>
      Promise.all(
        cacheNames.map(name => {
          if (name !== CACHE_NAME) {
            console.log('[ServiceWorker] Lösche alten Cache:', name);
            return caches.delete(name);
          }
        })
      )
    ).then(() => self.clients.claim())
  );
});

// Fetch-Event: Network-first Strategie
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;

  const cacheUrl = stripQueryString(event.request.url);

  event.respondWith(
    caches.open(CACHE_NAME).then(cache => {
      return fetch(event.request).then(networkResponse => {
        // Bei erfolgreichem Netzwerk-Response Cache aktualisieren
        if (networkResponse && networkResponse.ok) {
          cache.put(cacheUrl, networkResponse.clone());
        }
        return networkResponse;
      }).catch(() => {
        // Netzwerk fehlgeschlagen -> Cache als Fallback
        return cache.match(cacheUrl);
      });
    })
  );
});
