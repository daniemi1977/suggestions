# 🚀 Guida Rapida Installazione

Guida veloce per installare il child theme Influencer + IPV Pro in 5 minuti.

---

## Passaggio 1: Verifica Prerequisiti

Prima di iniziare, assicurati di avere:

- ✅ WordPress 5.8+ installato
- ✅ Tema **Influencer** installato (non deve essere attivo, basta installato)
- ✅ Plugin **IPV Production System Pro v2.0.0+** installato e attivato
- ✅ API Keys configurate (YouTube, SupaData, OpenAI)

---

## Passaggio 2: Installa Child Theme

### Opzione A: Via FTP/SFTP

1. Connettiti al server via FTP
2. Naviga in `/wp-content/themes/`
3. Carica la cartella `influencer-child` completa
4. Verifica che la struttura sia:
   ```
   /wp-content/themes/influencer-child/
   ├── style.css
   ├── functions.php
   └── README.md
   ```

### Opzione B: Via File Manager cPanel

1. Accedi a cPanel
2. Apri **File Manager**
3. Naviga in `/public_html/wp-content/themes/`
4. Click **Upload** e carica l'archivio ZIP del child theme
5. Estrai l'archivio
6. Verifica struttura file

### Opzione C: Via SSH

```bash
cd /percorso/sito/wp-content/themes/
# Copia cartella child theme
cp -r /path/to/influencer-child ./
# Verifica permessi
chmod 755 influencer-child
chmod 644 influencer-child/style.css
chmod 644 influencer-child/functions.php
```

---

## Passaggio 3: Attiva Child Theme

1. Accedi a **WordPress Admin**
2. Vai su **Aspetto → Temi**
3. Trova **Influencer Child - IPV Integration**
4. Click **Attiva**

✅ Il child theme è ora attivo!

---

## Passaggio 4: Configura Widget

1. Vai su **Aspetto → Widget**
2. Trova la sidebar **"Video Sidebar Influencer"**
3. Aggiungi widget a piacere:
   - **IPV Video Recenti** (consigliato)
   - **Categorie**
   - **Ricerca**
   - Altri widget personalizzati

---

## Passaggio 5: Test Importazione Video

1. Vai su **IPV Production → Dashboard**
2. Inserisci URL di un video YouTube di test
3. Click **Importa Video**
4. Attendi completamento elaborazione
5. Verifica che:
   - ✅ Post creato correttamente
   - ✅ Video embed presente
   - ✅ Featured image impostata
   - ✅ CTA finale presente
   - ✅ Meta box "Informazioni Video" visibile nell'editor

---

## Passaggio 6: Verifica Schema Markup

1. Vai al post video creato
2. Apri **Google Rich Results Test**: https://search.google.com/test/rich-results
3. Inserisci URL del post
4. Verifica presenza di:
   - ✅ VideoObject schema
   - ✅ Thumbnail URL
   - ✅ Upload date
   - ✅ Duration
   - ✅ Embed URL

---

## Passaggio 7: Abilita Notifiche Email (Opzionale)

1. Vai su **IPV Production → Impostazioni**
2. Scheda **RSS Auto-Import**
3. Abilita **"Invia Notifiche Email"**
4. Inserisci email destinatario
5. Click **Salva Modifiche**
6. Testa con nuovo video

---

## ⚡ Quick Test Commands

### Verifica Tema Attivo
```php
// Nel browser, apri: /wp-admin/
// Vai su Aspetto → Temi
// Verifica badge "Attivo" su Influencer Child
```

### Test Widget Sidebar
```php
// Vai su Aspetto → Widget
// Cerca "Video Sidebar Influencer"
// Se presente = ✅ OK
```

### Debug Mode (Opzionale)
```php
// Aggiungi a wp-config.php:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Log in: /wp-content/debug.log
```

---

## 🔧 Personalizzazioni Rapide

### Cambio Colori Brand

Modifica in `style.css`:

```css
:root {
    --ipv-primary-color: #TUO_COLORE;
    --ipv-secondary-color: #TUO_COLORE;
    --ipv-accent-color: #TUO_COLORE;
}
```

### Personalizza CTA

Modifica in `functions.php` la funzione:
```php
function influencer_add_cta_section($content, $post_id) {
    // Modifica qui il testo CTA
}
```

### Modifica Link Social

Aggiorna in `functions.php`:
```php
- **Telegram**: [t.me/TUO_CANALE](...)
- **Facebook**: [facebook.com/TUO_CANALE](...)
- **Instagram**: [@TUO_HANDLE](...)
```

---

## ❌ Problemi Comuni

### Errore: "Il tema parent è mancante"

**Causa:** Tema Influencer non installato

**Soluzione:**
1. Vai su **Aspetto → Temi → Aggiungi nuovo**
2. Cerca "Influencer"
3. Installa (non serve attivare)
4. Attiva child theme

### Stili non si vedono

**Soluzione:**
1. Svuota cache browser (Ctrl+Shift+R)
2. Svuota cache WordPress (se plugin cache attivo)
3. Verifica file `style.css` presente nella cartella child theme

### Widget sidebar non appare

**Soluzione:**
1. Verifica tema attivato correttamente
2. Vai su **Aspetto → Widget**
3. Controlla che "Video Sidebar Influencer" sia presente
4. Se assente, disattiva e riattiva child theme

### Featured image non si imposta

**Soluzione:**
1. Verifica permessi cartella `/wp-content/uploads/` (755)
2. Controlla PHP setting `allow_url_fopen = On`
3. Testa connessione YouTube da server

---

## 📊 Checklist Post-Installazione

Verifica che tutto funzioni:

- [ ] Child theme attivo (badge su Aspetto → Temi)
- [ ] Widget sidebar "Video Sidebar Influencer" presente
- [ ] Test import video completato con successo
- [ ] Post video visualizza embed YouTube
- [ ] Featured image presente nel post
- [ ] CTA finale visibile nel contenuto
- [ ] Meta box "Informazioni Video" nell'editor
- [ ] Schema markup validato (Google Rich Results Test)
- [ ] Sidebar video personalizzata visibile nel post
- [ ] Notifiche email funzionanti (se abilitate)

---

## 🎉 Installazione Completata!

Se tutti i punti della checklist sono verificati, l'installazione è completa!

### Prossimi Passi:

1. 📹 **Importa i tuoi video** da IPV Production → Dashboard
2. 🎨 **Personalizza colori** in style.css (opzionale)
3. ✏️ **Modifica CTA** in functions.php (opzionale)
4. 🔄 **Configura RSS Auto-Import** per importazione automatica
5. 📊 **Monitora Queue** in IPV Production → Video Manager

---

## 📞 Serve Aiuto?

- **Documentazione completa**: Leggi `README.md` nella cartella child theme
- **Guida integrazione**: Leggi `THEME_INTEGRATION_GUIDE.md` nella cartella plugin
- **Debug**: Abilita `WP_DEBUG` e controlla `/wp-content/debug.log`

---

**Tempo stimato installazione:** 5-10 minuti
**Difficoltà:** ⭐⭐☆☆☆ Facile

✅ **Installazione completata con successo!**
