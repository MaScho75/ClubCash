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
  './index.html?terminal=1',
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
  console.log('ServiceWorker: Install Event');
  // Sofort aktivieren ohne auf alte Tabs zu warten
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(async cache => {
      console.log('ServiceWorker: Cache geöffnet:', CACHE_NAME);
      // Auflösen der relativen Pfade in absolute URLs basierend auf dem SW-Scope
      const ASSETS = ASSETS_REL.map(p => new URL(p, self.registration.scope).href);
      console.log('ServiceWorker: Versuche', ASSETS.length, 'Dateien zu cachen');
      console.log('ServiceWorker: Scope:', self.registration.scope);
      
      const results = await Promise.allSettled(ASSETS.map(async (url, index) => {
        try {
          console.log(`ServiceWorker: Lade [${index+1}/${ASSETS.length}]:`, url);
          const resp = await fetch(url);
          if (resp && resp.ok) {
            // Speichere unter mehreren Pfad-Varianten für besseres Matching
            const urlObj = new URL(url);
            const urlNoSearch = urlObj.origin + urlObj.pathname;
            
            await cache.put(url, resp.clone());
            await cache.put(urlNoSearch, resp.clone());
            console.log('ServiceWorker: ✓ Gecached:', urlNoSearch);
            return {url: urlNoSearch, status: 'cached'};
          }
          throw new Error(`Bad response for ${url}: ${resp && resp.status}`);
        } catch (err) {
          console.error('ServiceWorker: ✗ Cache error für:', url, err.message);
          return {url, status: 'failed', error: String(err)};
        }
      }));
      
      const failed = results.filter(r => r.status === 'rejected' || (r.value && r.value.status === 'failed'));
      const success = results.filter(r => r.value && r.value.status === 'cached');
      console.log(`ServiceWorker: ✓ ${success.length}/${ASSETS.length} Dateien erfolgreich gecached`);
      if (failed.length) {
        console.warn('ServiceWorker: ✗ Fehler beim Cachen von', failed.length, 'Dateien');
      }
    }).catch(err => {
      console.error('ServiceWorker: Kritischer Fehler beim Öffnen des Caches:', err);
      throw err;
    })
  );
});

// Activate: remove old caches and take control immediately
self.addEventListener('activate', event => {
  console.log('ServiceWorker: Activate Event');
  event.waitUntil(
    caches.keys().then(keys => {
      console.log('ServiceWorker: Gefundene Caches:', keys);
      return Promise.all(
        keys.filter(k => k !== CACHE_NAME).map(k => {
          console.log('ServiceWorker: Lösche alten Cache:', k);
          return caches.delete(k);
        })
      );
    }).then(() => {
      console.log('ServiceWorker: Aktiviert und übernimmt Kontrolle');
      return self.clients.claim();
    })
  );
});

self.addEventListener('fetch', event => {
  // Spezielle Behandlung für Navigation-Requests (PWA-Start, Back/Forward)
  if (event.request.mode === 'navigate') {
    console.log('ServiceWorker: Navigation-Request zu:', event.request.url);
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Erfolgreiche Navigation - Cache aktualisieren
          if (response && response.ok) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request.url, responseClone).catch(() => {});
            }).catch(() => {});
          }
          return response;
        })
        .catch(async () => {
          // Offline - versuche index.html aus Cache zu laden
          console.log('ServiceWorker: Navigation offline, suche index.html im Cache');
          
          // Versuche verschiedene Pfade für index.html
          const paths = [
            './index.html',
            '/kasse/index.html',
            'index.html',
            new URL('./index.html', self.registration.scope).href,
            event.request.url
          ];
          
          for (const path of paths) {
            const cached = await caches.match(path, {ignoreSearch: true});
            if (cached) {
              console.log('ServiceWorker: ✓ index.html gefunden unter:', path);
              return cached;
            }
          }
          
          // Fallback: Suche irgendeine index.html im Cache
          const cache = await caches.open(CACHE_NAME);
          const keys = await cache.keys();
          for (const request of keys) {
            if (request.url.includes('index.html')) {
              console.log('ServiceWorker: ✓ index.html gefunden:', request.url);
              return cache.match(request);
            }
          }
          
          console.error('ServiceWorker: ✗ Keine index.html im Cache gefunden!');
          return new Response('<h1>Offline</h1><p>Die App konnte nicht geladen werden.</p>', {
            headers: {'Content-Type': 'text/html'}
          });
        })
    );
    return;
  }

  // Network-First Strategie für alle anderen Ressourcen:
  // Bei Online: Lade immer frisch vom Server und aktualisiere Cache
  // Bei Offline: Verwende gecachte Version als Fallback
  event.respondWith(
    fetch(event.request)
      .then(networkResponse => {
        // Erfolgreiche Netzwerkantwort - Cache aktualisieren
        if (networkResponse && networkResponse.ok && event.request.method === 'GET') {
          const responseClone = networkResponse.clone();
          caches.open(CACHE_NAME).then(cache => {
            const urlObj = new URL(event.request.url);
            const urlNoSearch = urlObj.origin + urlObj.pathname;
            // Speichere beide Varianten
            cache.put(event.request.url, responseClone.clone()).catch(() => {});
            cache.put(urlNoSearch, responseClone).catch(() => {});
          }).catch(() => {});
        }
        return networkResponse;
      })
      .catch(async () => {
        // Netzwerk fehlgeschlagen (Offline) -> Cache-Fallback
        console.log('ServiceWorker: Netzwerk fehlgeschlagen für:', event.request.url);
        
        // Versuche verschiedene Matching-Strategien
        let cachedResponse = await caches.match(event.request, {ignoreSearch: true});
        
        if (!cachedResponse) {
          // Versuche mit URL ohne Query-Parameter
          const urlObj = new URL(event.request.url);
          const urlNoSearch = urlObj.origin + urlObj.pathname;
          cachedResponse = await caches.match(urlNoSearch, {ignoreSearch: true});
          console.log('ServiceWorker: Versuche ohne Query:', urlNoSearch, cachedResponse ? '✓' : '✗');
        }
        
        if (!cachedResponse) {
          // Versuche mit relativem Pfad
          const urlObj = new URL(event.request.url);
          const relativePath = urlObj.pathname;
          cachedResponse = await caches.match(relativePath, {ignoreSearch: true});
          console.log('ServiceWorker: Versuche relativen Pfad:', relativePath, cachedResponse ? '✓' : '✗');
        }
        
        if (cachedResponse) {
          console.log('ServiceWorker: ✓ Aus Cache geladen:', event.request.url);
          return cachedResponse;
        }
        
        console.warn('ServiceWorker: ✗ Keine gecachte Version gefunden für:', event.request.url);
        
        // Zeige alle gecachten URLs zur Fehlersuche
        const cache = await caches.open(CACHE_NAME);
        const keys = await cache.keys();
        console.log('ServiceWorker: Verfügbare Cache-Keys:', keys.map(r => r.url));
        
        return new Response('Offline - Ressource nicht verfügbar: ' + event.request.url, { 
          status: 503, 
          statusText: 'Service Unavailable' 
        });
      })
  );
});
