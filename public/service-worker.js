const CACHE_NAME = 'ck-quiz-me-v1';
const urlsToCache = [
    '/',
    '/home',
    '/login',
    '/register',
    '/css/app.css',
    '/js/app.js',
    // Add paths to other critical assets you want to cache for offline use
    // For example, images:
    '/icons/quizme.png',
    '/icons/coachsports.png',
    '/icons/email.png',
    '/icons/infotext.png',
    '/icons/proofreader.png',
    '/icons/quote.png',
    '/icons/realworld.png',
    '/icons/studyhabits.png',
    '/icons/summarizer.png',
    '/icons/teacherjoke.png',
    '/icons/text leveler.png',
    '/icons/text rewritter.png',
    '/icons/text scaff.png',
    '/icons/text translator.png',
    '/icons/tonguetwister.png',
    '/icons/ty.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Cache hit - return response
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});

self.addEventListener('activate', (event) => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
}); 