# Claude WooCommerce AI - RAG Sales Agent

Sistema avanzato di assistente AI per WooCommerce con **RAG (Retrieval-Augmented Generation)** che integra Claude (Anthropic) e OpenAI per offrire un'esperienza di vendita assistita intelligente e personalizzata.

## 🌟 Caratteristiche Principali

### Sistema RAG (Retrieval-Augmented Generation)
- **Indicizzazione automatica** dei prodotti WooCommerce con embeddings vettoriali
- **Ricerca semantica** avanzata per trovare prodotti rilevanti
- **Risposte contestuali** basate sul catalogo reale del negozio
- **Aggiornamento dinamico** dell'indice quando vengono aggiunti nuovi prodotti

### Intelligenza Artificiale Dual-Provider
- **OpenAI (GPT-4o-mini)**: Veloce ed economico per conversazioni quotidiane
- **Anthropic (Claude 3.5 Sonnet)**: Ragionamento avanzato e risposte più elaborate
- Cambio dinamico tra provider AI in base alle esigenze

### Gestione Conversazioni Avanzata
- **Storico completo** di tutte le conversazioni
- **Analisi del sentiment** automatica (positivo/neutro/negativo)
- **Sistema di escalation** agli operatori umani
- **Analytics dettagliati** con grafici e statistiche

### Personalizzazione Totale
- **Template di prompt** personalizzabili per diversi scenari
- **Risposte rapide** configurabili
- **Temi e colori** personalizzabili
- **Posizionamento widget** flessibile

## 📋 Requisiti

- WordPress 5.7 o superiore
- WooCommerce 5.0 o superiore
- PHP 7.4 o superiore
- MySQL 5.6 o superiore
- **Chiave API OpenAI** (obbligatoria per RAG ed embeddings)
- **Chiave API Anthropic** (opzionale, per usare Claude)

## 🚀 Installazione

### 1. Carica il Plugin

```bash
# Via WP-CLI
wp plugin install claude-woocommerce-ai-rag.zip --activate

# Oppure manualmente
1. Scarica il plugin
2. Vai in WordPress Admin > Plugin > Aggiungi nuovo
3. Clicca "Carica plugin"
4. Seleziona il file ZIP
5. Attiva il plugin
```

### 2. Configura le API Key

1. Vai in **Claude AI RAG > Impostazioni**
2. Inserisci la tua **OpenAI API Key**
   - Ottienila da: https://platform.openai.com/api-keys
   - Clicca "Test Connessione" per verificare
3. (Opzionale) Inserisci la **Anthropic API Key**
   - Ottienila da: https://console.anthropic.com/
   - Seleziona "Anthropic" come AI preferita se vuoi usare Claude

### 3. Indicizza i Prodotti (RAG)

1. Vai in **Claude AI RAG > RAG System**
2. Clicca su **"Indicizza Prodotti"**
3. Attendi il completamento (può richiedere alcuni minuti per cataloghi grandi)
4. Verifica le statistiche per confermare l'indicizzazione

**Nota**: L'indicizzazione è necessaria per il funzionamento ottimale del RAG. Senza indicizzazione, il sistema userà la ricerca standard di WooCommerce.

### 4. Configura l'Aspetto

1. Vai in **Claude AI RAG > Aspetto**
2. Personalizza:
   - Titolo del widget chat
   - Colori e tema
   - Posizione del widget
   - Placeholder del messaggio

### 5. Configura i Prompt (Opzionale)

1. Vai in **Claude AI RAG > Prompt Templates**
2. Personalizza i prompt per:
   - Conversazioni generali
   - Domande sui prodotti
   - Supporto ordini
   - Gestione reclami
3. Configura le risposte rapide

## 🎯 Utilizzo

### Mostrare la Chat sul Sito

#### Opzione 1: Widget Automatico (Raccomandato)
Il widget appare automaticamente in basso a destra (o sinistra) su tutte le pagine.

#### Opzione 2: Shortcode
Usa lo shortcode `[cwau_chat]` in qualsiasi pagina o post:

```
[cwau_chat]
```

#### Opzione 3: Codice PHP
Inserisci nel tuo tema:

```php
<?php echo do_shortcode('[cwau_chat]'); ?>
```

### Come Funziona il RAG

1. **Indicizzazione**:
   - I prodotti WooCommerce vengono convertiti in embeddings vettoriali
   - Gli embeddings catturano il significato semantico di titolo, descrizione, categoria, attributi, ecc.

2. **Query Utente**:
   - Quando l'utente fa una domanda, viene creato un embedding della query
   - Il sistema cerca i prodotti più simili usando similarità coseno

3. **Generazione Risposta**:
   - I prodotti rilevanti vengono forniti all'AI come contesto
   - L'AI genera una risposta informata e accurata basata sui prodotti reali

**Esempio**:
```
Utente: "Cerco un telefono economico con buona fotocamera"

Sistema RAG:
1. Crea embedding della query
2. Trova i 5 prodotti più simili (es. smartphone economici)
3. Fornisce all'AI: nome, prezzo, caratteristiche di questi prodotti
4. L'AI risponde suggerendo i prodotti più adatti con link diretti
```

### Gestione Escalation

#### Escalation Automatica
Configura parole chiave che attivano automaticamente l'escalation:

1. Vai in **Claude AI RAG > Operatori**
2. Aggiungi parole chiave (una per riga):
   ```
   rimborso
   problema grave
   manager
   responsabile
   operatore
   ```
3. Configura l'email dell'operatore

#### Escalation Manuale
L'utente può chiedere di parlare con un operatore in qualsiasi momento.

## 📊 Analytics e Monitoraggio

### Dashboard Analytics
Vai in **Claude AI RAG > Analytics** per vedere:

- **Conversazioni oggi**: Numero di chat iniziate oggi
- **Messaggi totali**: Volume totale di messaggi
- **Escalazioni attive**: Conversazioni che richiedono attenzione
- **Sentiment medio**: Analisi dell'umore dei clienti
- **Grafico conversazioni**: Trend degli ultimi 7 giorni

### Storico Conversazioni
Vai in **Claude AI RAG > Conversazioni** per:

- Visualizzare tutte le conversazioni
- Filtrare per status e sentiment
- Leggere i messaggi completi
- Esportare in CSV per analisi esterne

## 🔧 Configurazione Avanzata

### Ottimizzazione Prompt

I prompt sono il cuore del sistema. Ecco alcuni suggerimenti:

#### Prompt Default (Generale)
```
Sei un assistente esperto di e-commerce per [Nome Negozio].
Aiuta i clienti con domande su prodotti, ordini e servizi.
Sii professionale, amichevole e conciso.
Se non conosci la risposta, ammettilo onestamente.
Quando suggerisci prodotti, includi sempre il link diretto.
```

#### Prompt Product Inquiry
```
Focus sui dettagli tecnici e benefici pratici del prodotto.
Confronta con prodotti simili se rilevante.
Suggerisci prodotti complementari.
Evidenzia promozioni o sconti attivi.
```

### Parametri RAG

- **Top-K**: Numero di prodotti da recuperare (default: 5)
  - Più basso (3): Risposte più mirate ma meno contesto
  - Più alto (10): Più contesto ma risposte potenzialmente meno focalizzate

### Performance e Costi

#### Costi API stimati (OpenAI)

**Embeddings (text-embedding-3-small)**:
- Costo: $0.02 / 1M tokens
- 100 prodotti ≈ 50,000 tokens ≈ $0.001
- 1,000 prodotti ≈ 500,000 tokens ≈ $0.01

**Chat (GPT-4o-mini)**:
- Costo: $0.15 / 1M input tokens, $0.60 / 1M output tokens
- Conversazione media (10 messaggi): ≈ 5,000 tokens ≈ $0.003
- 1,000 conversazioni/mese ≈ $3

**Totale stimato per negozio medio (1,000 prodotti, 1,000 conversazioni/mese): ~$5/mese**

## 🔒 Sicurezza

- **Nonce verification** su tutte le richieste AJAX
- **Capability checks** per funzioni admin
- **Input sanitization** su tutti i dati utente
- **Prepared statements** per query database
- **API keys** salvate in modo sicuro (non esposte nel frontend)

## 🐛 Troubleshooting

### Il RAG non trova prodotti
1. Verifica che i prodotti siano indicizzati (vai in RAG System)
2. Controlla che l'API Key OpenAI sia valida
3. Verifica i log di errore in `/wp-content/debug.log`

### La chat non risponde
1. Testa la connessione API (Impostazioni > Test Connessione)
2. Verifica la console JavaScript del browser (F12)
3. Controlla che WooCommerce sia attivo

### Errore "API Key non configurata"
1. Vai in Impostazioni
2. Inserisci una API Key valida
3. Salva le impostazioni

### Performance lente
1. Riduci il valore Top-K (es. da 5 a 3)
2. Considera l'uso di caching (plugin come WP Rocket)
3. Per cataloghi molto grandi (>10,000 prodotti), considera hosting dedicato

## 📚 API e Webhook

### Hook WordPress Disponibili

```php
// Dopo l'invio di un messaggio
do_action('cwau_message_sent', $conversation_id, $message, $sender);

// Dopo la creazione di una conversazione
do_action('cwau_conversation_created', $conversation_id, $session_id);

// Dopo un'escalation
do_action('cwau_escalation_created', $escalation_id, $conversation_id);

// Filtro per modificare la risposta AI
apply_filters('cwau_ai_response', $response, $user_message, $context);

// Filtro per modificare il contesto RAG
apply_filters('cwau_rag_context', $context, $query, $products);
```

### Esempio Personalizzazione

```php
// functions.php del tema
add_filter('cwau_ai_response', function($response, $user_message, $context) {
    // Aggiungi firma personalizzata
    return $response . "\n\n— Team " . get_bloginfo('name');
}, 10, 3);
```

## 🤝 Supporto

### Documentazione
- Visita il wiki del progetto: [link]
- API Reference: [link]

### Segnalazione Bug
Apri una issue su GitHub con:
- Versione WordPress
- Versione WooCommerce
- Versione PHP
- Descrizione del problema
- Log di errore

### Feature Request
Suggerisci nuove funzionalità aprendo una issue con tag `enhancement`.

## 📄 Licenza

Questo plugin è rilasciato sotto licenza GPLv3 o successiva.

## 🙏 Crediti

- **OpenAI** per le API GPT e Embeddings
- **Anthropic** per Claude API
- **WooCommerce** per l'integrazione e-commerce
- **WordPress** per la piattaforma

---

## 🎨 Screenshots

### Admin Dashboard
![Dashboard](screenshots/dashboard.png)

### RAG System
![RAG](screenshots/rag-system.png)

### Chat Widget
![Chat](screenshots/chat-widget.png)

### Analytics
![Analytics](screenshots/analytics.png)

---

Sviluppato con ❤️ per la community WordPress & WooCommerce

**Version**: 3.0.0
**Requires PHP**: 7.4+
**Requires WordPress**: 5.7+
**Tested up to**: 6.6
**Last Updated**: 2025
