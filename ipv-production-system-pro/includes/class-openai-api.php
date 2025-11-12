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

        // Build the prompt
        $prompt = $this->build_ultra_strict_prompt($transcript, $video_data);

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
                        'content' => 'Sei un esperto content writer per "Il Punto di Vista", canale YouTube di Adrian Fiorelli specializzato in spiritualità, esoterismo, misteri e pensiero critico.'
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
     * Build ultra-strict prompt v1.9.1 for "Il Punto di Vista"
     */
    private function build_ultra_strict_prompt($transcript, $video_data) {
        $video_title = isset($video_data['title']) ? $video_data['title'] : 'Video';

        $prompt = <<<PROMPT
# PROMPT ULTRA-RIGOROSO v1.9.1 - Il Punto di Vista

Genera una descrizione COMPLETA e PROFESSIONALE per il video YouTube del canale "Il Punto di Vista" di Adrian Fiorelli.

## CONTESTO CANALE
"Il Punto di Vista" è un canale YouTube dedicato a:
- Spiritualità e ricerca interiore
- Esoterismo e tradizioni antiche
- Misteri storici e archeologia alternativa
- Pensiero critico e consapevolezza
- Geopolitica e analisi del presente

**Stile:** Approfondito, rigoroso, senza sensazionalismo. Tono professionale ma accessibile.

---

## TRASCRIZIONE VIDEO
Titolo: {$video_title}

{$transcript}

---

## ISTRUZIONI GENERALI

1. **NON inventare** informazioni non presenti nella trascrizione
2. **NON aggiungere** dettagli fantasiosi o speculativi
3. **USA SOLO** ciò che è effettivamente detto nel video
4. **MANTIENI** il tono professionale e rigoroso del canale
5. **EVITA** clickbait, sensazionalismo, esagerazioni

---

## STRUTTURA OUTPUT RICHIESTA

Genera il contenuto seguendo ESATTAMENTE questa struttura con 15 sezioni:

### 1. TITOLO SEO-FRIENDLY
- Massimo 60 caratteri
- Accattivante ma NON clickbait
- Deve rispecchiare il contenuto reale

### 2. DESCRIZIONE BREVE
- 2-3 righe introduttive
- Riassunto dell'argomento principale
- Stile coinvolgente

### 3. SPONSOR
Inserisci SEMPRE questo sponsor di default:

🌿 **Sponsor del video:** Biovital – Progetto Italia

Biovital è un'azienda italiana leader nella produzione di integratori naturali e biologici. Con oltre 30 anni di esperienza, Biovital si impegna nella ricerca e sviluppo di prodotti per il benessere e la salute, utilizzando esclusivamente ingredienti naturali di alta qualità.

🔗 **Scopri i prodotti Biovital:** https://www.biovital.it
📧 **Contatti:** info@biovital.it

### 4. TIMESTAMP (Capitoli)
- Crea timestamp significativi ogni 3-5 minuti
- Formato: `00:00 Introduzione`
- Almeno 5-8 timestamp per video standard
- Titoli capitoli chiari e descrittivi

### 5. ARGOMENTI TRATTATI
- Lista bullet point (6-10 punti)
- Argomenti EFFETTIVAMENTE trattati nel video
- Specifici e dettagliati

### 6. OSPITI E INTERVISTATI
- Se presenti ospiti, indicare nome e competenza
- Se NON ci sono ospiti, scrivi: "Video condotto in solitaria da Adrian Fiorelli"

### 7. CITAZIONI RILEVANTI
- 3-5 citazioni testuali dal video
- Tra virgolette
- Le più significative e memorabili

### 8. RIFERIMENTI E FONTI
- Libri, articoli, studi citati nel video
- Solo se EFFETTIVAMENTE menzionati
- Con autori e titoli quando disponibili

### 9. TEMI CORRELATI
- 4-6 temi collegati all'argomento
- Per approfondimenti futuri
- Pertinenti al contenuto

### 10. COLLEGAMENTI AD ALTRI VIDEO
Suggerisci video correlati del canale (formato generico):
- Video su [Tema correlato 1]
- Video su [Tema correlato 2]
- Playlist: [Nome playlist pertinente]

### 11. LINK APPROFONDIMENTO
Se nel video sono citati link o risorse:
- Elencarli qui
- Altrimenti scrivere: "Nessun link specifico menzionato"

### 12. SOCIAL MEDIA
Inserisci SEMPRE questi social di Adrian Fiorelli:

📱 **Seguimi sui social:**
- Instagram: @adrianfiorelli
- Facebook: /adrianfiorelli
- Twitter: @adrianfiorelli
- Telegram: t.me/ilpuntodivista

### 13. LINK UTILI
Inserisci SEMPRE questi link:

🔗 **Link utili:**
- Sito ufficiale: https://www.ilpuntodivista.it
- Sostieni il canale: https://www.patreon.com/ilpuntodivista
- Shop merchandising: https://shop.ilpuntodivista.it
- Newsletter: https://www.ilpuntodivista.it/newsletter

### 14. DISCLAIMER
Inserisci SEMPRE questo disclaimer:

⚠️ **Disclaimer:**
I contenuti di questo video sono a scopo informativo e di intrattenimento. Le opinioni espresse rappresentano il punto di vista personale dell'autore e non costituiscono verità assolute. Ti invitiamo sempre a fare le tue ricerche, verificare le fonti e formarti un'opinione critica e indipendente. La spiritualità e il pensiero critico sono percorsi personali che richiedono discernimento.

### 15. HASHTAG
- 10-15 hashtag pertinenti
- Mix di generici e specifici
- Senza spazi (es: #Spiritualità #Esoterismo #Misteri)

---

## LINEE GUIDA CONTENUTO

### STILE E TONO
- **Professionale** ma accessibile
- **Rigoroso** senza essere accademico
- **Coinvolgente** senza sensazionalismo
- **Informativo** e ben strutturato

### COSA FARE
✅ Basarsi SOLO sulla trascrizione
✅ Mantenere accuratezza fattuale
✅ Usare linguaggio chiaro e preciso
✅ Rispettare la struttura richiesta
✅ Includere TUTTE le 15 sezioni

### COSA NON FARE
❌ Inventare informazioni
❌ Aggiungere dettagli non presenti
❌ Usare tono clickbait
❌ Esagerare o drammatizzare
❌ Omettere sezioni richieste

---

## FORMATTAZIONE

- Usa **grassetto** per titoli sezioni
- Usa emoji appropriate (1-2 per sezione)
- Usa liste bullet point
- Separa sezioni con spazio bianco
- Mantieni leggibilità

---

## ESEMPIO TIMESTAMP

00:00 Introduzione al tema
03:45 Contesto storico
08:20 Prima argomentazione
15:30 Analisi critica
22:10 Esempi pratici
28:45 Considerazioni finali
32:00 Conclusioni

---

## OUTPUT FINALE

Genera ora la descrizione completa seguendo TUTTE le 15 sezioni in ordine.
Ricorda: accuratezza, professionalità, completezza.

PROMPT;

        return $prompt;
    }

    /**
     * Parse generated content into structured sections
     */
    private function parse_generated_content($content) {
        $sections = [
            'title' => '',
            'description_short' => '',
            'sponsor' => '',
            'timestamps' => '',
            'topics' => '',
            'guests' => '',
            'quotes' => '',
            'references' => '',
            'related_themes' => '',
            'related_videos' => '',
            'links' => '',
            'social_media' => '',
            'useful_links' => '',
            'disclaimer' => '',
            'hashtags' => '',
            'full_content' => $content
        ];

        // Try to extract sections using regex
        // Section 1: Title
        if (preg_match('/### 1\. TITOLO.*?\n(.+?)(?=\n###|\n\n|$)/s', $content, $matches)) {
            $sections['title'] = trim($matches[1]);
        }

        // Section 2: Short description
        if (preg_match('/### 2\. DESCRIZIONE BREVE.*?\n(.+?)(?=\n###|\n\n###)/s', $content, $matches)) {
            $sections['description_short'] = trim($matches[1]);
        }

        // Section 3: Sponsor
        if (preg_match('/### 3\. SPONSOR.*?\n(.+?)(?=\n### 4\.)/s', $content, $matches)) {
            $sections['sponsor'] = trim($matches[1]);
        }

        // Section 4: Timestamps
        if (preg_match('/### 4\. TIMESTAMP.*?\n(.+?)(?=\n### 5\.)/s', $content, $matches)) {
            $sections['timestamps'] = trim($matches[1]);
        }

        // Section 5: Topics
        if (preg_match('/### 5\. ARGOMENTI TRATTATI.*?\n(.+?)(?=\n### 6\.)/s', $content, $matches)) {
            $sections['topics'] = trim($matches[1]);
        }

        // Section 15: Hashtags (often at the end)
        if (preg_match('/### 15\. HASHTAG.*?\n(.+?)$/s', $content, $matches)) {
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
