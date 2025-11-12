# ⚡ Quick Start: Tema Influencer + IPV Pro

Guida ultra-rapida per iniziare in **5 minuti**.

---

## 📦 Cosa Hai Ricevuto

✅ **Plugin IPV Production System Pro v2.0.0**
- Sistema completo import video YouTube
- Trascrizione AI (SupaData)
- Generazione contenuti (OpenAI)
- Queue automatica
- RSS auto-import
- Dashboard gestione

✅ **Integrazione Tema Influencer** (NUOVO!)
- Widget video recenti
- Shortcode video player/grid/stats
- CSS responsive completo
- JavaScript interattivo
- SEO schema markup

✅ **Child Theme Pronto** (NUOVO!)
- Design ottimizzato per Influencer
- Prompt AI personalizzati
- CTA automatiche
- Featured image automatica
- Email notifications
- Documentazione completa

---

## 🚀 Start in 5 Passi

### 1️⃣ Installa Plugin (2 min)

```bash
# Via WordPress Admin
1. Vai su Plugin → Aggiungi nuovo → Carica plugin
2. Seleziona ipv-production-system-pro-v2.0.0-COMPLETE.zip
3. Installa e attiva
```

**Configura API:**
- IPV Production → Impostazioni
- Inserisci YouTube API Key
- Inserisci SupaData API Key
- Inserisci OpenAI API Key
- Testa connessioni

### 2️⃣ Installa Child Theme (1 min)

```bash
# Metodo FTP/SFTP
1. Copia cartella: example-child-theme
2. In: /wp-content/themes/influencer-child
3. WordPress Admin → Aspetto → Temi
4. Attiva "Influencer Child - IPV Integration"
```

### 3️⃣ Configura Widget (1 min)

```bash
# WordPress Admin
1. Vai su Aspetto → Widget
2. Trova "Video Sidebar Influencer"
3. Aggiungi widget "IPV Video Recenti"
4. Configura (titolo, numero video, thumb on/off)
```

### 4️⃣ Test Import Video (1 min)

```bash
# Import singolo
1. IPV Production → Dashboard
2. Incolla URL video YouTube
3. Click "Importa Video"
4. Attendi (~2-3 min)
5. Verifica post creato
```

### 5️⃣ Abilita Auto-Import (opzionale)

```bash
# RSS Feed automatico
1. IPV Production → Impostazioni
2. Tab "RSS Auto-Import"
3. Abilita auto-import
4. Inserisci RSS feed YouTube
5. Configura intervallo (60 min default)
6. Abilita notifiche email (opzionale)
7. Salva
```

✅ **Fatto! Il sistema è attivo.**

---

## 📍 Dove Trovare Tutto

### Documentazione

| File | Cosa Contiene | Quando Leggerlo |
|------|---------------|-----------------|
| `INTEGRATION_SUMMARY.md` | Riepilogo completo integrazione | Subito (panoramica) |
| `THEME_INTEGRATION_GUIDE.md` | Guida tecnica dettagliata | Se vuoi personalizzare |
| `example-child-theme/README.md` | Doc child theme completa | Per capire child theme |
| `example-child-theme/INSTALLATION.md` | Setup rapido child theme | Prima di installare |
| `README.md` | Documentazione plugin base | Per capire plugin IPV |

### File Codice

| File | Cosa Fa | Quando Modificare |
|------|---------|-------------------|
| `example-child-theme/style.css` | Stili child theme | Per cambiare colori/design |
| `example-child-theme/functions.php` | Hook e personalizzazioni | Per modificare CTA/prompt |
| `includes/class-theme-integration.php` | Classe integrazione | Non modificare (è core) |
| `assets/css/frontend.css` | Stili frontend | Non modificare (è core) |
| `assets/js/frontend.js` | Scripts frontend | Non modificare (è core) |

---

## 🎨 Personalizzazioni Rapide

### Cambia Colori Brand

**File:** `example-child-theme/style.css` (linea ~20)

```css
:root {
    --ipv-primary-color: #TUO_COLORE;
    --ipv-secondary-color: #TUO_COLORE;
    --ipv-accent-color: #TUO_COLORE;
}
```

### Modifica CTA Finale

**File:** `example-child-theme/functions.php` (linea ~50)

Cerca funzione `influencer_add_cta_section()`:

```php
$cta = '
## 💬 IL TUO TESTO PERSONALIZZATO

[Scrivi qui la tua CTA]
';
```

### Aggiorna Link Social

**File:** `example-child-theme/functions.php` (linea ~70)

Nella stessa funzione, modifica:

```php
- **Telegram**: [t.me/TUO_CANALE](https://t.me/TUO_CANALE)
- **Facebook**: [facebook.com/TUA_PAGINA](...)
- **Instagram**: [@TUO_HANDLE](...)
```

---

## 🔧 Shortcode Pronti All'Uso

### Video Player Singolo

```php
[ipv_video_player id="123"]
```

Mostra player YouTube del post ID 123.

### Griglia Video Recenti

```php
[ipv_recent_videos count="6" columns="3"]
```

Mostra 6 video in griglia 3 colonne.

### Griglia Filtrata per Categoria

```php
[ipv_recent_videos count="9" columns="3" category="5"]
```

Solo video della categoria ID 5.

### Statistiche Video

```php
[ipv_video_stats id="123"]
```

Box con views/likes/comments.

### Embed Veloce

```php
[ipv_video_embed url="https://youtube.com/watch?v=xxxxx"]
```

Embed diretto da URL.

---

## 📊 Widget Disponibili

### IPV Video Recenti

**Dove:** Aspetto → Widget → "Video Sidebar Influencer"

**Opzioni:**
- Titolo widget
- Numero video (1-20)
- Mostra thumbnail (sì/no)
- Mostra statistiche (sì/no)

**Utilizzo:**
Trascina nella sidebar, configura, salva.

---

## 🎯 Template Functions (Per Developer)

Se stai creando template personalizzati:

### Mostra Player Video

```php
<?php IPV_Theme_Integration::the_video_player(); ?>
```

### Mostra Meta Informazioni

```php
<?php IPV_Theme_Integration::the_video_meta(); ?>
```

### Ottieni Metadata Video

```php
<?php
$meta = IPV_Theme_Integration::get_video_meta($post_id);
echo $meta['video_id'];
echo $meta['channel_title'];
echo $meta['view_count'];
?>
```

---

## ❓ FAQ Rapide

### Il child theme non si attiva?

**Verifica:**
- Tema "Influencer" installato (anche se non attivo)
- File `style.css` presente nella cartella child theme
- Cartella nome corretto: `influencer-child`

### Stili non si vedono?

**Soluzione:**
1. Ctrl+Shift+R (refresh forzato browser)
2. Svuota cache WordPress (se plugin cache)
3. Verifica child theme attivo in Aspetto → Temi

### Featured image non si imposta?

**Verifica:**
- Permessi `/wp-content/uploads/` = 755
- PHP `allow_url_fopen = On`
- Connessione internet server OK

### Video non si vedono?

**Verifica:**
- Plugin IPV attivo
- API keys configurate
- Test import completato con successo
- Post pubblicato (non in bozza)

### Widget sidebar non appare?

**Soluzione:**
1. Disattiva child theme
2. Riattiva child theme
3. Vai su Aspetto → Widget
4. Cerca "Video Sidebar Influencer"

---

## 🔍 Test Rapido Funzionalità

### ✅ Plugin IPV Funziona?

```bash
1. IPV Production → Dashboard
2. Incolla URL YouTube
3. Click "Importa Video"
4. Attendi 2-3 minuti
5. Controlla Video Manager
6. Status = "Completato" ✅
```

### ✅ Child Theme Attivo?

```bash
1. Aspetto → Temi
2. Badge "Attivo" su "Influencer Child" ✅
```

### ✅ Featured Image OK?

```bash
1. Post → Tutti i post
2. Trova video importato
3. Colonna "Immagine in evidenza"
4. Thumbnail presente ✅
```

### ✅ CTA Visibile?

```bash
1. Apri post video pubblicato
2. Scorri a fine articolo
3. Vedi sezione "💬 Partecipa alla Discussione" ✅
```

### ✅ Schema Markup OK?

```bash
1. Vai su: https://search.google.com/test/rich-results
2. Incolla URL post video
3. Click "Test URL"
4. Vedi "VideoObject" rilevato ✅
```

---

## 📞 Serve Aiuto?

### Problema Tecnico

1. **Abilita debug:**
   ```php
   // wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Controlla log:**
   ```bash
   /wp-content/debug.log
   ```

3. **Leggi troubleshooting:**
   - `THEME_INTEGRATION_GUIDE.md` → sezione "Troubleshooting"
   - `example-child-theme/README.md` → sezione "Debug"

### Vuoi Personalizzare

1. **Leggi guide:**
   - `INTEGRATION_SUMMARY.md` (panoramica)
   - `THEME_INTEGRATION_GUIDE.md` (dettagli tecnici)

2. **Studia esempi:**
   - `example-child-theme/functions.php` (hook esempi)
   - `example-child-theme/style.css` (CSS esempi)

3. **Esplora classe:**
   - `includes/class-theme-integration.php` (core funzionalità)

---

## 🎉 Ready to Go!

Dopo aver completato i 5 passi iniziali:

✅ **Sistema Attivo** → Video importati automaticamente
✅ **Design Integrato** → Perfettamente inserito nel tema
✅ **SEO Ottimizzato** → Schema markup attivo
✅ **CTA Automatiche** → Engagement massimizzato
✅ **Performance** → Lazy loading e caching

### Prossimi Passi:

1. 📹 **Importa i tuoi video** (manuale o RSS)
2. 🎨 **Personalizza colori** (opzionale)
3. ✏️ **Modifica CTA** (opzionale)
4. 📊 **Monitora statistiche** (Video Manager)
5. 🚀 **Pubblica e cresci!**

---

## 📚 Risorse Quick Access

| Cosa Cerchi | Dove Trovarlo | Tempo Lettura |
|-------------|---------------|---------------|
| Panoramica completa | `INTEGRATION_SUMMARY.md` | 10 min |
| Setup child theme | `example-child-theme/INSTALLATION.md` | 5 min |
| Personalizzazioni | `THEME_INTEGRATION_GUIDE.md` | 30 min |
| Riferimento API | `includes/class-theme-integration.php` | - |
| Esempi codice | `example-child-theme/functions.php` | 15 min |

---

## ✨ Tutto Pronto!

Hai ricevuto un sistema completo e professionale per:

🎥 **Automatizzare** la pubblicazione di contenuti video
🤖 **Generare** articoli SEO-friendly con AI
🎨 **Integrare** perfettamente con il tema Influencer
📈 **Crescere** la tua audience con CTA automatiche
⚡ **Risparmiare** ore di lavoro manuale

**Inizia subito con i 5 passi sopra!** 🚀

---

**IPV Production System Pro v2.0.0**
*with Influencer Theme Integration*

✅ **Quick Start Complete** - Ready to Publish!

🌐 [ilpuntodivistachannel.com](https://ilpuntodivistachannel.com)
