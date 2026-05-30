const CACHE_VERSION = 'v1.0.0';
const CACHE_NAME = `jilaoTV-${CACHE_VERSION}`;

const STATIC_CACHE = [
    '/',
    '/index.html',
    '/style.css',
    '/logo.png',
    '/favicon.ico',
    '/manifest.json'
];

const CDN_RESOURCES = [
    'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/hls.js/1.1.5/hls.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/dplayer/1.26.0/DPlayer.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/vue/3.2.47/vue.global.prod.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/js/bootstrap.bundle.min.js'
];

self.addEventListener('install', (event) => {
    console.log('[SW] 安装中...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] 缓存静态资源');
                return cache.addAll(STATIC_CACHE);
            })
            .then(() => {
                console.log('[SW] 缓存CDN资源');
                return caches.open(CACHE_NAME).then((cache) => {
                    return Promise.all(
                        CDN_RESOURCES.map((url) => {
                            return fetch(url, { mode: 'cors' })
                                .then((response) => {
                                    if (response.ok) {
                                        return cache.put(url, response);
                                    }
                                })
                                .catch((err) => {
                                    console.warn('[SW] CDN资源缓存失败:', url, err);
                                });
                        })
                    );
                });
            })
            .then(() => {
                console.log('[SW] 安装完成，跳过等待');
                return self.skipWaiting();
            })
    );
});

self.addEventListener('activate', (event) => {
    console.log('[SW] 激活中...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('[SW] 删除旧缓存:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[SW] 激活完成，控制所有客户端');
                return self.clients.claim();
            })
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    if (request.method !== 'GET') {
        return;
    }
    
    if (url.pathname.startsWith('/api/tmdbimg/')) {
        event.respondWith(handleImageRequest(request));
        return;
    }
    
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(handleAPIRequest(request));
        return;
    }
    
    if (url.origin !== location.origin) {
        event.respondWith(handleCDNRequest(request));
        return;
    }
    
    event.respondWith(handleStaticRequest(request));
});

async function handleStaticRequest(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
        console.log('[SW] 使用缓存:', request.url);
        
        fetchAndCache(request, cache);
        
        return cachedResponse;
    }
    
    console.log('[SW] 网络请求:', request.url);
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.error('[SW] 网络请求失败:', request.url, error);
        
        if (request.destination === 'document') {
            return cache.match('/index.html');
        }
        
        return new Response('离线状态', { status: 503, statusText: 'Service Unavailable' });
    }
}

async function handleImageRequest(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
        console.log('[SW] 图片缓存命中:', request.url);
        return cachedResponse;
    }
    
    console.log('[SW] 图片网络请求:', request.url);
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
            console.log('[SW] 图片已缓存:', request.url);
        }
        
        return networkResponse;
    } catch (error) {
        console.error('[SW] 图片请求失败:', request.url, error);
        
        return new Response('', {
            status: 404,
            statusText: 'Image Not Found'
        });
    }
}

async function handleCDNRequest(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
        console.log('[SW] CDN缓存命中:', request.url);
        return cachedResponse;
    }
    
    console.log('[SW] CDN网络请求:', request.url);
    try {
        const networkResponse = await fetch(request, { mode: 'cors' });
        
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
            console.log('[SW] CDN资源已缓存:', request.url);
        }
        
        return networkResponse;
    } catch (error) {
        console.error('[SW] CDN请求失败:', request.url, error);
        return new Response('CDN资源加载失败', { status: 503 });
    }
}

async function handleAPIRequest(request) {
    console.log('[SW] API请求:', request.url);
    
    const cache = await caches.open(CACHE_NAME);
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            console.log('[SW] API请求成功，缓存响应:', request.url);
            cache.put(request, networkResponse.clone());
            return networkResponse;
        }
        
        throw new Error('API响应异常');
    } catch (error) {
        console.warn('[SW] API请求失败，尝试使用缓存:', request.url);
        
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            console.log('[SW] 使用API缓存:', request.url);
            return cachedResponse;
        }
        
        return new Response(
            JSON.stringify({ error: true, message: '网络连接失败，请检查网络设置' }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

async function fetchAndCache(request, cache) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
            console.log('[SW] 后台更新缓存:', request.url);
        }
    } catch (error) {
        console.warn('[SW] 后台更新失败:', request.url);
    }
}

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        caches.delete(CACHE_NAME).then(() => {
            console.log('[SW] 缓存已清除');
        });
    }
});

console.log('[SW] Service Worker 已加载，版本:', CACHE_VERSION);
