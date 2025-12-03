# WP Event Calendar Pro

Un plugin WordPress elegante e moderno per la gestione di calendari eventi con sistema di prenotazione integrato WooCommerce. Ispirato a EventON con un design pulito e minimalista.

## 🎨 Caratteristiche Principali

### Design & UX
- **Design Ispirato a EventON**: Interfaccia pulita, minimalista e professionale
- **Lightbox Popup**: Visualizzazione dettagli eventi in finestra modale elegante
- **Vista Multipla**: Mese, Lista, e Tile view
- **Completamente Responsive**: Ottimizzato per desktop, tablet e mobile
- **Color-Coded Events**: Eventi personalizzabili con colori per categoria
- **Animazioni Fluide**: Transizioni smooth e microinterazioni

### Gestione Eventi
- **Custom Post Type**: Eventi come tipo di contenuto dedicato
- **Campi Evento Completi**:
  - Data e ora inizio/fine
  - Eventi giornata intera
  - Luogo/Venue con indirizzo completo
  - Coordinate GPS per mappe
  - Immagine in evidenza
  - Categorie e tag

### Sistema di Prenotazione WooCommerce
- ✅ **Integrazione Completa WooCommerce**
- ✅ **Prodotti Automatici**: Crea automaticamente prodotti WC per ogni evento
- ✅ **Carrello WooCommerce**: Usa il sistema carrello esistente
- ✅ **Gestione Stock**: Limite partecipanti con controllo disponibilità
- ✅ **Multi-Ticket**: Acquisto multipli biglietti
- ✅ **Prezzi Personalizzabili**: Imposta prezzo per ogni evento
- ✅ **QR Code Tickets**: Generazione automatica QR code per check-in
- ✅ **Email Automatiche**: Conferme ordine con dettagli evento

### Funzionalità Avanzate
- **Scanner Biglietti**: Sistema di verifica QR code per check-in
- **AJAX Loading**: Caricamento veloce senza refresh pagina
- **Shortcode Potenti**: Sistema shortcode flessibile e personalizzabile
- **Multilingua Ready**: Compatibile con WPML e Polylang
- **Developer Friendly**: Hooks e filtri per personalizzazioni

## 📦 Installazione

### Requisiti
- WordPress 5.8 o superiore
- PHP 7.4 o superiore
- WooCommerce 5.0 o superiore (per funzionalità booking)

### Installazione Manuale
1. Scarica il plugin
2. Carica la cartella `wp-event-calendar-pro` in `/wp-content/plugins/`
3. Attiva il plugin dal menu Plugins di WordPress
4. Installa e attiva WooCommerce se vuoi usare il sistema di booking
5. Vai a Event Calendar > Settings per configurare

## 🚀 Utilizzo

### Shortcodes

#### Calendario Mese
```
[wecp_calendar view="month" category="concerts" interaction="lightbox"]
```

**Parametri:**
- `view`: month, week, list, tile (default: month)
- `category`: slug categoria eventi
- `tag`: slug tag evento
- `limit`: numero massimo eventi (default: 100)
- `show_past`: yes/no (default: no)
- `interaction`: lightbox, slide, page, none (default: lightbox)

#### Lista Eventi
```
[wecp_events_list category="workshops" limit="10" show_past="no"]
```

**Parametri:**
- `category`: filtra per categoria
- `tag`: filtra per tag
- `limit`: numero eventi da mostrare (default: 10)
- `show_past`: mostra eventi passati (default: no)

#### Vista Tile
```
[wecp_events_tile columns="3" limit="12"]
```

**Parametri:**
- `columns`: 2, 3, 4 (default: 3)
- `limit`: numero eventi (default: 12)
- `category`: filtra per categoria
- `tag`: filtra per tag

#### Scanner Biglietti
```
[wecp_ticket_scanner]
```

### Creare un Evento

1. Vai a **Event Calendar > Add New**
2. Inserisci titolo e descrizione evento
3. Compila i campi:
   - **Event Details**: Date, orari, colore
   - **Event Location**: Luogo, indirizzo, coordinate
   - **Event Tickets & Booking**: Abilita prenotazione, imposta prezzo e posti disponibili
4. Assegna categorie e tag
5. Aggiungi immagine in evidenza
6. Pubblica

### Abilitare Booking per un Evento

1. Modifica evento
2. Nel box "Event Tickets & Booking":
   - Spunta "Enable Booking for this Event"
   - Imposta "Max Attendees" (lascia vuoto per illimitato)
   - Imposta "Ticket Price"
3. Salva: il prodotto WooCommerce verrà creato automaticamente

## 🔌 API e Hooks

### AJAX Actions

#### wecp_add_to_cart
Aggiunge evento al carrello WooCommerce.

**Parametri:**
- `event_id`: ID evento
- `quantity`: numero biglietti (default: 1)

**Risposta:**
```json
{
  "success": true,
  "data": {
    "message": "Event added to cart",
    "cart_url": "https://...",
    "cart_count": 3
  }
}
```

#### wecp_check_availability
Verifica disponibilità posti per un evento.

**Parametri:**
- `event_id`: ID evento
- `quantity`: numero biglietti richiesti

**Risposta:**
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

#### wecp_get_event_details
Ottiene dettagli evento per lightbox.

**Parametri:**
- `event_id`: ID evento

**Risposta:**
```json
{
  "success": true,
  "data": {
    "html": "<div>...</div>"
  }
}
```

#### wecp_verify_ticket
Verifica validità QR code biglietto.

**Parametri:**
- `ticket_code`: codice biglietto

**Risposta:**
```json
{
  "success": true,
  "data": {
    "valid": true,
    "already_checked_in": false,
    "event_title": "Concert Name",
    "customer_name": "John Doe",
    "order_id": 123
  }
}
```

### WordPress Hooks

#### Actions

```php
// Dopo sincronizzazione evento con prodotto WC
do_action('wecp_sync_event_product', $event_id);

// Prima di salvare meta evento
do_action('wecp_before_save_event_meta', $post_id, $meta_data);

// Dopo generazione QR codes
do_action('wecp_after_generate_qr_codes', $order_id, $qr_codes);
```

#### Filters

```php
// Modifica parametri query eventi
add_filter('wecp_events_query_args', function($args) {
    // Modifica $args
    return $args;
});

// Personalizza HTML eventcard
add_filter('wecp_event_card_html', function($html, $event) {
    // Modifica $html
    return $html;
}, 10, 2);

// Modifica dati prodotto WC per evento
add_filter('wecp_product_data', function($product_data, $event_id) {
    // Modifica $product_data
    return $product_data;
}, 10, 2);
```

### Custom Post Type

**Nome**: `wecp_event`

**Meta Fields:**
- `_wecp_start_date`: Data inizio (Y-m-d)
- `_wecp_end_date`: Data fine (Y-m-d)
- `_wecp_start_time`: Ora inizio (H:i)
- `_wecp_end_time`: Ora fine (H:i)
- `_wecp_all_day`: Evento giornata intera (1/0)
- `_wecp_event_color`: Colore esadecimale (#rrggbb)
- `_wecp_venue_name`: Nome venue
- `_wecp_venue_address`: Indirizzo
- `_wecp_venue_city`: Città
- `_wecp_venue_state`: Provincia/Stato
- `_wecp_venue_zip`: CAP
- `_wecp_venue_country`: Nazione
- `_wecp_venue_lat`: Latitudine
- `_wecp_venue_lng`: Longitudine
- `_wecp_enable_booking`: Abilita prenotazione (1/0)
- `_wecp_max_attendees`: Posti massimi (numero)
- `_wecp_ticket_price`: Prezzo biglietto (decimale)
- `_wecp_wc_product_id`: ID prodotto WooCommerce associato

### Taxonomies

**Categorie**: `wecp_event_category` (gerarchica)
**Tag**: `wecp_event_tag` (non gerarchica)

## 🎯 Utilizzo Programmatico

### Ottenere Eventi

```php
// Query eventi futuri
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
            'type' => 'DATE',
        ),
    ),
);

$events = new WP_Query($args);
```

### Ottenere Dati Evento

```php
$event_id = 123;

$start_date = get_post_meta($event_id, '_wecp_start_date', true);
$venue_name = get_post_meta($event_id, '_wecp_venue_name', true);
$max_attendees = get_post_meta($event_id, '_wecp_max_attendees', true);
```

### Verificare Disponibilità

```php
$booking_manager = WECP_Booking_Manager::get_instance();
$available = $booking_manager->check_event_availability($event_id, $quantity);

if ($available) {
    // Posti disponibili
}
```

### Ottenere Lista Partecipanti

```php
$booking_manager = WECP_Booking_Manager::get_instance();
$attendees = $booking_manager->get_event_attendees($event_id);

foreach ($attendees as $attendee) {
    echo $attendee->first_name . ' ' . $attendee->last_name;
    echo $attendee->email;
    echo $attendee->quantity . ' tickets';
}
```

## 🎨 Personalizzazione CSS

### Override Stili

Crea un file CSS nel tuo tema:

```css
/* Personalizza colori evento */
.wecp-event-card {
    border-left-width: 6px;
}

/* Cambia colore bottoni */
.wecp-btn-book {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
}

/* Personalizza lightbox */
.wecp-lightbox-content {
    border-radius: 20px;
    max-width: 1000px;
}
```

### Classi CSS Disponibili

- `.wecp-calendar-wrapper`: Container calendario
- `.wecp-calendar-cell`: Singola cella giorno
- `.wecp-today`: Giorno corrente
- `.wecp-has-events`: Giorno con eventi
- `.wecp-event-card`: Card evento
- `.wecp-lightbox`: Modale lightbox
- `.wecp-btn`: Bottone generico
- `.wecp-btn-book`: Bottone prenotazione

## 🔧 Troubleshooting

### Eventi non visualizzati
- Verifica che gli eventi abbiano una data futura
- Controlla i parametri shortcode (category, show_past)
- Controlla i permessi utente

### Booking non funziona
- Verifica che WooCommerce sia installato e attivo
- Controlla che "Enable Booking" sia spuntato nell'evento
- Verifica stock prodotto WooCommerce associato

### QR Code non generati
- Controlla permessi scrittura cartella `/wp-content/uploads/`
- Verifica che l'ordine sia completato
- Controlla log errori PHP

## 📝 Changelog

### Version 1.0.0
- ✨ Release iniziale
- ✅ Sistema calendario con viste multiple
- ✅ Integrazione WooCommerce completa
- ✅ Generazione QR code biglietti
- ✅ Scanner biglietti
- ✅ Design responsive
- ✅ Lightbox eventi
- ✅ Sistema shortcode

## 📄 Licenza

GPL v2 or later

## 👨‍💻 Autore

Sviluppato con ❤️ per WordPress

## 🤝 Contribuire

Segnala bug o richiedi feature su GitHub Issues.

## 📞 Supporto

Per supporto e domande, apri una issue su GitHub.
