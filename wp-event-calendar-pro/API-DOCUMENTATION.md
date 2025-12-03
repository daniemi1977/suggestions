# API Documentation - WP Event Calendar Pro

Documentazione completa delle API disponibili nel plugin.

## 📡 REST API Endpoints

### WooCommerce REST API Integration

Il plugin si integra con le API REST di WooCommerce. Per accedere ai prodotti eventi:

```http
GET /wp-json/wc/v3/products?meta_key=_wecp_event_id
```

**Autenticazione**: Usa le chiavi API WooCommerce standard.

## 🔌 AJAX API

Tutte le chiamate AJAX richiedono il nonce `wecp_nonce` disponibile in JavaScript tramite `wecpData.nonce`.

### 1. Add to Cart

Aggiunge un evento al carrello WooCommerce.

**Endpoint**: `wp-admin/admin-ajax.php`

**Action**: `wecp_add_to_cart`

**Method**: POST

**Parametri**:
| Nome | Tipo | Richiesto | Descrizione |
|------|------|-----------|-------------|
| `action` | string | Sì | `wecp_add_to_cart` |
| `nonce` | string | Sì | Nonce di sicurezza |
| `event_id` | int | Sì | ID dell'evento |
| `quantity` | int | No | Quantità (default: 1) |

**Risposta Success**:
```json
{
  "success": true,
  "data": {
    "message": "Event added to cart",
    "cart_url": "https://example.com/cart/",
    "cart_count": 3
  }
}
```

**Risposta Error**:
```json
{
  "success": false,
  "data": {
    "message": "Sorry, not enough tickets available"
  }
}
```

**Esempio JavaScript**:
```javascript
jQuery.ajax({
    url: wecpData.ajaxUrl,
    type: 'POST',
    data: {
        action: 'wecp_add_to_cart',
        nonce: wecpData.nonce,
        event_id: 123,
        quantity: 2
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data.message);
        }
    }
});
```

### 2. Check Availability

Verifica la disponibilità di posti per un evento.

**Action**: `wecp_check_availability`

**Parametri**:
| Nome | Tipo | Richiesto | Descrizione |
|------|------|-----------|-------------|
| `action` | string | Sì | `wecp_check_availability` |
| `nonce` | string | Sì | Nonce di sicurezza |
| `event_id` | int | Sì | ID dell'evento |
| `quantity` | int | No | Quantità da verificare (default: 1) |

**Risposta Success**:
```json
{
  "success": true,
  "data": {
    "available": true,
    "available_spots": 45,
    "current_bookings": 5,
    "max_attendees": 50
  }
}
```

Se `max_attendees` è null, `available_spots` sarà -1 (illimitato).

### 3. Get Events

Ottiene eventi per un mese specifico.

**Action**: `wecp_get_events`

**Parametri**:
| Nome | Tipo | Richiesto | Descrizione |
|------|------|-----------|-------------|
| `action` | string | Sì | `wecp_get_events` |
| `nonce` | string | Sì | Nonce di sicurezza |
| `month` | int | No | Mese (1-12, default: corrente) |
| `year` | int | No | Anno (default: corrente) |

**Risposta Success**:
```json
{
  "success": true,
  "data": {
    "2025-12-15": [
      {
        "ID": 123,
        "post_title": "Concert Name",
        "post_excerpt": "...",
        // altri dati evento
      }
    ],
    "2025-12-20": [...]
  }
}
```

### 4. Get Event Details

Ottiene i dettagli completi di un evento (HTML per lightbox).

**Action**: `wecp_get_event_details`

**Parametri**:
| Nome | Tipo | Richiesto | Descrizione |
|------|------|-----------|-------------|
| `action` | string | Sì | `wecp_get_event_details` |
| `nonce` | string | Sì | Nonce di sicurezza |
| `event_id` | int | Sì | ID dell'evento |

**Risposta Success**:
```json
{
  "success": true,
  "data": {
    "html": "<div class='wecp-event-details-modal'>...</div>"
  }
}
```

### 5. Verify Ticket

Verifica un codice QR di un biglietto.

**Action**: `wecp_verify_ticket`

**Parametri**:
| Nome | Tipo | Richiesto | Descrizione |
|------|------|-----------|-------------|
| `action` | string | Sì | `wecp_verify_ticket` |
| `nonce` | string | Sì | Nonce di sicurezza |
| `ticket_code` | string | Sì | Codice biglietto (hash MD5) |

**Risposta Success (Prima Check-in)**:
```json
{
  "success": true,
  "data": {
    "valid": true,
    "already_checked_in": false,
    "message": "Ticket verified successfully!",
    "event_title": "Concert Name",
    "customer_name": "John Doe",
    "order_id": 123
  }
}
```

**Risposta Success (Già Check-in)**:
```json
{
  "success": true,
  "data": {
    "valid": true,
    "already_checked_in": true,
    "checked_in_time": "2025-12-03 14:30:00",
    "message": "This ticket was already checked in",
    "event_title": "Concert Name",
    "customer_name": "John Doe"
  }
}
```

## 🎣 WordPress Hooks

### Actions

#### wecp_sync_event_product

Eseguito quando un evento viene sincronizzato con un prodotto WooCommerce.

**Parametri**:
- `$event_id` (int): ID dell'evento

**Esempio**:
```php
add_action('wecp_sync_event_product', function($event_id) {
    // Esegui azioni personalizzate dopo sync
    error_log("Event {$event_id} synced with WooCommerce");
}, 10, 1);
```

#### wecp_before_save_event_meta

Eseguito prima di salvare i meta dati dell'evento.

**Parametri**:
- `$post_id` (int): ID del post evento
- `$meta_data` (array): Array dei meta da salvare

**Esempio**:
```php
add_action('wecp_before_save_event_meta', function($post_id, $meta_data) {
    // Validazione personalizzata
    if (isset($meta_data['_wecp_max_attendees'])) {
        // Verifica limiti
    }
}, 10, 2);
```

#### wecp_after_generate_qr_codes

Eseguito dopo la generazione dei codici QR per un ordine.

**Parametri**:
- `$order_id` (int): ID ordine WooCommerce
- `$qr_codes` (array): Array dei percorsi QR code generati

**Esempio**:
```php
add_action('wecp_after_generate_qr_codes', function($order_id, $qr_codes) {
    // Invia email personalizzata con QR codes
}, 10, 2);
```

### Filters

#### wecp_events_query_args

Modifica i parametri della query eventi.

**Parametri**:
- `$args` (array): Array di argomenti WP_Query

**Return**: array (argomenti modificati)

**Esempio**:
```php
add_filter('wecp_events_query_args', function($args) {
    // Aggiungi meta query personalizzata
    if (!isset($args['meta_query'])) {
        $args['meta_query'] = array();
    }

    $args['meta_query'][] = array(
        'key' => '_wecp_featured',
        'value' => '1',
        'compare' => '='
    );

    return $args;
});
```

#### wecp_event_card_html

Personalizza l'HTML di una event card.

**Parametri**:
- `$html` (string): HTML della card
- `$event` (WP_Post): Oggetto post evento

**Return**: string (HTML modificato)

**Esempio**:
```php
add_filter('wecp_event_card_html', function($html, $event) {
    // Aggiungi badge "Featured"
    if (get_post_meta($event->ID, '_wecp_featured', true)) {
        $html = '<div class="featured-badge">Featured</div>' . $html;
    }
    return $html;
}, 10, 2);
```

#### wecp_product_data

Modifica i dati del prodotto WooCommerce creato per un evento.

**Parametri**:
- `$product_data` (array): Array dati prodotto
- `$event_id` (int): ID evento

**Return**: array (dati prodotto modificati)

**Esempio**:
```php
add_filter('wecp_product_data', function($product_data, $event_id) {
    // Aggiungi SKU personalizzato
    $product_data['sku'] = 'EVENT-' . $event_id;

    // Imposta categoria prodotto
    $product_data['category_ids'] = array(123);

    return $product_data;
}, 10, 2);
```

#### wecp_ticket_qr_url

Personalizza l'URL della Google Charts API per QR code.

**Parametri**:
- `$url` (string): URL API
- `$ticket_code` (string): Codice biglietto

**Return**: string (URL modificato)

**Esempio**:
```php
add_filter('wecp_ticket_qr_url', function($url, $ticket_code) {
    // Usa un servizio QR diverso
    return 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($ticket_code);
}, 10, 2);
```

## 📊 Database Schema

### Post Meta

Tabella: `wp_postmeta`

**Event Meta Fields**:

| meta_key | Tipo | Descrizione |
|----------|------|-------------|
| `_wecp_start_date` | string | Data inizio (Y-m-d) |
| `_wecp_end_date` | string | Data fine (Y-m-d) |
| `_wecp_start_time` | string | Ora inizio (H:i) |
| `_wecp_end_time` | string | Ora fine (H:i) |
| `_wecp_all_day` | string | Evento giornata intera (1/0) |
| `_wecp_event_color` | string | Colore hex (#RRGGBB) |
| `_wecp_venue_name` | string | Nome venue |
| `_wecp_venue_address` | string | Indirizzo |
| `_wecp_venue_city` | string | Città |
| `_wecp_venue_state` | string | Provincia/Stato |
| `_wecp_venue_zip` | string | CAP |
| `_wecp_venue_country` | string | Nazione |
| `_wecp_venue_lat` | string | Latitudine |
| `_wecp_venue_lng` | string | Longitudine |
| `_wecp_enable_booking` | string | Booking abilitato (1/0) |
| `_wecp_max_attendees` | string | Posti massimi (numero o vuoto) |
| `_wecp_ticket_price` | string | Prezzo biglietto (decimale) |
| `_wecp_wc_product_id` | string | ID prodotto WC associato |

### Order Item Meta

Tabella: `wp_woocommerce_order_itemmeta`

**Ticket Meta Fields**:

| meta_key | Tipo | Descrizione |
|----------|------|-------------|
| `_wecp_event_id` | string | ID evento |
| `_wecp_event_date` | string | Data evento |
| `_wecp_event_time` | string | Ora evento |
| `_wecp_venue_name` | string | Nome venue |
| `_wecp_ticket_code_{index}` | string | Codice biglietto (MD5 hash) |
| `_wecp_qr_code_{index}` | string | URL immagine QR code |

### Order Meta

Tabella: `wp_postmeta` (order posts)

| meta_key | Tipo | Descrizione |
|----------|------|-------------|
| `_wecp_checked_in_{ticket_code}` | string | Timestamp check-in |

## 🔍 Query Examples

### Get Upcoming Events

```php
$args = array(
    'post_type' => 'wecp_event',
    'posts_per_page' => 10,
    'meta_key' => '_wecp_start_date',
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'meta_query' => array(
        array(
            'key' => '_wecp_start_date',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE'
        )
    )
);

$events = new WP_Query($args);
```

### Get Events by Category

```php
$args = array(
    'post_type' => 'wecp_event',
    'tax_query' => array(
        array(
            'taxonomy' => 'wecp_event_category',
            'field' => 'slug',
            'terms' => 'concerts'
        )
    )
);

$events = new WP_Query($args);
```

### Get Events in Date Range

```php
$args = array(
    'post_type' => 'wecp_event',
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key' => '_wecp_start_date',
            'value' => '2025-12-01',
            'compare' => '>=',
            'type' => 'DATE'
        ),
        array(
            'key' => '_wecp_start_date',
            'value' => '2025-12-31',
            'compare' => '<=',
            'type' => 'DATE'
        )
    )
);

$events = new WP_Query($args);
```

### Get Orders for Event

```php
global $wpdb;

$event_id = 123;
$product_id = get_post_meta($event_id, '_wecp_wc_product_id', true);

$orders = $wpdb->get_results($wpdb->prepare("
    SELECT DISTINCT p.ID, p.post_status, p.post_date
    FROM {$wpdb->prefix}posts AS p
    INNER JOIN {$wpdb->prefix}woocommerce_order_items AS oi ON p.ID = oi.order_id
    INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim ON oi.order_item_id = oim.order_item_id
    WHERE p.post_type = 'shop_order'
    AND oim.meta_key = '_product_id'
    AND oim.meta_value = %d
    AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
    ORDER BY p.post_date DESC
", $product_id));
```

## 🛠️ Helper Functions

### Get Event Bookings Count

```php
function get_event_bookings_count($event_id) {
    $booking_manager = WECP_Booking_Manager::get_instance();
    return $booking_manager->get_event_bookings($event_id);
}
```

### Get Event Attendees

```php
function get_event_attendees($event_id) {
    $booking_manager = WECP_Booking_Manager::get_instance();
    return $booking_manager->get_event_attendees($event_id);
}
```

### Check Event Availability

```php
function is_event_available($event_id, $quantity = 1) {
    $booking_manager = WECP_Booking_Manager::get_instance();
    return $booking_manager->check_event_availability($event_id, $quantity);
}
```

## 📧 Email Hooks

### Customize Event Email

```php
add_action('woocommerce_email_order_details', function($order, $sent_to_admin) {
    foreach ($order->get_items() as $item) {
        $event_id = $item->get_meta('_wecp_event_id');

        if ($event_id) {
            echo '<h3>Event Details</h3>';
            echo '<p><strong>Event:</strong> ' . get_the_title($event_id) . '</p>';
            // Aggiungi altri dettagli
        }
    }
}, 10, 2);
```

## 🔐 Security

### Nonce Verification

Tutte le richieste AJAX sono protette con nonce:

```javascript
// JavaScript
wecpData.nonce // Contiene il nonce

// PHP
check_ajax_referer('wecp_nonce', 'nonce');
```

### Capability Checks

```php
// Solo admin possono accedere allo scanner
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized');
}
```

## 📱 Mobile Considerations

Le API sono completamente compatibili con dispositivi mobili. Per app native, considera:

1. Usa autenticazione JWT per API REST
2. Implementa caching locale
3. Gestisci offline con Service Workers
4. Ottimizza payload JSON

## 🚀 Performance

### Caching Recommendations

```php
// Cache event queries
$cache_key = 'wecp_upcoming_events_' . md5(serialize($args));
$events = wp_cache_get($cache_key);

if (false === $events) {
    $events = new WP_Query($args);
    wp_cache_set($cache_key, $events, '', 3600);
}
```

### Database Optimization

- Indici su `_wecp_start_date` per query veloci
- Usa `posts_per_page` per limitare risultati
- Evita `meta_query` complesse quando possibile

## 📞 Support & Contributing

Per domande sulle API o richieste di nuove funzionalità, apri una issue su GitHub.
