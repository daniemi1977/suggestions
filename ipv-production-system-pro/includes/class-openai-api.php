<?php
/**
 * OpenAI API Integration
 *
 * Generates optimized content from transcripts using GPT-4
 * with ultra-strict prompt for "Il Punto di Vista"
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_OpenAI_API {

    /**
     * API endpoint
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Get API key from settings
     */
    private function get_api_key() {
        return get_option('ipv_pro_openai_api_key', '');
    }

    /**
     * Get model from settings
     */
    private function get_model() {
        return get_option('ipv_pro_openai_model', 'gpt-4-turbo-preview');
    }

    /**
     * Generate AI content from transcript
     *
     * @param string $transcript Video transcript
     * @param array $video_data Additional video metadata
     * @return array|WP_Error Generated content sections or error
     */
    public function generate_content($transcript, $video_data = []) {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'OpenAI API key non configurata');
        }

        if (empty($transcript)) {
            return new WP_Error('no_transcript', 'Trascrizione vuota');
        }

        // Build the prompt using dynamic configuration
        $prompt = IPV_Prompt_Builder::build_prompt($transcript, $video_data);

        // Get channel config for system message
        $config = IPV_Channel_Config::get_config();
        $themes_short = is_array($config['channel_themes'])
            ? implode(', ', array_slice($config['channel_themes'], 0, 3))
            : $config['channel_themes'];

        // Call OpenAI API
        $response = wp_remote_post($this->api_endpoint, [
            'timeout' => 120,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => $this->get_model(),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "Sei un esperto content writer per \"{$config['channel_name']}\", canale YouTube di {$config['channel_owner']} specializzato in {$themes_short}."
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 4000
            ])
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Errore connessione API: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($response_code !== 200) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Errore sconosciuto';
            return new WP_Error('api_error', 'OpenAI API error: ' . $error_message);
        }

        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', 'Risposta API non valida');
        }

        $generated_content = $data['choices'][0]['message']['content'];

        // Parse the generated content into sections
        return $this->parse_generated_content($generated_content);
    }

    /**
     * Build ultra-strict prompt v2.0 - Dynamic Multi-Channel Support
     * Uses channel configuration for personalization
     */
    private function build_ultra_strict_prompt($transcript, $video_data) {
        $video_title = isset($video_data['title']) ? $video_data['title'] : 'Video';

        // Load channel configuration
        $config = IPV_Channel_Config::get_config();

        // Build dynamic themes list
        $themes_list = is_array($config['channel_themes'])
            ? implode("\n", array_map(function($theme) { return "- $theme"; }, $config['channel_themes']))
            : "- " . $config['channel_themes'];

        $prompt = <<<PROMPT
# PROMPT ULTRA-RIGOROSO v2.0 - {$config['channel_name']}

Genera una descrizione COMPLETA e PROFESSIONALE per il video YouTube del canale "{$config['channel_name']}" di {$config['channel_owner']}.

## CONTESTO CANALE
"{$config['channel_name']}" è un canale YouTube dedicato a:
{$themes_list}
- **Esoterismo** – Tradizioni occulte e conoscenze nascoste
- **Spiritualità** – Ricerca interiore e crescita personale
- **Misteri** – Enigmi storici e archeologia alternativa
- **Geopolitica** – Analisi critica degli eventi mondiali
- **Divulgazione alternativa** – Informazione fuori dagli schemi

**Stile:** Approfondito, rigoroso, senza sensazionalismo. Tono professionale ma accessibile.

---

## TRASCRIZIONE VIDEO
📺 **Titolo episodio:** {$video_title}

{$transcript}

---

## ISTRUZIONI FONDAMENTALI

🔴 **REGOLE ASSOLUTE:**
1. **NON inventare** informazioni non presenti nella trascrizione
2. **NON aggiungere** dettagli fantasiosi o speculativi
3. **USA SOLO** ciò che è effettivamente detto nel video
4. **MANTIENI** il tono professionale e rigoroso del canale
5. **EVITA** clickbait, sensazionalismo, esagerazioni
6. **INCLUDI TUTTE** le sezioni richieste, anche se con "Nessuno" o "Non presente"

---

## STRUTTURA OUTPUT (18 SEZIONI OBBLIGATORIE)

Genera il contenuto seguendo ESATTAMENTE questa struttura:

---

### 📺 TITOLO EPISODIO
- Massimo 60 caratteri
- SEO-ottimizzato per YouTube
- Accattivante ma NON clickbait
- Deve rispecchiare il contenuto reale del video

---

### 📌 DESCRIZIONE
- Max 1200 caratteri
- 2-3 paragrafi introduttivi
- Riassunto dell'argomento principale
- Stile coinvolgente e informativo
- Include parole chiave per SEO

---

### 🎁 SPONSOR
Inserisci SEMPRE questo sponsor (IMPORTANTE: link aggiornato):

**Biovital – Progetto Italia**

Biovital è un'azienda italiana leader nella produzione di integratori naturali e biologici. Con oltre 30 anni di esperienza, Biovital si impegna nella ricerca e sviluppo di prodotti per il benessere e la salute, utilizzando esclusivamente ingredienti naturali di alta qualità.

Sostieni il progetto: 👉 https://biovital-italia.com/?bio=17

---

### ⏱️ MINUTAGGIO (Timestamp)
- Crea timestamp significativi ogni 3-5 minuti
- Formato esatto: `00:00 – Intro`
- Almeno 5-8 timestamp per video standard
- Primo timestamp SEMPRE: `00:00 – Intro`
- Titoli capitoli chiari e descrittivi

**Esempio formato:**
00:00 – Intro
03:45 – Contesto storico
08:20 – Prima argomentazione
15:30 – Analisi critica
22:10 – Esempi pratici
28:45 – Considerazioni finali

---

### 📖 ARGOMENTI TRATTATI
- Lista bullet point con `–` (non `•`)
- 6-10 punti principali
- Argomenti EFFETTIVAMENTE trattati nel video
- Specifici e dettagliati
- NON generici

---

### 👤 OSPITI
- Se presenti ospiti: indicare nome completo e competenza/ruolo
- Se NON ci sono ospiti, scrivi: "Video condotto in solitaria da Adrian Fiorelli"
- Formato: `– Nome Cognome (ruolo/competenza)`

---

### 📌 PERSONE MENZIONATE
- Lista di nomi citati/menzionati nel video
- Solo se EFFETTIVAMENTE nominati nella trascrizione
- Formato: `– Nome Cognome`
- Se nessuno menzionato: "Nessuna persona menzionata"

---

### 🎥 REGIA
Inserisci SEMPRE:

**Regia:** Il Punto di Vista

---

### 🔔 EVENTI
- Eventuali eventi, corsi, incontri menzionati nel video
- Formato: `📅 [Nome Evento] – [Data] – [Modalità]`
- Includere link o info utili se presenti
- Se non ci sono eventi: "Nessun evento in programma"

---

### 💬 CITAZIONI RILEVANTI
- 3-5 citazioni testuali dal video
- Tra virgolette « »
- Le più significative e memorabili
- Solo se effettivamente dette nel video

---

### 📚 RIFERIMENTI E FONTI
- Libri, articoli, studi, documenti citati nel video
- Solo se EFFETTIVAMENTE menzionati
- Con autori e titoli quando disponibili
- Formato: `– Autore, "Titolo Opera" (Anno)`
- Se nessuno: "Nessun riferimento specifico citato"

---

### 🔗 LINK UTILI
- Link/risorse menzionati nel video
- Solo se EFFETTIVAMENTE citati
- Se nessuno: **NON includere questa sezione** (rimuoverla completamente)

---

### 🎯 TEMI CORRELATI
- 4-6 temi collegati all'argomento del video
- Per approfondimenti futuri
- Pertinenti al contenuto
- Formato: `– Tema 1`, `– Tema 2`

---

### 📹 COLLEGAMENTI AD ALTRI VIDEO
Suggerisci video correlati del canale (formato generico):
- Playlist o video su temi simili
- Formato: `– Video su [Tema correlato]`
- 3-4 suggerimenti massimo

---

### ❤️ DONAZIONI
Inserisci SEMPRE:

**Per sostenere il canale e i progetti de Il Punto di Vista basta fare una donazione a questo link:**

👉 https://paypal.me/adrianfiorelli

Grazie in anticipo a tutti i pirati 🖤🏴‍☠️

---

### 🙏 ABBONATI AL CANALE
Inserisci SEMPRE:

/ @ilpuntodivista

---

### 🌐 SOCIAL
Inserisci SEMPRE questi link esatti:

Telegram 👉 https://t.me/il_punto_divista
Facebook 👉 https://facebook.com/groups/4102938329737588
Instagram 👉 https://instagram.com/_ilpuntodivista._

---

### 📩 CONTATTI
Inserisci SEMPRE:

✉️ ilpuntodivistaredazione@gmail.com

---

### 🎯 TEMI DEL CANALE
Inserisci SEMPRE:

Esoterismo – Spiritualità – Misteri – Geopolitica – Divulgazione alternativa

---

### #️⃣ HASHTAG
- 10-15 hashtag pertinenti (fino a 30 max)
- Mix di generici e specifici
- Senza spazi
- Formato: #Esoterismo #Spiritualità #Misteri #IlPuntoDiVista #AdrianFiorelli
- Includere sempre: #IlPuntoDiVista #AdrianFiorelli

---

## LINEE GUIDA CONTENUTO

### ✅ COSA FARE
- Basarsi ESCLUSIVAMENTE sulla trascrizione
- Mantenere accuratezza fattuale al 100%
- Usare linguaggio chiaro, preciso e professionale
- Rispettare TUTTE le 18 sezioni in ordine
- Usare formattazione con emoji e grassetto
- Separare sezioni con `---`

### ❌ COSA NON FARE
- Inventare informazioni non presenti
- Aggiungere dettagli fantasiosi
- Usare tono clickbait o sensazionalistico
- Esagerare o drammatizzare
- Omettere sezioni obbligatorie
- Usare link non forniti nella trascrizione

---

## FORMATTAZIONE

- **Grassetto** per titoli sezioni (es: `### 📺 TITOLO EPISODIO`)
- Emoji appropriate per ogni sezione (già indicate)
- Liste con `–` per bullet points
- Separatori `---` tra sezioni principali
- Citazioni tra virgolette « »
- Link completi e funzionanti

---

## OUTPUT FINALE

Genera ora la descrizione completa seguendo TUTTE le 18 sezioni nell'ordine esatto.

**IMPORTANTE:**
- Ogni sezione deve essere presente
- Se una sezione non ha contenuto, indicare "Nessuno/a" o "Non presente"
- L'unica eccezione è "LINK UTILI": se non ci sono link, rimuovi completamente la sezione
- Mantieni la struttura esatta con emoji e separatori
- Usa i link esatti forniti (Biovital, PayPal, Social)

Ricorda: accuratezza, professionalità, completezza.

PROMPT;

        return $prompt;
    }

    /**
     * Parse generated content into structured sections (v1.9.4 - 18 sections)
     */
    private function parse_generated_content($content) {
        $sections = [
            'title' => '',
            'description' => '',
            'sponsor' => '',
            'timestamps' => '',
            'topics' => '',
            'guests' => '',
            'persone_menzionate' => '',
            'regia' => '',
            'eventi' => '',
            'quotes' => '',
            'references' => '',
            'links' => '',
            'related_themes' => '',
            'related_videos' => '',
            'donazioni' => '',
            'abbonati' => '',
            'social_media' => '',
            'contatti' => '',
            'temi_canale' => '',
            'hashtags' => '',
            'full_content' => $content
        ];

        // Try to extract sections using regex

        // Titolo
        if (preg_match('/### 📺 TITOLO EPISODIO.*?\n(.+?)(?=\n---|\n###)/s', $content, $matches)) {
            $sections['title'] = trim($matches[1]);
        }

        // Descrizione
        if (preg_match('/### 📌 DESCRIZIONE.*?\n(.+?)(?=\n---|\n### 🎁)/s', $content, $matches)) {
            $sections['description'] = trim($matches[1]);
        }

        // Sponsor
        if (preg_match('/### 🎁 SPONSOR.*?\n(.+?)(?=\n---|\n### ⏱️)/s', $content, $matches)) {
            $sections['sponsor'] = trim($matches[1]);
        }

        // Timestamps
        if (preg_match('/### ⏱️ MINUTAGGIO.*?\n(.+?)(?=\n---|\n### 📖)/s', $content, $matches)) {
            $sections['timestamps'] = trim($matches[1]);
        }

        // Topics
        if (preg_match('/### 📖 ARGOMENTI TRATTATI.*?\n(.+?)(?=\n---|\n### 👤)/s', $content, $matches)) {
            $sections['topics'] = trim($matches[1]);
        }

        // Guests
        if (preg_match('/### 👤 OSPITI.*?\n(.+?)(?=\n---|\n### 📌)/s', $content, $matches)) {
            $sections['guests'] = trim($matches[1]);
        }

        // Persone Menzionate
        if (preg_match('/### 📌 PERSONE MENZIONATE.*?\n(.+?)(?=\n---|\n### 🎥)/s', $content, $matches)) {
            $sections['persone_menzionate'] = trim($matches[1]);
        }

        // Regia
        if (preg_match('/### 🎥 REGIA.*?\n(.+?)(?=\n---|\n### 🔔)/s', $content, $matches)) {
            $sections['regia'] = trim($matches[1]);
        }

        // Eventi
        if (preg_match('/### 🔔 EVENTI.*?\n(.+?)(?=\n---|\n### 💬)/s', $content, $matches)) {
            $sections['eventi'] = trim($matches[1]);
        }

        // Quotes
        if (preg_match('/### 💬 CITAZIONI RILEVANTI.*?\n(.+?)(?=\n---|\n### 📚)/s', $content, $matches)) {
            $sections['quotes'] = trim($matches[1]);
        }

        // References
        if (preg_match('/### 📚 RIFERIMENTI E FONTI.*?\n(.+?)(?=\n---|\n### 🔗|\n### 🎯)/s', $content, $matches)) {
            $sections['references'] = trim($matches[1]);
        }

        // Links (optional section)
        if (preg_match('/### 🔗 LINK UTILI.*?\n(.+?)(?=\n---|\n### 🎯)/s', $content, $matches)) {
            $sections['links'] = trim($matches[1]);
        }

        // Related Themes
        if (preg_match('/### 🎯 TEMI CORRELATI.*?\n(.+?)(?=\n---|\n### 📹)/s', $content, $matches)) {
            $sections['related_themes'] = trim($matches[1]);
        }

        // Related Videos
        if (preg_match('/### 📹 COLLEGAMENTI AD ALTRI VIDEO.*?\n(.+?)(?=\n---|\n### ❤️)/s', $content, $matches)) {
            $sections['related_videos'] = trim($matches[1]);
        }

        // Donazioni
        if (preg_match('/### ❤️ DONAZIONI.*?\n(.+?)(?=\n---|\n### 🙏)/s', $content, $matches)) {
            $sections['donazioni'] = trim($matches[1]);
        }

        // Abbonati
        if (preg_match('/### 🙏 ABBONATI AL CANALE.*?\n(.+?)(?=\n---|\n### 🌐)/s', $content, $matches)) {
            $sections['abbonati'] = trim($matches[1]);
        }

        // Social Media
        if (preg_match('/### 🌐 SOCIAL.*?\n(.+?)(?=\n---|\n### 📩)/s', $content, $matches)) {
            $sections['social_media'] = trim($matches[1]);
        }

        // Contatti
        if (preg_match('/### 📩 CONTATTI.*?\n(.+?)(?=\n---|\n### 🎯)/s', $content, $matches)) {
            $sections['contatti'] = trim($matches[1]);
        }

        // Temi Canale
        if (preg_match('/### 🎯 TEMI DEL CANALE.*?\n(.+?)(?=\n---|\n### #️⃣)/s', $content, $matches)) {
            $sections['temi_canale'] = trim($matches[1]);
        }

        // Hashtags
        if (preg_match('/### #️⃣ HASHTAG.*?\n(.+?)(?=\n---|\n##|$)/s', $content, $matches)) {
            $sections['hashtags'] = trim($matches[1]);
        }

        return $sections;
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'API key non configurata'
            ];
        }

        // Simple test request
        $response = wp_remote_post($this->api_endpoint, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'model' => $this->get_model(),
                'messages' => [
                    ['role' => 'user', 'content' => 'Test connection. Reply with OK.']
                ],
                'max_tokens' => 10
            ])
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Errore connessione: ' . $response->get_error_message()
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code !== 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : 'Errore sconosciuto';

            return [
                'success' => false,
                'message' => $error_message
            ];
        }

        return [
            'success' => true,
            'message' => 'Connessione riuscita! API key valida.'
        ];
    }

    /**
     * Get token usage statistics
     */
    public function estimate_tokens($text) {
        // Rough estimation: ~4 characters per token
        return ceil(strlen($text) / 4);
    }
}
