<?php
/**
 * Dynamic Prompt Builder
 *
 * Builds OpenAI prompts using channel configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Prompt_Builder {

    /**
     * Build complete prompt using channel configuration
     */
    public static function build_prompt($transcript, $video_data) {
        $config = IPV_Channel_Config::get_config();
        $video_title = isset($video_data['title']) ? $video_data['title'] : 'Video';

        // Build themes list
        $themes_list = self::build_themes_list($config['channel_themes']);

        // Build sponsor section
        $sponsor_section = self::build_sponsor_section($config);

        // Build social section
        $social_section = self::build_social_section($config);

        // Build donations section
        $donations_section = self::build_donations_section($config);

        // Build contact section
        $contact_section = self::build_contact_section($config);

        // Build channel themes section
        $channel_themes = is_array($config['channel_themes'])
            ? implode(' – ', $config['channel_themes'])
            : $config['channel_themes'];

        $prompt = <<<PROMPT
# PROMPT ULTRA-RIGOROSO v2.0 - {$config['channel_name']}

Genera una descrizione COMPLETA e PROFESSIONALE per il video YouTube del canale "{$config['channel_name']}" di {$config['channel_owner']}.

## CONTESTO CANALE
"{$config['channel_name']}" è un canale YouTube dedicato a:
{$themes_list}

**Stile:** {$config['content_tone']}

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

{$sponsor_section}

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
- Se NON ci sono ospiti, scrivi: "Video condotto in solitaria da {$config['channel_owner']}"
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

**Regia:** {$config['production_credits']}

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

{$donations_section}

---

### 🙏 ABBONATI AL CANALE
Inserisci SEMPRE:

{$config['channel_youtube_handle']}

---

{$social_section}

---

{$contact_section}

---

### 🎯 TEMI DEL CANALE
Inserisci SEMPRE:

{$channel_themes}

---

### #️⃣ HASHTAG
- 10-15 hashtag pertinenti (fino a 30 max)
- Mix di generici e specifici
- Senza spazi
- Formato esempio: #Gaming #Tech #Tutorial
- Includere sempre gli hashtag del canale

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
- Usa i link e contatti forniti

Ricorda: accuratezza, professionalità, completezza.

PROMPT;

        return $prompt;
    }

    /**
     * Build themes list
     */
    private static function build_themes_list($themes) {
        if (is_array($themes)) {
            return implode("\n", array_map(function($theme) {
                return "- {$theme}";
            }, $themes));
        }
        return "- {$themes}";
    }

    /**
     * Build sponsor section
     */
    private static function build_sponsor_section($config) {
        if (!$config['sponsor_enabled']) {
            return "### 🎁 SPONSOR\n**Sezione sponsor non configurata**";
        }

        $section = "### 🎁 SPONSOR\n";
        $section .= "Inserisci SEMPRE questo sponsor:\n\n";
        $section .= "**{$config['sponsor_name']}**\n\n";
        $section .= "{$config['sponsor_description']}\n\n";

        if (!empty($config['sponsor_link'])) {
            $section .= "{$config['sponsor_cta']}: 👉 {$config['sponsor_link']}";
        }

        return $section;
    }

    /**
     * Build social section
     */
    private static function build_social_section($config) {
        $section = "### 🌐 SOCIAL\n";
        $section .= "Inserisci SEMPRE questi link:\n\n";

        $socials = [];
        if (!empty($config['social_telegram'])) $socials[] = "Telegram 👉 {$config['social_telegram']}";
        if (!empty($config['social_facebook'])) $socials[] = "Facebook 👉 {$config['social_facebook']}";
        if (!empty($config['social_instagram'])) $socials[] = "Instagram 👉 {$config['social_instagram']}";
        if (!empty($config['social_twitter'])) $socials[] = "Twitter 👉 {$config['social_twitter']}";
        if (!empty($config['social_tiktok'])) $socials[] = "TikTok 👉 {$config['social_tiktok']}";
        if (!empty($config['social_linkedin'])) $socials[] = "LinkedIn 👉 {$config['social_linkedin']}";

        $section .= implode("\n", $socials);

        return $section;
    }

    /**
     * Build donations section
     */
    private static function build_donations_section($config) {
        if (!$config['donations_enabled']) {
            return "### ❤️ DONAZIONI\n**Sezione donazioni non abilitata**";
        }

        $section = "### ❤️ DONAZIONI\n";
        $section .= "Inserisci SEMPRE:\n\n";

        // Replace {{channel_name}} in message
        $message = str_replace('{{channel_name}}', $config['channel_name'], $config['donations_message']);

        $section .= "**{$message}:**\n\n";
        $section .= "👉 {$config['donations_link']}\n\n";

        if (!empty($config['donations_thanks'])) {
            $section .= $config['donations_thanks'];
        }

        return $section;
    }

    /**
     * Build contact section
     */
    private static function build_contact_section($config) {
        $section = "### 📩 CONTATTI\n";
        $section .= "Inserisci SEMPRE:\n\n";

        if (!empty($config['contact_email'])) {
            $section .= "✉️ {$config['contact_email']}\n";
        }

        if (!empty($config['contact_website'])) {
            $section .= "🌐 {$config['contact_website']}\n";
        }

        if (!empty($config['contact_phone'])) {
            $section .= "📞 {$config['contact_phone']}";
        }

        return $section;
    }
}
