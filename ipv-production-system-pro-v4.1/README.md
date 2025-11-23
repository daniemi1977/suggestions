# IPV Production System Pro v4.2.0

**Sistema di produzione avanzato per il canale YouTube "Il Punto di Vista"**

![Version](https://img.shields.io/badge/version-4.2.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)

---

## Novita v4.2.0 - BULK IMPORT + YOUTUBE API COMPLETA!

### Bulk Import
Importazione massiva di video precedenti dal canale:
- Lista ultimi N video dal canale (10, 25, 50, 100)
- Selezione multipla con checkbox
- Preview dati prima dell'importazione
- Controllo duplicati automatico
- Risoluzione automatica Channel ID da URL

### YouTube Data API v3 Completa
Estrazione di TUTTI i metadati video:
- Title, Description, Tags, Category
- Duration, Definition, Caption status
- View Count, Like Count, Comment Count
- Thumbnail URLs (multiple risoluzioni)
- Published date, Channel info
- Salvataggio automatico nei meta del post
- Aggiornamento dati on-demand

### CPT Migliorato
- Tassonomie: Categorie Video e Tag Video
- Meta boxes per dati YouTube e trascrizione
- Colonne admin: thumbnail, durata, views, stato
- Ordinamento per views e durata
- Azioni rapide: aggiorna dati, rigenera trascrizione/AI

### SupaData Fixes
- Correzione campo risposta (`content` invece di `transcript`)
- Rotazione automatica API key (una per riga)
- Gestione errori 402 (quota) e 429 (rate limit)
- Supporto job asincroni con polling automatico

---

## Architettura del Sistema

Il plugin opera su un ciclo di automazione basato su Cron Jobs e una tabella database personalizzata.

### Flusso dei Dati (Pipeline)

1. **Feed Atom / Bulk Import**: Scansiona il canale per nuovi video
2. **YouTube Data API**: Estrae tutti i metadati strutturali
3. **Filtro Duplicati**: Verifica aggressiva (DB + Post Meta + any status)
4. **Trascrizione (SupaData)**: Scarica il testo parlato con rotazione API key
5. **AI Processing (OpenAI)**: Genera descrizione usando il Golden Prompt
6. **Media Sideload**: Scarica thumbnail dalla libreria YouTube
7. **Pubblicazione**: Aggiorna il post con contenuto, excerpt, featured image

---

## Struttura dei File

```
ipv-production-system-pro/
├── ipv-production-system-pro.php    # Bootstrap principale
├── README.md                         # Questa documentazione
├── assets/
│   ├── css/admin.css                # Stili admin Bootstrap 5
│   └── js/admin.js                  # JavaScript admin
└── includes/
    ├── class-ai-generator.php       # OpenAI + Golden Prompt
    ├── class-bulk-import.php        # Importazione massiva
    ├── class-cpt.php                # Custom Post Type + Meta Boxes
    ├── class-logger.php             # Logging utility
    ├── class-queue.php              # Coda elaborazione + Cron
    ├── class-rss-importer.php       # Auto-import RSS
    ├── class-settings.php           # Pagina impostazioni
    ├── class-supadata.php           # SupaData API + Rotazione key
    ├── class-youtube-api.php        # YouTube Data API v3
    ├── class-youtube-importer.php   # Importazione singola
    └── views/
        └── rss-settings.php         # UI Auto-Import RSS
```

---

## Configurazione API

### YouTube Data API v3
1. Vai su Google Cloud Console
2. Crea un nuovo progetto
3. Abilita "YouTube Data API v3"
4. Crea credenziali API Key
5. Inserisci la key nelle Impostazioni del plugin

### SupaData API
1. Registrati su supadata.ai
2. Ottieni la tua API key
3. Inseriscila nelle Impostazioni
4. **Per rotazione**: inserisci piu key, una per riga

### OpenAI API
1. Vai su platform.openai.com
2. Crea una API key
3. Inseriscila nelle Impostazioni

---

## Metadati Salvati

Ogni video importato salva i seguenti meta:

| Meta Key | Descrizione |
|----------|-------------|
| `_ipv_video_id` | YouTube Video ID |
| `_ipv_youtube_url` | URL completo video |
| `_ipv_yt_title` | Titolo originale YouTube |
| `_ipv_yt_description` | Descrizione originale |
| `_ipv_yt_published_at` | Data pubblicazione |
| `_ipv_yt_channel_title` | Nome canale |
| `_ipv_yt_tags` | Array tag YouTube |
| `_ipv_yt_category_id` | ID categoria YouTube |
| `_ipv_yt_thumbnail_url` | URL thumbnail migliore |
| `_ipv_yt_duration` | Durata ISO 8601 |
| `_ipv_yt_duration_seconds` | Durata in secondi |
| `_ipv_yt_duration_formatted` | Durata formattata (1:30:45) |
| `_ipv_yt_definition` | HD/SD |
| `_ipv_yt_view_count` | Visualizzazioni |
| `_ipv_yt_like_count` | Like |
| `_ipv_yt_comment_count` | Commenti |
| `_ipv_transcript` | Trascrizione completa |
| `_ipv_ai_description` | Descrizione generata AI |
| `_ipv_source` | Fonte (manual/rss/bulk) |

---

## Changelog

### v4.2.0 (Nov 2024)
- Bulk Import: importazione massiva video precedenti
- YouTube Data API v3 completa con tutti i metadati
- CPT migliorato con tassonomie e meta boxes
- SupaData: rotazione API key e gestione errori 402/429
- Colonne admin: thumbnail, durata, views, stato
- Download automatico thumbnail come featured image
- Importazione tag YouTube come tassonomie

### v4.1.0 (Nov 2024)
- RSS Auto-Import completamente automatico
- UI moderna con Bootstrap 5
- Dashboard con grafici interattivi
- Sistema coda migliorato

### v4.0.0
- Rilascio iniziale con Golden Prompt 350+ righe

---

## Requisiti

- WordPress 5.8+
- PHP 7.4+
- API Keys: SupaData, OpenAI, YouTube Data API v3

---

**Il Punto di Vista** - Made with love by Daniele
