/* Minimalny Service Worker dla instalowalności PWA.
 * Celowo nie cache'uje HTML/API (login + dane prywatne).
 */

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  // Bez cache – przepuszczamy requesty normalnie.
});
