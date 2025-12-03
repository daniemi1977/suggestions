# Guida Integrazione Elementor

Guida completa per utilizzare WP Event Calendar Pro con Elementor Page Builder.

## 🎨 Widget Disponibili

Il plugin fornisce 4 widget Elementor personalizzati nella categoria **Event Calendar Pro**:

### 1. Event Calendar
Widget calendario completo con vista mese/settimana/lista/tile

### 2. Events List
Lista eventi verticale con filtraggio

### 3. Events Tile Grid
Griglia eventi in stile masonry

### 4. Single Event Display
Mostra un singolo evento specifico

---

## 🚀 Come Usare i Widget

### Aggiungere un Calendario

1. **Apri Elementor** su una pagina
2. **Cerca "Event"** nella barra laterale widget
3. **Trascina "Event Calendar"** nella pagina
4. **Configura** nelle impostazioni:

#### Scheda Content > Calendar Settings
- **View Type**: Month / Week / List / Tile
- **Interaction Mode**: Lightbox / Slide Down / Event Page / None
- **Theme/Skin**: Scegli tra 6 temi disponibili

#### Scheda Content > Filters
- **Category**: Filtra per categoria evento
- **Tag**: Filtra per tag
- **Show Past Events**: Mostra/nascondi eventi passati
- **Events Limit**: Numero massimo eventi

#### Scheda Style > Style
- **Primary Color**: Colore principale
- **Accent Color**: Colore accento
- **Text Color**: Colore testo
- **Background Color**: Colore sfondo
- **Heading Typography**: Font intestazioni
- **Body Typography**: Font testo
- **Border Radius**: Arrotondamento angoli
- **Box Shadow**: Ombra

#### Scheda Style > Buttons
- **Button Color**: Colore pulsante prenotazione
- **Button Hover Color**: Colore hover
- **Button Typography**: Font pulsanti

---

## 🎭 Temi/Skins Disponibili

### 1. **Default** - Pulito e Moderno
```
Colori: Blu (#3498db) e viola (#667eea)
Design: Moderno con gradienti e ombre
Ideale per: Siti business e professionali
```

**Caratteristiche:**
- Gradient colorati su pulsanti e badge
- Ombre morbide e profonde
- Border-left colorato sulle card
- Transizioni smooth

---

### 2. **Minimal** - Ultra Pulito
```
Colori: Bianco/nero monocromatico
Design: Essenziale, linee pulite
Ideale per: Portfolio, design agency, arte
```

**Caratteristiche:**
- Zero border radius (angoli squadrati)
- Palette monocromatica
- Font sottili e spaziatura generosa
- No ombre, bordi netti
- Tipografia uppercase e spaziata

**Ispirato a:** Design scandinavo minimalista

---

### 3. **Modern** - Contemporaneo e Vibrante
```
Colori: Rosso (#FF6B6B) e turchese (#4ECDC4)
Design: Bold con colori vivaci
Ideale per: Eventi giovani, festival, startup
```

**Caratteristiche:**
- Header colorato con gradiente
- Cell calendario arrotondate
- Colori vivaci e accesi
- Bottoni pill-shaped (arrotondati)
- Ombre colorate

**Perfetto per:** Eventi musicali, tech conference, festival

---

### 4. **Bold** - Audace ed Energico
```
Colori: Rosso intenso (#FF2D55) e viola (#5856D6)
Design: Alto contrasto, tipografia massiva
Ideale per: Eventi sportivi, concerti rock, sale
```

**Caratteristiche:**
- Bordi neri spessi (4px)
- Tipografia black weight (font-weight: 900)
- Trasformazioni e rotazioni on hover
- Colori fluorescenti
- Elementi geometrici squadrati

**Ispirato a:** Poster concerti anni '90, design punk

---

### 5. **Classic** - Eleganza Tradizionale
```
Colori: Marrone (#8B4513) e oro (#DAA520)
Design: Serif fonts, colori caldi
Ideale per: Eventi culturali, musei, teatri
```

**Caratteristiche:**
- Font serif (Georgia, Times)
- Colori terrosi e caldi
- Design raffinato e senza tempo
- Decorazioni dorate
- Stile retrò-elegante

**Perfetto per:** Concerti classici, mostre d'arte, gala

---

### 6. **Elegant** - Sofisticato e Lusso
```
Colori: Blu navy (#2C3E50) e oro pallido (#C0A772)
Design: Premium, raffinato, dettagli oro
Ideale per: Hotel 5 stelle, luxury events, wedding
```

**Caratteristiche:**
- Palette raffinata navy + oro
- Font serif eleganti
- Linee dorate decorative
- Ombre morbide e sfumate
- Transizioni fluide cubic-bezier
- Dettagli premium

**Ispirato a:** Brand luxury come Rolex, Chanel

---

## 📐 Layout e Combinazioni

### Layout Full Width
```
1. Aggiungi sezione full-width in Elementor
2. Inserisci widget Event Calendar
3. Imposta:
   - View: Month
   - Skin: Modern
   - Columns: Auto
```

### Layout Sidebar
```
1. Sezione con 2 colonne (70/30)
2. Colonna sinistra: Events List
3. Colonna destra: Widget sidebar
4. Skin consigliato: Minimal o Default
```

### Layout Grid Homepage
```
1. Sezione full-width
2. Widget: Events Tile Grid
3. Impostazioni:
   - Columns: 3
   - Limit: 6
   - Skin: Bold o Modern
```

### Landing Page Evento
```
1. Hero section con immagine
2. Single Event Display widget
3. Sezione booking prominente
4. Skin: Elegant per eventi premium
```

---

## 🎨 Combinazioni Colori Consigliate

### Per Eventi Musicali
```
Skin: Modern o Bold
Primary: #FF6B6B (Rosso vibrante)
Accent: #4ECDC4 (Turchese)
Background: #ffffff
```

### Per Eventi Corporate
```
Skin: Default o Minimal
Primary: #2C3E50 (Navy)
Accent: #3498db (Blu corporate)
Background: #f8f9fa
```

### Per Wedding/Matrimoni
```
Skin: Elegant o Classic
Primary: #C0A772 (Oro champagne)
Accent: #2C3E50 (Navy elegante)
Background: #faf8f5 (Avorio)
```

### Per Festival Giovani
```
Skin: Modern
Primary: #FF2D55 (Magenta)
Accent: #5856D6 (Viola elettrico)
Background: #ffffff
```

---

## ⚙️ Impostazioni Avanzate

### Responsive Design

Ogni widget supporta impostazioni responsive separate per:
- **Desktop** (>1024px)
- **Tablet** (768px - 1024px)
- **Mobile** (<768px)

**Esempio Columns responsive:**
```
Desktop: 4 colonne
Tablet: 2 colonne
Mobile: 1 colonna
```

### Custom CSS per Skin

Puoi sovrascrivere stili specifici usando CSS personalizzato:

```css
/* Personalizza skin Minimal */
.wecp-skin-minimal .wecp-event-card {
    border-bottom: 3px solid #000000;
}

/* Cambia font skin Classic */
.wecp-skin-classic .wecp-event-title {
    font-family: 'Crimson Text', serif;
}

/* Aggiungi animazione custom */
.wecp-skin-bold .wecp-event-card:hover {
    animation: pulse 0.5s ease;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
```

### CSS Variables per Theming

Ogni skin usa CSS variables che puoi sovrascrivere:

```css
.wecp-skin-modern {
    --wecp-primary-color: #your-color;
    --wecp-accent-color: #your-accent;
    --wecp-text-color: #your-text;
    --wecp-bg-color: #your-bg;
    --wecp-border-radius: 20px;
}
```

---

## 🔧 Tips & Best Practices

### Performance

1. **Limita numero eventi**: Non caricare più di 50 eventi per pagina
2. **Usa caching**: Attiva cache Elementor
3. **Ottimizza immagini**: Comprimi featured images

### Design

1. **Scegli skin appropriato**: Match con brand del sito
2. **Contrasto**: Assicura leggibilità testo
3. **Spazio bianco**: Non sovraffollare layout
4. **Mobile first**: Testa sempre su mobile

### UX

1. **Lightbox** per dettagli rapidi
2. **Filtri** per tanti eventi
3. **CTA evidenti** per booking
4. **Date chiare** e facili da leggere

---

## 🎯 Esempi Pratici

### Homepage Eventi Multipli

```
[Sezione Hero]
- Heading H1
- Sottotitolo
- CTA button

[Sezione Calendario]
- Widget: Event Calendar
- View: Month
- Skin: Modern
- Interaction: Lightbox

[Sezione Evidenza]
- Widget: Events Tile
- Columns: 3
- Limit: 6
- Category: Featured
- Skin: Bold
```

### Pagina Lista Eventi

```
[Header con Filtri]
- Dropdown categorie
- Search bar

[Body]
- Widget: Events List
- Skin: Minimal
- Limit: 20
- Show Past: No

[Sidebar]
- Widget: Calendar small
- Upcoming events
```

### Landing Singolo Evento

```
[Hero Section]
- Single Event Display
- Full width
- Skin: Elegant
- Large image

[Dettagli]
- Data, ora, luogo
- Descrizione completa
- Gallery immagini

[Booking Section]
- Form prenotazione WooCommerce
- CTA prominent
- Countdown timer
```

---

## 🛠️ Troubleshooting

### Widget non appare in Elementor

**Soluzione:**
1. Controlla che Elementor sia installato e attivo
2. Verifica versione Elementor (min 3.0)
3. Disattiva/riattiva plugin WP Event Calendar Pro
4. Pulisci cache Elementor

### Skin non si applica

**Soluzione:**
1. Svuota cache browser
2. Rigenera CSS Elementor (Tools > Regenerate CSS)
3. Controlla console errori
4. Verifica permessi file /wp-content/uploads/

### Lightbox non funziona

**Soluzione:**
1. Controlla conflitti jQuery
2. Disattiva altri lightbox plugin
3. Verifica Interaction Mode = "Lightbox"
4. Console browser per errori JS

### Eventi non visualizzati

**Soluzione:**
1. Verifica eventi pubblicati
2. Controlla date eventi (future)
3. Rimuovi filtri categoria/tag
4. Aumenta Limit eventi

---

## 📱 Ottimizzazione Mobile

### Impostazioni Consigliate Mobile

**Event Calendar:**
```
View: List (meglio di Month su mobile)
Columns: 1
Spacing: 15px
Font size: 14px
```

**Events Tile:**
```
Columns: 1
Image height: 200px
Padding: 15px
```

### Touch Interactions

Tutti i widget supportano:
- ✅ Tap per aprire dettagli
- ✅ Swipe per navigare mesi
- ✅ Pinch to zoom su immagini
- ✅ Touch-friendly button size (min 44px)

---

## 🔌 Integrazione con Altri Plugin

### Compatible Con:

- ✅ **WPML** - Traduzioni
- ✅ **Polylang** - Multilingua
- ✅ **WooCommerce** - Booking (già integrato)
- ✅ **Yoast SEO** - Schema markup eventi
- ✅ **Contact Form 7** - Form custom
- ✅ **Elementor Pro** - Popup, Theme Builder

### Template Elementor Pro

Puoi creare template per:
- Single Event page template
- Archive Events template
- Event Category template

---

## 🎓 Risorse

### Font Pairing Consigliati

**Minimal Skin:**
- Heading: Montserrat Light
- Body: Open Sans

**Classic Skin:**
- Heading: Playfair Display
- Body: Lora

**Modern Skin:**
- Heading: Poppins Bold
- Body: Inter

**Bold Skin:**
- Heading: Bebas Neue
- Body: Roboto Condensed

### Color Tools

- [Coolors.co](https://coolors.co) - Color palette generator
- [Adobe Color](https://color.adobe.com) - Color wheel
- [Contrast Checker](https://webaim.org/resources/contrastchecker/) - WCAG compliance

---

## 💡 Idee Creative

### Countdown Homepage
Combina widget Event Calendar con Elementor Countdown widget per urgenza

### Map Integration
Aggiungi Google Maps sotto Single Event per venue location

### Social Sharing
Usa Elementor Share Buttons per condivisione eventi

### Email Signup
Integra Mailchimp form per newsletter eventi futuri

---

## 📞 Supporto

Problemi o domande? Apri issue su GitHub o contatta supporto.

**Link Utili:**
- Plugin Documentation: README.md
- API Documentation: API-DOCUMENTATION.md
- GitHub Issues: [github.com/user/repo/issues]
