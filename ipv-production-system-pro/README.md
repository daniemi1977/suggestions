# IPV Production System Pro v2.0.0

🎬 **Sistema completo per importazione automatica video YouTube con trascrizione AI e generazione contenuti**

Sviluppato per **Il Punto di Vista** - Canale YouTube di Adrian Fiorelli

---

## 📋 Indice

1. [Caratteristiche](#caratteristiche)
2. [Requisiti](#requisiti)
3. [Installazione](#installazione)
4. [Configurazione](#configurazione)
5. [Utilizzo](#utilizzo)
6. [Pipeline di Processing](#pipeline-di-processing)
7. [API Integrate](#api-integrate)
8. [Troubleshooting](#troubleshooting)
9. [FAQ](#faq)

---

## ✨ Caratteristiche

### Core Features

- ✅ **Importazione Automatica YouTube**: Importa metadati completi da YouTube Data API v3
- ✅ **Trascrizione AI**: Genera trascrizioni accurate con SupaData (fallback automatico native → AI)
- ✅ **Generazione Contenuti OpenAI**: Prompt ultra-rigoroso v1.9.1 con 15 sezioni strutturate
- ✅ **Queue System**: Processing asincrono con retry automatico e gestione errori
- ✅ **Bulk Import**: Importa fino a 50 video simultaneamente
- ✅ **Dashboard Moderna**: UI pulita e professionale con statistiche in tempo reale
- ✅ **Video Manager**: Stati dettagliati con tracking errori specifici
- ✅ **Mappatura Categorie**: Associa categorie YouTube → WordPress automaticamente
- ✅ **Auto-Publish**: Pubblicazione automatica o revisione manuale
- ✅ **Test API**: Verifica connessione API direttamente dall'interfaccia

### Stati Processing

Il plugin traccia 8 stati dettagliati:

- 🔵 **In Coda** (pending) - Video in attesa di elaborazione
- 🟡 **Importando** (importing) - Recupero dati da YouTube
- 🟠 **Trascrivendo** (transcribing) - Generazione trascrizione SupaData
- 🟣 **Generando AI** (generating_ai) - Generazione contenuti OpenAI
- 🟢 **Completato** (completed) - Elaborazione terminata con successo
- 🔴 **Errore YouTube** - Errore durante import metadata
- 🔴 **Errore Trascrizione** - Trascrizione non disponibile
- 🔴 **Errore AI** - Errore generazione contenuti

---

## 📦 Requisiti

### Server

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- WP Cron abilitato (o cron system)
- `allow_url_fopen` o `cURL` abilitato

### API Keys (Obbligatorie)

1. **YouTube Data API v3** - [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. **SupaData API** - [SupaData.ai](https://supadata.ai)
3. **OpenAI API** - [OpenAI Platform](https://platform.openai.com/api-keys)

### Costi Stimati

- **YouTube API**: Gratuita (10,000 richieste/giorno)
- **SupaData API**: ~$0.10-0.30 per trascrizione (dipende da lunghezza video)
- **OpenAI API**: ~$0.50-1.50 per video (GPT-4 Turbo)

**Budget mensile stimato**: $50-150 per 100-200 video/mese

---

## 🚀 Installazione

### Metodo 1: Upload ZIP

1. Scarica `ipv-production-system-pro.zip`
2. WordPress Admin → Plugin → Aggiungi Nuovo → Carica Plugin
3. Seleziona il file ZIP e clicca "Installa Ora"
4. Attiva il plugin

### Metodo 2: FTP

1. Estrai la cartella ZIP
2. Carica `ipv-production-system-pro/` in `/wp-content/plugins/`
3. Attiva il plugin da WordPress Admin

### Verifica Installazione

Dopo l'attivazione, dovresti vedere:

- ✅ Voce menu "IPV Production" nella sidebar
- ✅ Tabella `wp_ipv_processing_queue` nel database
- ✅ Cron job `ipv_pro_process_queue` schedulato

---

## ⚙️ Configurazione

### 1. Configurazione API Keys

**Vai a:** IPV Production → Impostazioni → API Keys

#### YouTube Data API v3

1. Vai a [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Crea nuovo progetto o seleziona esistente
3. Abilita **YouTube Data API v3**
4. Crea credenziali → API Key
5. Copia la chiave e incollala nelle impostazioni
6. Clicca **Test Connessione** per verificare

#### SupaData API

1. Registrati su [SupaData.ai](https://supadata.ai)
2. Vai al dashboard e copia la tua API key
3. Incollala nelle impostazioni
4. Clicca **Test Connessione** per verificare

#### OpenAI API

1. Vai a [OpenAI Platform](https://platform.openai.com/api-keys)
2. Crea nuova API key
3. Copia e incollala nelle impostazioni
4. Seleziona modello (consigliato: **GPT-4 Turbo**)
5. Clicca **Test Connessione** per verificare

### 2. Impostazioni Trascrizione

**Modalità Trascrizione:**

- **Automatica** (consigliata): Prova native captions → fallback AI
- **Solo Nativi**: Usa solo sottotitoli YouTube (più veloce, meno affidabile)
- **Sempre Generata**: Genera sempre con AI (più lento, più accurato)

**Timeout**: 300 secondi (5 minuti) per video lunghi

**Tentativi Massimi**: 3 retry in caso di errore

### 3. Mappatura Categorie

Associa categorie YouTube alle tue categorie WordPress:

| YouTube Category | → | WordPress Category |
|------------------|---|-------------------|
| People & Blogs   | → | Misteri           |
| Music            | → | Musica            |
| Entertainment    | → | Esoterismo        |
| News & Politics  | → | Geopolitica       |
| Science & Tech   | → | Scienza           |

**Categoria Default**: Usata quando non c'è mappatura specifica

### 4. Impostazioni Importazione

- **Pubblicazione Automatica**: ✅ Pubblica automaticamente / ❌ Lascia in bozza
- **Dimensione Batch**: 5 video elaborati simultaneamente dal cron

---

## 📖 Utilizzo

### Importare un Singolo Video

1. Vai a **IPV Production → Dashboard**
2. Copia l'URL del video YouTube
3. Incolla nel campo "Importa Singolo Video"
4. Clicca "Aggiungi alla Coda"
5. Il video verrà elaborato automaticamente in background

### Importare Video in Bulk

1. Vai a **IPV Production → Dashboard**
2. Nella sezione "Importa Multipli Video (Bulk)":
   - Inserisci un URL per riga
   - Massimo 50 video per volta
3. Clicca "Importa Bulk"
4. Il sistema processerà tutti i video in coda

### Monitorare i Video

**IPV Production → Video Manager**

- Visualizza tutti i video in coda
- Filtra per stato (Tutti, In Coda, In Elaborazione, Completati, Errori)
- Vedi step corrente di ogni video
- Tooltip con errori dettagliati
- Azioni: Riprova, Vedi Post, Elimina

### Gestione Errori

**Video Fallito?**

1. Passa il mouse sull'icona ℹ️ per vedere l'errore dettagliato
2. Clicca "Riprova" per reprocessare il video
3. Se persiste, controlla:
   - API keys valide
   - Video ancora disponibile su YouTube
   - Limiti API non superati

---

## 🔄 Pipeline di Processing

Il sistema elabora ogni video in **4 step sequenziali**:

### Step 1: Importazione YouTube (30-60s)

```
✅ Recupera metadati video
✅ Download thumbnail HD
✅ Imposta featured image
✅ Salva tag e categoria
✅ Crea post bozza
```

**Meta salvati**: `_ipv_video_id`, `_ipv_video_title`, `_ipv_duration`, `_ipv_view_count`, etc.

### Step 2: Generazione Trascrizione (60-300s)

```
🔹 Prova native captions (YouTube)
   ↓ Se non disponibili
🔹 Genera con AI (SupaData)
   ↓ Job asincrono
🔹 Polling fino a completamento
   ↓
✅ Salva trascrizione formattata
```

**Meta salvati**: `_ipv_transcript`, `_ipv_transcript_stats`

### Step 3: Generazione Contenuti AI (60-120s)

```
🤖 Invia prompt + trascrizione a OpenAI
🤖 Modello: GPT-4 Turbo
🤖 Genera 15 sezioni strutturate:
   1. Titolo SEO
   2. Descrizione Breve
   3. Sponsor (Biovital)
   4. Timestamp Capitoli
   5. Argomenti Trattati
   6. Ospiti
   7. Citazioni Rilevanti
   8. Riferimenti & Fonti
   9. Temi Correlati
   10. Collegamenti Video
   11. Link Approfondimento
   12. Social Media
   13. Link Utili
   14. Disclaimer
   15. Hashtag
```

**Meta salvati**: `_ipv_ai_content_full`, `_ipv_ai_title`, `_ipv_ai_timestamps`, etc.

### Step 4: Finalizzazione (5-10s)

```
✅ Compila post content completo
✅ Aggiorna titolo post
✅ Imposta excerpt
✅ Aggiunge hashtag come tag
✅ Pubblica (se auto-publish attivo)
✅ Marca come completato
```

**Tempo totale stimato**: 3-8 minuti per video

---

## 🔌 API Integrate

### YouTube Data API v3

**Endpoint**: `videos?part=snippet,contentDetails,statistics`

**Quota**: 10,000 unità/giorno (1 video = 1 unità)

**Rate Limit**: ~100 richieste/secondo

### SupaData Transcription API

**Endpoints**:
- POST `/v1/transcribe` (native/generate)
- GET `/v1/jobs/{job_id}` (polling)

**Rate Limit**: Dipende dal piano

**Timeout**: 300s (configurabile)

### OpenAI API

**Endpoint**: `/v1/chat/completions`

**Modello Consigliato**: `gpt-4-turbo-preview`

**Token Limit**: 4,000 token output

**Rate Limit**: Dipende dal tier account

---

## 🛠️ Troubleshooting

### Errore: "YouTube API key non configurata"

**Soluzione**: Vai in Impostazioni → API Keys e inserisci la chiave YouTube

### Errore: "Sottotitoli nativi non disponibili"

**Causa**: Il video YouTube non ha sottotitoli caricati

**Soluzione**:
- Cambia modalità trascrizione su "Automatica" o "Sempre Generata"
- SupaData genererà la trascrizione AI automaticamente

### Errore: "Timeout durante generazione trascrizione"

**Causa**: Video molto lungo (>1 ora)

**Soluzione**: Aumenta timeout in Impostazioni → Trascrizione (es: 600s)

### Errore: "OpenAI API error: Rate limit exceeded"

**Causa**: Troppe richieste simultanee

**Soluzione**: Riduci "Dimensione Batch" in Impostazioni (es: da 5 a 2)

### Il cron non processa i video

**Verifica**:
```php
// Controlla se il cron è schedulato
wp_next_scheduled('ipv_pro_process_queue');
```

**Soluzione**:
1. Disattiva e riattiva il plugin
2. Verifica che WP Cron sia abilitato (`DISABLE_WP_CRON !== true`)
3. Usa cron system invece di WP Cron (avanzato)

### Post rimane in "Bozza" invece di pubblicare

**Causa**: Auto-publish disabilitato

**Soluzione**: Vai in Impostazioni → Importazione → ✅ Pubblicazione Automatica

---

## ❓ FAQ

### Posso usare il plugin per altri canali YouTube?

Sì! Il plugin funziona con qualsiasi video YouTube pubblico. Basta modificare:
- Prompt OpenAI nelle impostazioni (rimpiazza riferimenti a "Il Punto di Vista")
- Sponsor e link social nel prompt

### Quanto costa processare 100 video?

**Stima**:
- YouTube API: Gratis
- SupaData: $10-30 (trascrizioni)
- OpenAI GPT-4: $50-150 (generazione contenuti)

**Totale**: $60-180 per 100 video

### Posso modificare il prompt OpenAI?

Sì! Il prompt è nel file `includes/class-openai-api.php` metodo `build_ultra_strict_prompt()`.

Puoi personalizzare:
- Sponsor default
- Link social
- Struttura sezioni
- Stile e tono

### Il plugin supporta video privati?

No, solo video pubblici. I video privati non sono accessibili via API.

### Posso importare playlist intere?

La versione corrente supporta solo URL singoli. Per playlist:
1. Estrai gli URL con uno script
2. Usa bulk import

**Feature futura**: Import diretto playlist in v2.1

### Come cancello tutti i video dalla coda?

```sql
-- Backup prima!
TRUNCATE TABLE wp_ipv_processing_queue;
```

Poi vai in Video Manager e rimuovi manualmente o usa bulk delete.

---

## 📊 Database Schema

### Tabella `wp_ipv_processing_queue`

```sql
CREATE TABLE wp_ipv_processing_queue (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    video_url varchar(500) NOT NULL,
    status varchar(50) NOT NULL DEFAULT 'pending',
    current_step varchar(100) DEFAULT NULL,
    error_message text DEFAULT NULL,
    retry_count int(11) DEFAULT 0,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY status (status)
);
```

---

## 🔐 Sicurezza

- ✅ Nonce verification su tutti gli AJAX endpoint
- ✅ Capability check (`manage_options`)
- ✅ Input sanitization con `sanitize_text_field()`
- ✅ Output escaping con `esc_html()`, `esc_attr()`, `esc_url()`
- ✅ Prepared statements per database queries
- ✅ API keys non esposte nel frontend

---

## 📝 Changelog

### v2.0.0 (2025-01-12)

**🎉 Release Iniziale**

- ✅ Pipeline completa YouTube → SupaData → OpenAI
- ✅ Queue system con retry automatico
- ✅ Dashboard moderna con statistiche
- ✅ Video Manager con stati dettagliati
- ✅ Settings page con test API
- ✅ Bulk import fino a 50 video
- ✅ Prompt ultra-rigoroso v1.9.1 per "Il Punto di Vista"
- ✅ Mappatura categorie YouTube → WordPress
- ✅ Auto-publish configurabile
- ✅ Documentazione completa

---

## 👤 Autore

**Daniele**
Sviluppato per **Il Punto di Vista** - Adrian Fiorelli

📧 Email: [contatti disponibili nel sito]
🌐 Sito: https://ilpuntodivista.it

---

## 📄 Licenza

GPL-2.0+ - Free to use and modify

---

## 🙏 Crediti

- **YouTube Data API v3** by Google
- **SupaData API** by SupaData.ai
- **OpenAI GPT-4** by OpenAI
- **WordPress** by Automattic

---

**Made with ❤️ for Il Punto di Vista**
