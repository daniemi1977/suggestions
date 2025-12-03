# WP Event Calendar Pro - Installazione Rapida

## 📦 Contenuto ZIP

File: **wp-event-calendar-pro.zip** (55 KB)

### Struttura:
```
wp-event-calendar-pro/
├── wp-event-calendar-pro.php (Main plugin file)
├── README.md (Documentazione completa)
├── API-DOCUMENTATION.md (API reference)
├── ELEMENTOR-GUIDE.md (Guida Elementor)
├── includes/ (13 file PHP)
│   ├── class-event-post-type.php
│   ├── class-event-calendar.php
│   ├── class-woocommerce-integration.php
│   ├── class-booking-manager.php
│   ├── class-qr-code-generator.php
│   ├── class-admin-settings.php
│   ├── class-elementor-integration.php
│   └── elementor/
│       ├── widget-calendar.php
│       ├── widget-events-list.php
│       ├── widget-events-tile.php
│       └── widget-single-event.php
├── assets/
│   ├── css/
│   │   ├── calendar.css
│   │   ├── admin.css
│   │   ├── elementor-editor.css
│   │   └── skins/ (6 temi)
│   │       ├── skin-default.css
│   │       ├── skin-minimal.css
│   │       ├── skin-modern.css
│   │       ├── skin-bold.css
│   │       ├── skin-classic.css
│   │       └── skin-elegant.css
│   └── js/
│       ├── calendar.js
│       └── admin.js
├── templates/ (vuota, per personalizzazioni future)
└── languages/ (vuota, pronta per traduzioni)
```

---

## 🚀 Installazione

### Metodo 1: Upload tramite WordPress Admin (CONSIGLIATO)

1. **Login** al tuo WordPress Admin
2. Vai a **Plugin > Aggiungi nuovo**
3. Clicca **Carica plugin** (in alto)
4. **Scegli file**: Seleziona `wp-event-calendar-pro.zip`
5. Clicca **Installa ora**
6. Clicca **Attiva plugin**

✅ Fatto!

### Metodo 2: Upload via FTP

1. **Estrai** il file ZIP sul tuo computer
2. **Carica** la cartella `wp-event-calendar-pro` via FTP in:
   ```
   /wp-content/plugins/
   ```
3. Vai a **WordPress Admin > Plugin**
4. **Attiva** "WP Event Calendar Pro"

---

## ⚙️ Configurazione Iniziale

### 1. Installa WooCommerce (Opzionale ma consigliato)

Se vuoi usare il sistema di prenotazione:
- Vai a **Plugin > Aggiungi nuovo**
- Cerca "WooCommerce"
- Installa e attiva

### 2. Configura Impostazioni

1. Vai a **Event Calendar > Settings**
2. Configura:
   - Default Calendar View
   - Show Past Events
   - Default Event Color
   - Enable Lightbox
   - Enable Booking

### 3. Crea Prima Categoria

1. Vai a **Event Calendar > Categories**
2. Aggiungi categorie come:
   - Concerti
   - Workshop
   - Conferenze
   - Festival

### 4. Crea Primo Evento

1. Vai a **Event Calendar > Add New**
2. Compila:
   - **Titolo**: Nome evento
   - **Descrizione**: Contenuto completo
   - **Event Details**: Date, orari, colore
   - **Location**: Venue, indirizzo, città
   - **Tickets & Booking**:
     - Spunta "Enable Booking"
     - Imposta prezzo (es: 25.00)
     - Imposta posti max (es: 100)
3. **Immagine in evidenza**: Aggiungi foto evento
4. **Pubblica**

---

## 📄 Usare in una Pagina

### Metodo 1: Con Elementor (CONSIGLIATO)

1. **Crea/Modifica** una pagina con Elementor
2. **Cerca** "Event Calendar" nella sidebar widget
3. **Trascina** widget nella pagina
4. **Scegli skin** dal dropdown (es: "Modern")
5. **Personalizza** colori e layout
6. **Pubblica**

### Metodo 2: Con Shortcode

Aggiungi in qualsiasi pagina/post:

**Calendario mese:**
```
[wecp_calendar view="month" skin="modern"]
```

**Lista eventi:**
```
[wecp_events_list category="concerti" limit="10"]
```

**Griglia eventi:**
```
[wecp_events_tile columns="3" skin="bold"]
```

---

## 🎨 Scegliere il Tema Giusto

| Tipo Evento | Skin Consigliato |
|-------------|------------------|
| 🎵 Festival Musicale | **Modern** (colorato) |
| 🎭 Teatro/Opera | **Classic** (elegante) |
| 💼 Business/Corporate | **Default** o **Minimal** |
| 🎸 Concerti Rock | **Bold** (audace) |
| 💒 Wedding/Luxury | **Elegant** (premium) |
| 🎨 Arte/Galleria | **Minimal** (pulito) |

---

## 🛒 Sistema Prenotazione

### Come Funziona:

1. **Utente** trova evento nel calendario
2. Clicca **"Book Now"**
3. Evento aggiunto a **carrello WooCommerce**
4. **Checkout** standard WooCommerce
5. Dopo pagamento:
   - ✅ Email conferma
   - ✅ QR code generato automaticamente
   - ✅ Ticket scaricabile

### Verificare Prenotazioni:

1. Vai a **WooCommerce > Ordini**
2. Visualizza ordini con eventi
3. Vedi dettagli evento nell'ordine

### Usare Scanner QR:

1. Crea pagina "Check-in"
2. Aggiungi shortcode: `[wecp_ticket_scanner]`
3. Solo Admin possono accedere
4. Scansiona/inserisci codice biglietto
5. Sistema verifica e marca check-in

---

## 📱 Responsive & Mobile

Il plugin è 100% responsive:
- ✅ Layout mobile ottimizzato
- ✅ Touch-friendly buttons
- ✅ Swipe per navigare calendario
- ✅ Lightbox responsive

**Consiglio Mobile:**
- Usa **List View** invece di Month su mobile
- Font size minimo 14px
- Button height minimo 44px

---

## 🔧 Troubleshooting

### Plugin non appare dopo attivazione
**Soluzione:** Svuota cache browser e ricarica pagina

### Eventi non visualizzati
**Soluzione:**
- Verifica che eventi siano pubblicati
- Controlla date (devono essere future)
- Rimuovi filtri categoria

### Booking non funziona
**Soluzione:**
- Verifica WooCommerce installato e attivo
- Controlla "Enable Booking" spuntato nell'evento
- Verifica stock prodotto associato

### Widget Elementor non appare
**Soluzione:**
- Verifica Elementor installato (min v3.0)
- Disattiva/riattiva plugin
- Rigenera CSS Elementor (Tools > Regenerate CSS)

### QR Code non generati
**Soluzione:**
- Controlla permessi cartella `/wp-content/uploads/`
- Chmod 755 o 777 sulla cartella
- Verifica ordine completato

---

## 📚 Documentazione Completa

Una volta installato, leggi:

1. **README.md** - Documentazione utente completa
2. **API-DOCUMENTATION.md** - Per sviluppatori
3. **ELEMENTOR-GUIDE.md** - Guida Elementor dettagliata

---

## ✨ Features Recap

✅ Calendario eventi multipla vista (Mese/Lista/Tile)
✅ 6 temi professionali stile EventON
✅ Integrazione WooCommerce per prenotazioni
✅ QR code automatico per check-in
✅ 4 widget Elementor drag & drop
✅ Lightbox popup per dettagli eventi
✅ Sistema shortcode potente
✅ Responsive design mobile-first
✅ AJAX interactions velocissimo
✅ Gestione stock e disponibilità
✅ Email conferme automatiche
✅ Categorie e tag eventi
✅ Filtri avanzati
✅ Google Maps ready
✅ Translation ready (WPML/Polylang)
✅ Developer friendly (hooks & filters)

---

## 🎯 Quick Start (5 minuti)

1. ⬆️ **Upload** `wp-event-calendar-pro.zip`
2. ✅ **Attiva** plugin
3. 🛒 **Installa** WooCommerce (opzionale)
4. 📝 **Crea** primo evento
5. 📄 **Aggiungi** shortcode o widget Elementor
6. 🎉 **Fatto!**

---

## 💡 Tips Iniziali

**Per iniziare velocemente:**
1. Crea 3-4 eventi di test
2. Usa skin "Modern" (più colorato e friendly)
3. Metti calendario in homepage
4. Abilita lightbox per UX migliore
5. Aggiungi categorie colorate

**Per massimo impatto:**
1. Foto eventi di qualità
2. Descrizioni coinvolgenti
3. Prezzi chiari
4. Venue con indirizzo completo
5. Categorie organizzate

---

## 🆘 Supporto

Problemi? Apri issue su GitHub con:
- Versione WordPress
- Versione PHP
- Versione WooCommerce (se usato)
- Versione Elementor (se usato)
- Descrizione problema
- Screenshot errore

---

## 🚀 Buon Lavoro!

Il plugin è pronto all'uso. Inizia a creare eventi bellissimi! 🎉

**Location file ZIP:**
```
/home/user/suggestions/wp-event-calendar-pro.zip
```

**Dimensione:** 55 KB (leggero e ottimizzato!)
