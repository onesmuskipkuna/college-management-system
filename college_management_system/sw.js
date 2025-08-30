/**
 * Service Worker for College Management System
 * Provides offline functionality, caching, and push notifications
 */

const CACHE_NAME = 'cms-v1.0.0';
const OFFLINE_URL = '/offline.html';

// Files to cache for offline functionality
const CACHE_URLS = [
  '/',
  '/index.php',
  '/login.php',
  '/student/mobile_app.php',
  '/student/dashboard.php',
  '/student/assignments.php',
  '/student/progress.php',
  '/student/fee_statement.php',
  '/library/dashboard.php',
  '/css/styles.css',
  '/js/validations.js',
  '/manifest.json',
  OFFLINE_URL
];

// API endpoints to cache responses
const API_CACHE_URLS = [
  '/api/chatbot.php',
  '/api/get_fee_structure.php'
];

// Install event - cache essential files
self.addEventListener('install', event => {
  console.log('Service Worker installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Caching essential files...');
        return cache.addAll(CACHE_URLS);
      })
      .then(() => {
        console.log('Service Worker installed successfully');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('Service Worker installation failed:', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker activating...');
  
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME) {
              console.log('Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('Service Worker activated');
        return self.clients.claim();
      })
  );
});

// Fetch event - implement caching strategies
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }
  
  // Skip chrome-extension and other non-http requests
  if (!url.protocol.startsWith('http')) {
    return;
  }
  
  // Handle different types of requests with appropriate strategies
  if (url.pathname.startsWith('/api/')) {
    // API requests - Network First with cache fallback
    event.respondWith(networkFirstStrategy(request));
  } else if (url.pathname.endsWith('.php')) {
    // PHP pages - Stale While Revalidate
    event.respondWith(staleWhileRevalidateStrategy(request));
  } else if (isStaticAsset(url.pathname)) {
    // Static assets - Cache First
    event.respondWith(cacheFirstStrategy(request));
  } else {
    // Default - Network First
    event.respondWith(networkFirstStrategy(request));
  }
});

// Network First Strategy - Try network, fallback to cache
async function networkFirstStrategy(request) {
  try {
    const networkResponse = await fetch(request);
    
    // Cache successful responses
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Network failed, trying cache:', request.url);
    
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline page for navigation requests
    if (request.mode === 'navigate') {
      return caches.match(OFFLINE_URL);
    }
    
    // Return a basic offline response for other requests
    return new Response('Offline', {
      status: 503,
      statusText: 'Service Unavailable',
      headers: { 'Content-Type': 'text/plain' }
    });
  }
}

// Cache First Strategy - Try cache, fallback to network
async function cacheFirstStrategy(request) {
  const cachedResponse = await caches.match(request);
  
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Cache and network failed for:', request.url);
    return new Response('Resource not available offline', {
      status: 503,
      statusText: 'Service Unavailable'
    });
  }
}

// Stale While Revalidate Strategy - Return cache immediately, update in background
async function staleWhileRevalidateStrategy(request) {
  const cache = await caches.open(CACHE_NAME);
  const cachedResponse = await cache.match(request);
  
  // Fetch from network in background
  const networkResponsePromise = fetch(request)
    .then(networkResponse => {
      if (networkResponse.ok) {
        cache.put(request, networkResponse.clone());
      }
      return networkResponse;
    })
    .catch(error => {
      console.log('Background fetch failed:', error);
    });
  
  // Return cached version immediately if available
  if (cachedResponse) {
    return cachedResponse;
  }
  
  // If no cache, wait for network
  try {
    return await networkResponsePromise;
  } catch (error) {
    return caches.match(OFFLINE_URL);
  }
}

// Check if URL is a static asset
function isStaticAsset(pathname) {
  const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf'];
  return staticExtensions.some(ext => pathname.endsWith(ext));
}

// Background Sync for offline actions
self.addEventListener('sync', event => {
  console.log('Background sync triggered:', event.tag);
  
  if (event.tag === 'background-sync-assignments') {
    event.waitUntil(syncAssignments());
  } else if (event.tag === 'background-sync-fees') {
    event.waitUntil(syncFeePayments());
  } else if (event.tag === 'background-sync-chatbot') {
    event.waitUntil(syncChatbotMessages());
  }
});

// Sync offline assignment submissions
async function syncAssignments() {
  try {
    const cache = await caches.open(CACHE_NAME);
    const offlineSubmissions = await cache.match('/offline-submissions');
    
    if (offlineSubmissions) {
      const submissions = await offlineSubmissions.json();
      
      for (const submission of submissions) {
        try {
          const response = await fetch('/api/submit-assignment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(submission)
          });
          
          if (response.ok) {
            console.log('Assignment submission synced:', submission.id);
          }
        } catch (error) {
          console.error('Failed to sync assignment:', error);
        }
      }
      
      // Clear offline submissions after sync
      await cache.delete('/offline-submissions');
    }
  } catch (error) {
    console.error('Assignment sync failed:', error);
  }
}

// Sync offline fee payments
async function syncFeePayments() {
  try {
    const cache = await caches.open(CACHE_NAME);
    const offlinePayments = await cache.match('/offline-payments');
    
    if (offlinePayments) {
      const payments = await offlinePayments.json();
      
      for (const payment of payments) {
        try {
          const response = await fetch('/api/process-payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payment)
          });
          
          if (response.ok) {
            console.log('Payment synced:', payment.id);
          }
        } catch (error) {
          console.error('Failed to sync payment:', error);
        }
      }
      
      await cache.delete('/offline-payments');
    }
  } catch (error) {
    console.error('Payment sync failed:', error);
  }
}

// Sync offline chatbot messages
async function syncChatbotMessages() {
  try {
    const cache = await caches.open(CACHE_NAME);
    const offlineMessages = await cache.match('/offline-chatbot-messages');
    
    if (offlineMessages) {
      const messages = await offlineMessages.json();
      
      for (const message of messages) {
        try {
          const response = await fetch('/api/chatbot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(message)
          });
          
          if (response.ok) {
            console.log('Chatbot message synced:', message.id);
          }
        } catch (error) {
          console.error('Failed to sync chatbot message:', error);
        }
      }
      
      await cache.delete('/offline-chatbot-messages');
    }
  } catch (error) {
    console.error('Chatbot sync failed:', error);
  }
}

// Push notification handling
self.addEventListener('push', event => {
  console.log('Push notification received:', event);
  
  let notificationData = {
    title: 'College Management System',
    body: 'You have a new notification',
    icon: '/icons/icon-192x192.png',
    badge: '/icons/badge-72x72.png',
    tag: 'cms-notification',
    requireInteraction: false,
    actions: [
      {
        action: 'view',
        title: 'View',
        icon: '/icons/view-icon.png'
      },
      {
        action: 'dismiss',
        title: 'Dismiss',
        icon: '/icons/dismiss-icon.png'
      }
    ]
  };
  
  if (event.data) {
    try {
      const data = event.data.json();
      notificationData = { ...notificationData, ...data };
    } catch (error) {
      console.error('Error parsing push data:', error);
      notificationData.body = event.data.text();
    }
  }
  
  event.waitUntil(
    self.registration.showNotification(notificationData.title, notificationData)
  );
});

// Notification click handling
self.addEventListener('notificationclick', event => {
  console.log('Notification clicked:', event);
  
  event.notification.close();
  
  const action = event.action;
  const notificationData = event.notification.data || {};
  
  if (action === 'view' || !action) {
    // Open the app or navigate to specific page
    const urlToOpen = notificationData.url || '/student/mobile_app.php';
    
    event.waitUntil(
      clients.matchAll({ type: 'window', includeUncontrolled: true })
        .then(clientList => {
          // Check if app is already open
          for (const client of clientList) {
            if (client.url.includes(urlToOpen) && 'focus' in client) {
              return client.focus();
            }
          }
          
          // Open new window if app is not open
          if (clients.openWindow) {
            return clients.openWindow(urlToOpen);
          }
        })
    );
  } else if (action === 'dismiss') {
    // Just close the notification (already handled above)
    console.log('Notification dismissed');
  }
});

// Notification close handling
self.addEventListener('notificationclose', event => {
  console.log('Notification closed:', event);
  
  // Track notification dismissal analytics if needed
  // This could be useful for understanding user engagement
});

// Message handling from main thread
self.addEventListener('message', event => {
  console.log('Service Worker received message:', event.data);
  
  if (event.data && event.data.type) {
    switch (event.data.type) {
      case 'SKIP_WAITING':
        self.skipWaiting();
        break;
        
      case 'CACHE_URLS':
        event.waitUntil(
          caches.open(CACHE_NAME)
            .then(cache => cache.addAll(event.data.urls))
        );
        break;
        
      case 'CLEAR_CACHE':
        event.waitUntil(
          caches.delete(CACHE_NAME)
            .then(() => caches.open(CACHE_NAME))
        );
        break;
        
      case 'GET_CACHE_SIZE':
        event.waitUntil(
          getCacheSize().then(size => {
            event.ports[0].postMessage({ cacheSize: size });
          })
        );
        break;
    }
  }
});

// Get cache size for diagnostics
async function getCacheSize() {
  const cache = await caches.open(CACHE_NAME);
  const keys = await cache.keys();
  let totalSize = 0;
  
  for (const key of keys) {
    const response = await cache.match(key);
    if (response) {
      const blob = await response.blob();
      totalSize += blob.size;
    }
  }
  
  return totalSize;
}

// Periodic background sync for data updates
self.addEventListener('periodicsync', event => {
  console.log('Periodic sync triggered:', event.tag);
  
  if (event.tag === 'update-student-data') {
    event.waitUntil(updateStudentData());
  } else if (event.tag === 'sync-notifications') {
    event.waitUntil(syncNotifications());
  }
});

// Update student data in background
async function updateStudentData() {
  try {
    // Fetch latest student data
    const response = await fetch('/api/student-data.php');
    if (response.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put('/api/student-data.php', response.clone());
      console.log('Student data updated in background');
    }
  } catch (error) {
    console.error('Background student data update failed:', error);
  }
}

// Sync notifications in background
async function syncNotifications() {
  try {
    const response = await fetch('/api/notifications.php');
    if (response.ok) {
      const notifications = await response.json();
      
      // Show new notifications
      for (const notification of notifications.new || []) {
        await self.registration.showNotification(notification.title, {
          body: notification.message,
          icon: '/icons/icon-192x192.png',
          tag: `notification-${notification.id}`,
          data: notification
        });
      }
      
      console.log('Notifications synced in background');
    }
  } catch (error) {
    console.error('Background notification sync failed:', error);
  }
}

// Error handling
self.addEventListener('error', event => {
  console.error('Service Worker error:', event.error);
});

self.addEventListener('unhandledrejection', event => {
  console.error('Service Worker unhandled rejection:', event.reason);
});

console.log('Service Worker script loaded');
