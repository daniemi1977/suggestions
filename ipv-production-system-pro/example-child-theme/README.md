# Influencer Child Theme - IPV Integration

Child theme per il tema **Influencer** con integrazione completa per **IPV Production System Pro**.

## 📋 Contenuto

Questo child theme include:

- ✅ **style.css** - Stili personalizzati per integrazione video
- ✅ **functions.php** - Hook e personalizzazioni IPV Pro
- ✅ **Schema markup** per SEO video
- ✅ **Widget sidebar** personalizzata per video
- ✅ **CTA automatiche** nei post video
- ✅ **Featured image automatica** da thumbnail YouTube
- ✅ **Notifiche email** per nuovi video
- ✅ **Meta box** informazioni video nell'editor

---

## 🚀 Installazione

### Metodo 1: Caricamento Manuale (Consigliato)

1. **Copia questa cartella** nella directory dei temi:
   ```
   /wp-content/themes/influencer-child/
   ```

2. **Attiva il child theme** da WordPress Admin:
   - Vai su **Aspetto → Temi**
   - Trova **Influencer Child - IPV Integration**
   - Clicca su **Attiva**

### Metodo 2: Creazione da Zero

Se preferisci creare il child theme manualmente:

1. Crea cartella `/wp-content/themes/influencer-child/`
2. Copia i file `style.css` e `functions.php` in questa cartella
3. Attiva il tema da WordPress Admin

---

## ⚙️ Configurazione

### 1. Verifica Tema Parent

Assicurati che il tema **Influencer** sia installato (non serve attivarlo, basta che sia presente).

### 2. Attiva Plugin IPV

Prima di attivare il child theme, verifica che **IPV Production System Pro** sia:
- Installato
- Attivato
- Configurato con le API keys

### 3. Configura Widget

Dopo l'attivazione, vai su **Aspetto → Widget** e popola la sidebar **"Video Sidebar Influencer"** con:
- Widget "IPV Video Recenti"
- Widget "Categorie"
- Widget personalizzati

---

## 🎨 Personalizzazioni Incluse

### Prompt OpenAI Ottimizzato

Il child theme personalizza automaticamente i prompt OpenAI per generare contenuti in stile **Influencer**:

- Tono professionale ma coinvolgente
- Emoji pertinenti (senza esagerare)
- Paragrafi brevi ottimizzati per mobile
- Struttura in sezioni ben definite

### CTA Automatiche

Ogni post video include automaticamente:

```markdown
## 💬 Partecipa alla Discussione!
[Invito a commentare]

### 🔔 Non Perderti i Prossimi Contenuti
[Link iscrizione canale]

### 📱 Seguici sui Social
[Link social media]

### ❤️ Supporta il Canale
[Call to action engagement]
```

### Featured Image Automatica

Quando un video viene importato, il child theme:
1. Scarica automaticamente la thumbnail YouTube (risoluzione massima)
2. La carica nella media library
3. La imposta come "Immagine in evidenza" del post

### Schema Markup SEO

Ogni post video include automaticamente schema markup `VideoObject` per:
- Migliorare SEO
- Abilitare rich snippets nei risultati Google
- Ottimizzare per Google Discover

### Meta Box Informazioni Video

Nell'editor WordPress, vedrai un meta box con:
- 🆔 Video ID
- 🔗 Link originale YouTube
- 📺 Nome canale
- 👁️ Visualizzazioni
- 👍 Mi Piace
- ⏱️ Durata

---

## 🔧 Hook Personalizzati

Il child theme utilizza questi hook IPV:

### Filtri

```php
// Personalizza prompt OpenAI
add_filter('ipv_pro_openai_prompt', 'influencer_customize_ipv_prompt', 10, 2);

// Aggiungi CTA al contenuto
add_filter('ipv_pro_generated_content', 'influencer_add_cta_section', 10, 2);
```

### Azioni

```php
// Imposta featured image automatica
add_action('ipv_pro_after_video_processing', 'influencer_auto_set_featured_image', 10, 2);

// Invia notifica email
add_action('ipv_pro_after_video_processing', 'influencer_send_video_notification', 20, 2);
```

---

## 📱 Shortcode Disponibili

### [influencer_video_grid]

Mostra griglia video in stile Influencer.

**Parametri:**
- `count` - Numero di video (default: 6)
- `columns` - Numero colonne (default: 3)
- `category` - Filtra per categoria ID

**Esempio:**
```php
[influencer_video_grid count="9" columns="3" category="5"]
```

### Shortcode IPV Standard

Tutti gli shortcode IPV funzionano normalmente:
- `[ipv_video_player]`
- `[ipv_recent_videos]`
- `[ipv_video_stats]`
- `[ipv_video_embed]`

---

## 🎨 CSS Personalizzazioni

### Variabili CSS

Il child theme definisce variabili per i colori brand:

```css
:root {
    --ipv-primary-color: #667eea;
    --ipv-secondary-color: #764ba2;
    --ipv-accent-color: #f093fb;
}
```

Puoi modificarle in `style.css` per adattarle al tuo brand.

### Dark Mode

Il child theme include supporto per dark mode (se il tema Influencer lo supporta):

```css
body.dark-mode .ipv-video-card {
    background: #1a1a1a;
    border-color: #333;
}
```

---

## 🔔 Notifiche Email

### Attivazione

Le notifiche email sono controllate dalle impostazioni IPV:

1. Vai su **IPV Production → Impostazioni**
2. Scheda **RSS Auto-Import**
3. Abilita **"Notifiche Email"**
4. Inserisci email destinatario

### Formato Email

```
Soggetto: 📹 Nuovo Video Pubblicato: [Titolo]

Corpo:
- Titolo video
- Link pubblico
- Link modifica
- Avviso revisione contenuto
```

---

## 📊 SEO Ottimizzazioni

### Schema Markup

Ogni video include structured data:

```json
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": "Titolo Video",
  "description": "Descrizione",
  "thumbnailUrl": "URL thumbnail",
  "uploadDate": "Data pubblicazione",
  "contentUrl": "URL YouTube",
  "embedUrl": "URL embed",
  "duration": "PT300S"
}
```

### Meta Tags

Il plugin IPV già gestisce:
- Open Graph tags
- Twitter Card tags
- Canonical URL

---

## 🐛 Debug e Troubleshooting

### Abilita Debug

Nel file `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Con debug abilitato, il child theme logga:
- Inizio elaborazione video
- Fine elaborazione video
- Eventuali errori

I log si trovano in `/wp-content/debug.log`.

### Problemi Comuni

#### Child theme non si attiva

**Soluzione:** Verifica che il tema parent "Influencer" sia installato.

#### Stili non si applicano

**Soluzione:**
1. Svuota cache browser (Ctrl+Shift+R)
2. Se usi plugin di caching, svuota cache
3. Verifica in `functions.php` che `influencer_child_ipv_enqueue_styles` sia presente

#### Featured image non si imposta

**Soluzione:**
1. Verifica permessi cartella `/wp-content/uploads/`
2. Controlla che `allow_url_fopen` sia abilitato in PHP
3. Verifica connessione a YouTube (firewall, proxy)

#### Notifiche email non arrivano

**Soluzione:**
1. Verifica impostazioni SMTP WordPress
2. Installa plugin "WP Mail SMTP" per testing
3. Controlla spam/junk della casella email

---

## 🔐 Sicurezza

Il child theme include:

- ✅ Validazione input con `sanitize_*` functions
- ✅ Escape output con `esc_*` functions
- ✅ Verifica nonce (gestite da IPV Pro)
- ✅ Controllo capacità utente (gestite da IPV Pro)
- ✅ Protezione accesso diretto con `ABSPATH` check

---

## 📝 Personalizzazione CTA

Per modificare la sezione CTA finale, edita in `functions.php`:

```php
function influencer_add_cta_section($content, $post_id) {
    $cta = '

    ## 💬 IL TUO CTA PERSONALIZZATO

    [Il tuo contenuto qui]
    ';

    return $content . $cta;
}
```

---

## 🎯 Best Practices

### 1. Backup Prima di Modifiche

Prima di modificare `functions.php`, fai sempre backup:
```bash
cp functions.php functions.php.backup
```

### 2. Usa Child Theme per Personalizzazioni

Non modificare mai direttamente:
- Tema parent Influencer
- Plugin IPV Production System Pro

Usa sempre il child theme per personalizzazioni.

### 3. Testa su Staging

Prima di applicare modifiche su produzione:
1. Testa su ambiente staging
2. Verifica funzionalità video
3. Controlla performance
4. Valida SEO

### 4. Monitora Performance

Dopo l'installazione:
- Testa velocità pagina (GTmetrix, PageSpeed Insights)
- Verifica lazy loading immagini
- Controlla dimensione HTML generato

---

## 📞 Supporto

Per supporto tecnico:

1. **Plugin IPV**: Verifica `README.md` nella cartella plugin
2. **Tema Influencer**: Contatta supporto tema
3. **Child Theme**: Controlla `THEME_INTEGRATION_GUIDE.md`

---

## ✅ Checklist Installazione

- [ ] Tema Influencer installato
- [ ] Plugin IPV Production System Pro attivato e configurato
- [ ] Child theme caricato in `/wp-content/themes/influencer-child/`
- [ ] Child theme attivato da **Aspetto → Temi**
- [ ] Widget configurati in **Video Sidebar Influencer**
- [ ] Notifiche email testate
- [ ] Import video test completato con successo
- [ ] Featured image automatica funzionante
- [ ] Schema markup verificato (Google Rich Results Test)
- [ ] CSS personalizzato applicato correttamente
- [ ] Sidebar video personalizzata attiva

---

## 📦 File Inclusi

```
influencer-child/
├── style.css          # Stili child theme + personalizzazioni IPV
├── functions.php      # Hook, filtri e personalizzazioni
└── README.md          # Questa documentazione
```

---

## 🔄 Aggiornamenti

Quando aggiorni:

### Plugin IPV Pro
✅ Nessun problema - il child theme è compatibile con tutte le versioni future

### Tema Influencer
✅ Nessun problema - le personalizzazioni sono nel child theme

### Child Theme
⚠️ Se modifichi i file, fai backup prima degli aggiornamenti

---

## 🎉 Risultato Finale

Dopo l'installazione completa avrai:

✅ **Sistema automatizzato** import video YouTube
✅ **Design integrato** perfettamente con tema Influencer
✅ **Contenuti AI ottimizzati** per engagement
✅ **SEO avanzato** con schema markup
✅ **CTA automatiche** per crescita community
✅ **Performance ottimizzate** con lazy loading
✅ **Notifiche automatiche** per nuovi video
✅ **Gestione semplificata** con meta box editor

Il tuo sito è pronto per pubblicare contenuti video automaticamente mantenendo qualità e stile professionale!

---

**Influencer Child Theme - IPV Integration v1.0.0**
*Developed for Il Punto di Vista Channel*
🌐 [ilpuntodivistachannel.com](https://ilpuntodivistachannel.com)
