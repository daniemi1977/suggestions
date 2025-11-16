# 📦 Installazione Claude WooCommerce AI - RAG Sales Agent

## 🎯 Requisiti di Sistema

- **WordPress**: 5.7 o superiore
- **WooCommerce**: 5.0 o superiore
- **PHP**: 7.4 o superiore
- **MySQL**: 5.7 o superiore
- **RAM**: Minimo 512MB (consigliato 1GB+)

## 📥 Installazione Rapida

### Metodo 1: Upload tramite WordPress Admin (CONSIGLIATO)

1. **Accedi al pannello WordPress**
   - Vai su `Plugin` → `Aggiungi nuovo`
   - Clicca su `Carica plugin`

2. **Carica il file ZIP**
   - Seleziona il file `claude-woocommerce-ai-rag-complete.zip`
   - Clicca su `Installa ora`

3. **Attiva il plugin**
   - Dopo l'installazione, clicca su `Attiva plugin`

### Metodo 2: Upload via FTP/SFTP

1. **Estrai il file ZIP**
   - Estrai `claude-woocommerce-ai-rag-complete.zip` sul tuo computer

2. **Upload via FTP**
   - Connettiti al tuo server via FTP/SFTP
   - Vai nella cartella `wp-content/plugins/`
   - Carica l'intera cartella `claude-woocommerce-ai-rag`

3. **Attiva il plugin**
   - Nel pannello WordPress, vai su `Plugin`
   - Trova "Claude WooCommerce AI - RAG Sales Agent"
   - Clicca su `Attiva`

## ⚙️ Configurazione Iniziale

### 1. Configura le API Keys

Vai su **WordPress Admin** → **Claude AI RAG** → **Impostazioni**

#### OpenAI Configuration:
```
API Key: sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Modello: gpt-4o-mini (default)
```

Per ottenere la chiave OpenAI:
- Vai su https://platform.openai.com/api-keys
- Crea un nuovo API key
- Copia e incolla nelle impostazioni

#### Anthropic Claude Configuration (opzionale):
```
API Key: sk-ant-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Modello: claude-3-5-sonnet-20241022 (default)
```

Per ottenere la chiave Claude:
- Vai su https://console.anthropic.com/settings/keys
- Crea un nuovo API key
- Copia e incolla nelle impostazioni

### 2. Configura il RAG (Retrieval-Augmented Generation)

1. **Vai su** `Claude AI RAG` → `RAG Settings`
2. **Abilita RAG**: Spunta "Enable RAG"
3. **Configura parametri**:
   - Top-K Results: 5 (default)
   - Embedding Model: text-embedding-3-small
4. **Indicizza prodotti**: Clicca su "Index All Products"
   - Questo processo indicizzerà tutti i tuoi prodotti WooCommerce
   - Può richiedere alcuni minuti per cataloghi grandi

### 3. Abilita la Chat Widget

1. **Vai su** `Claude AI RAG` → `Chat Settings`
2. **Abilita chat**: Spunta "Enable Chat Widget"
3. **Configura aspetto**:
   - Titolo: "Come posso aiutarti?" (personalizzabile)
   - Posizione: Bottom Right / Bottom Left
   - Colore principale: #2563eb (personalizzabile)
4. **Salva le modifiche**

### 4. Setup Database (Automatico)

Il plugin creerà automaticamente 12 tabelle MySQL alla prima attivazione:
- ✅ `wp_cwau_conversations`
- ✅ `wp_cwau_messages`
- ✅ `wp_cwau_escalations`
- ✅ `wp_cwau_embeddings`
- ✅ `wp_cwau_customers`
- ✅ `wp_cwau_deals`
- ✅ `wp_cwau_activities`
- ✅ `wp_cwau_tickets`
- ✅ `wp_cwau_ticket_replies`
- ✅ `wp_cwau_operator_sessions`
- ✅ `wp_cwau_workflows`
- ✅ `wp_cwau_notifications`

Non è richiesta alcuna configurazione manuale del database.

## 🎨 Personalizzazione Chat Widget

### Inserire la chat in una pagina specifica

Usa lo shortcode:
```
[cwau_chat]
```

Esempio in una pagina:
```html
<h2>Hai bisogno di aiuto?</h2>
<p>Chatta con il nostro assistente AI:</p>
[cwau_chat]
```

### Widget automatico (footer)

Il widget appare automaticamente nel footer se abilitato nelle impostazioni.

## 👥 Configurazione Operatori

### 1. Assegna ruoli Operatore

Gli utenti con questi ruoli possono usare la dashboard operatore:
- **Administrator** (accesso completo)
- **Shop Manager** (accesso completo)

### 2. Accedi alla Dashboard Operatore

1. **Login come admin/shop manager**
2. **Vai su** `Live Chat` nel menu WordPress admin
3. **Imposta il tuo stato**: Online/Away/Offline
4. **Inizia a gestire le chat!**

## 🔌 Integrazioni Esterne (Opzionali)

### HubSpot Integration

1. **Vai su** `Claude AI RAG` → `Integrations` → `HubSpot`
2. **Inserisci API Key**:
   - Ottieni la chiave da: https://app.hubspot.com/settings/account/integrations/api-keys
3. **Test connessione**: Clicca "Test Connection"
4. **Abilita sync automatico**

### Zendesk Integration

1. **Vai su** `Claude AI RAG` → `Integrations` → `Zendesk`
2. **Configura**:
   - Subdomain: your-company (da yourcompany.zendesk.com)
   - Email: admin@tuodominio.com
   - API Token: (da Zendesk Admin → Channels → API)
3. **Test connessione**

### Freshdesk Integration

1. **Vai su** `Claude AI RAG` → `Integrations` → `Freshdesk`
2. **Configura**:
   - Domain: your-company (da yourcompany.freshdesk.com)
   - API Key: (da Freshdesk Profile → API Settings)
3. **Test connessione**

## 🤖 Creazione Workflows Automatici

### Esempio: Auto-escalation per clienti VIP

1. **Vai su** `Claude AI RAG` → `Automation` → `Create Workflow`
2. **Nome**: "VIP Auto-Escalation"
3. **Trigger**: "Lead Score Threshold" → 80
4. **Actions**:
   - Create Ticket (Priority: Urgent)
   - Notify Operator (ID operatore senior)
   - Send Email (al manager)
5. **Salva e attiva**

### Esempio: Abandoned Cart Recovery

1. **Trigger**: "Cart Abandoned" → 24 hours
2. **Actions**:
   - Send Email (template con sconto 10%)
   - Send Chat Message ("Hai lasciato articoli nel carrello!")
   - Update Lead Score (+5 points)

## 📊 Dashboard e Reporting

### Accedere alle Analytics

1. **Vai su** `Live Chat` → `Analytics`
2. **Visualizza metriche**:
   - Chat volume by day/hour
   - Sentiment trend
   - Conversion funnel
   - Operator performance
   - SLA compliance
   - CSAT scores

### Export Dati CSV

1. **Seleziona tipo report**: Conversations / Tickets / Customers / Operators
2. **Seleziona periodo**: Today / Week / Month / Quarter / Year
3. **Clicca "Export CSV"**
4. **Download automatico del file**

## 🔧 Troubleshooting

### Il widget chat non appare

1. ✅ Verifica che il plugin sia attivato
2. ✅ Vai su `Chat Settings` e abilita "Enable Chat Widget"
3. ✅ Svuota cache WordPress e browser
4. ✅ Verifica che WooCommerce sia attivo

### Errore API OpenAI

```
Error: Invalid API key
```

**Soluzione**:
1. Verifica la chiave API in `Claude AI RAG` → `Settings`
2. Testa la connessione: `Test OpenAI Connection`
3. Assicurati di avere credito sul tuo account OpenAI

### Prodotti non trovati nella ricerca RAG

1. **Vai su** `Claude AI RAG` → `RAG Settings`
2. **Clicca "Clear Index"** (rimuove tutti gli embeddings)
3. **Clicca "Reindex All Products"** (re-indicizza tutto)
4. **Attendi completamento** (può richiedere minuti per cataloghi grandi)

### Operator Dashboard non funziona

1. ✅ Verifica di essere loggato come Administrator o Shop Manager
2. ✅ Vai su `Live Chat` nel menu admin
3. ✅ Imposta stato su "Online"
4. ✅ Controlla la console browser per errori JavaScript

### Database tables non create

Se alla attivazione le tabelle non vengono create:

1. **Disattiva e riattiva il plugin**
2. **Verifica i permessi MySQL**: l'utente deve avere privilegi CREATE TABLE
3. **Controlla error log WordPress**: `wp-content/debug.log`

## 🚀 Performance Optimization

### Caching

Il plugin è compatibile con:
- ✅ WP Rocket
- ✅ W3 Total Cache
- ✅ WP Super Cache
- ✅ LiteSpeed Cache

**Importante**: Escludi dalla cache:
- `/wp-admin/admin-ajax.php?action=cwau_*`
- Cookie `cwau_session_*`

### Database Optimization

Per cataloghi molto grandi (10,000+ prodotti):

1. **Aggiungi indici custom** (opzionale):
```sql
ALTER TABLE wp_cwau_embeddings ADD INDEX idx_product_id (product_id);
ALTER TABLE wp_cwau_conversations ADD INDEX idx_session (session_id);
ALTER TABLE wp_cwau_messages ADD INDEX idx_conversation (conversation_id);
```

2. **Cleanup periodico** (opzionale, via phpMyAdmin o SQL):
```sql
-- Elimina conversazioni vecchie (> 90 giorni)
DELETE FROM wp_cwau_conversations WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Elimina embeddings orfani (prodotti cancellati)
DELETE e FROM wp_cwau_embeddings e
LEFT JOIN wp_posts p ON e.product_id = p.ID
WHERE p.ID IS NULL;
```

### PHP Memory Limit

Per cataloghi molto grandi, aumenta il limite in `wp-config.php`:

```php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

## 📧 Supporto e Documentazione

### Documentazione Completa

- **README.md**: Overview del progetto
- **CHANGELOG.md**: Registro modifiche
- **CRM_TICKETING_ANALYSIS.md**: Analisi competitor CRM/Ticketing
- **COMPETITOR_ANALYSIS.md**: Analisi competitor Chat AI

### Struttura Codebase

```
claude-woocommerce-ai-rag/
├── includes/              # Core PHP classes
│   ├── class-database.php
│   ├── class-rag.php
│   ├── class-chat.php
│   ├── class-crm.php
│   ├── class-tickets.php
│   ├── class-live-chat.php
│   ├── class-automation.php
│   ├── class-integrations.php
│   └── class-reporting.php
├── assets/               # CSS/JS frontend
│   ├── chat-frontend-enhanced.css
│   ├── chat-frontend-enhanced.js
│   ├── operator-dashboard.css
│   └── operator-dashboard.js
└── languages/           # Traduzioni i18n
```

## 🔐 Sicurezza

### Best Practices Implementate

✅ **Nonce verification** su tutte le richieste AJAX
✅ **Capability checks** (manage_woocommerce)
✅ **Prepared statements** per query SQL
✅ **Input sanitization** (sanitize_text_field, sanitize_email, etc.)
✅ **Output escaping** (esc_html, esc_url, esc_attr)
✅ **HTTPS** consigliato per API keys

### Permessi File (via SSH)

```bash
# Imposta permessi corretti
chmod 755 /wp-content/plugins/claude-woocommerce-ai-rag
chmod 644 /wp-content/plugins/claude-woocommerce-ai-rag/*.php
chmod 644 /wp-content/plugins/claude-woocommerce-ai-rag/includes/*.php
```

## 📋 Checklist Post-Installazione

- [ ] Plugin attivato
- [ ] WooCommerce attivo e funzionante
- [ ] OpenAI API Key configurata e testata
- [ ] (Opzionale) Claude API Key configurata
- [ ] RAG abilitato e prodotti indicizzati
- [ ] Chat widget abilitato e visibile sul frontend
- [ ] Testata una conversazione chat
- [ ] Dashboard operatore accessibile
- [ ] (Opzionale) Integrazioni esterne configurate
- [ ] (Opzionale) Workflows automatici creati

## 🎉 Congratulazioni!

Il tuo sistema AI RAG Sales Agent è ora completamente operativo! 🚀

**Prossimi passi suggeriti:**
1. Testa il sistema con conversazioni reali
2. Configura workflows automatici per il tuo business
3. Integra con HubSpot/Zendesk se necessario
4. Analizza le metriche nella dashboard Analytics
5. Forma i tuoi operatori all'uso della Live Chat Dashboard

**Buon utilizzo! 🎯**
