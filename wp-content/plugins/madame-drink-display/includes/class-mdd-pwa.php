<?php
if (!defined('ABSPATH')) exit;

/**
 * PWA (Progressive Web App) support for tablet kiosk mode.
 * Allows the tablet display to be installed as a standalone app,
 * hiding the browser chrome for a true kiosk experience.
 */
class MDD_PWA {

    /**
     * Generate manifest.json content
     */
    public static function get_manifest($device = null) {
        $name = get_bloginfo('name') . ' — Drinks';
        $event_mode = get_option('mdd_event_mode', 0);
        $event_name = get_option('mdd_event_name', '');

        if ($event_mode && $event_name) {
            $name = $event_name . ' — Drinks';
        }

        $primary = get_option('mdd_primary_color', '#C8962E');
        $bg = get_option('mdd_secondary_color', '#1A1A2E');

        $manifest = [
            'name'             => $name,
            'short_name'       => 'Drinks',
            'description'      => 'Cardápio Digital de Drinks',
            'start_url'        => '/display/tablet/' . ($device ? '?token=' . $device->token : ''),
            'display'          => 'standalone',
            'orientation'      => 'any',
            'theme_color'      => $bg,
            'background_color' => $bg,
            'categories'       => ['food'],
            'lang'             => 'pt-BR',
            'icons'            => [],
        ];

        // Try to use logo as icon
        $logo_id = get_option('mdd_establishment_logo', '');
        if ($logo_id) {
            $sizes = [192, 512];
            foreach ($sizes as $size) {
                $url = wp_get_attachment_image_url($logo_id, [$size, $size]);
                if ($url) {
                    $manifest['icons'][] = [
                        'src'   => $url,
                        'sizes' => "{$size}x{$size}",
                        'type'  => 'image/png',
                    ];
                }
            }
        }

        // Fallback icon if no logo
        if (empty($manifest['icons'])) {
            $manifest['icons'][] = [
                'src'   => MDD_PLUGIN_URL . 'assets/img/icon-192.png',
                'sizes' => '192x192',
                'type'  => 'image/png',
            ];
        }

        return $manifest;
    }

    /**
     * Output manifest link tag
     */
    public static function render_manifest_link($device = null) {
        $token = $device ? $device->token : '';
        $manifest_url = rest_url('mdd/v1/manifest') . ($token ? '?token=' . $token : '');
        echo '<link rel="manifest" href="' . esc_url($manifest_url) . '">';
    }

    /**
     * Output meta tags for standalone mode
     */
    public static function render_meta_tags() {
        $bg = get_option('mdd_secondary_color', '#1A1A2E');
        echo '<meta name="apple-mobile-web-app-capable" content="yes">';
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
        echo '<meta name="theme-color" content="' . esc_attr($bg) . '">';
        echo '<meta name="mobile-web-app-capable" content="yes">';
    }

    /**
     * Generate minimal service worker for offline caching
     */
    public static function get_service_worker() {
        $version = MDD_VERSION;
        return <<<JS
const CACHE_NAME = 'mdd-display-v{$version}';
const OFFLINE_URL = '/display/tablet/';

self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // Cache images aggressively
    if (event.request.destination === 'image') {
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) return cached;
                return fetch(event.request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                }).catch(() => new Response('', {status: 404}));
            })
        );
        return;
    }

    // Network first for API calls
    if (url.pathname.includes('/wp-json/mdd/')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    return response;
                })
                .catch(() => caches.match(event.request))
        );
        return;
    }

    // Cache first for static assets
    event.respondWith(
        caches.match(event.request).then(cached => cached || fetch(event.request))
    );
});
JS;
    }

    /**
     * Register REST endpoint for manifest
     */
    public static function register_routes() {
        register_rest_route('mdd/v1', '/manifest', [
            'methods'  => 'GET',
            'callback' => function($request) {
                $token = $request->get_param('token');
                $device = null;
                if ($token) {
                    $device = MDD_Token_Manager::validate_token($token);
                }
                $manifest = self::get_manifest($device);
                return new WP_REST_Response($manifest, 200, [
                    'Content-Type' => 'application/manifest+json',
                ]);
            },
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('mdd/v1', '/sw.js', [
            'methods'  => 'GET',
            'callback' => function() {
                return new WP_REST_Response(self::get_service_worker(), 200, [
                    'Content-Type' => 'application/javascript',
                    'Service-Worker-Allowed' => '/',
                ]);
            },
            'permission_callback' => '__return_true',
        ]);
    }
}
