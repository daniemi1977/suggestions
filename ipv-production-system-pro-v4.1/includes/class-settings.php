<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Settings {

    public static function register_settings() {
        register_setting( 'ipv_prod_settings_group', 'ipv_supadata_api_key' );
        register_setting( 'ipv_prod_settings_group', 'ipv_transcript_mode' );
        register_setting( 'ipv_prod_settings_group', 'ipv_openai_api_key' );
        register_setting( 'ipv_prod_settings_group', 'ipv_ai_prompt' );
        register_setting( 'ipv_prod_settings_group', 'ipv_youtube_api_key' );
        register_setting( 'ipv_prod_settings_group', 'ipv_default_sponsor' );
        register_setting( 'ipv_prod_settings_group', 'ipv_sponsor_link' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_telegram' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_facebook' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_instagram' );
        register_setting( 'ipv_prod_settings_group', 'ipv_social_website' );
        register_setting( 'ipv_prod_settings_group', 'ipv_contact_email' );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Salva le impostazioni se il form è stato inviato
        if ( isset( $_POST['ipv_save_settings'] ) ) {
            check_admin_referer( 'ipv_prod_settings_save' );
            self::save_settings();
            ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Impostazioni salvate con successo!</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php
        }

        $supadata_key     = get_option( 'ipv_supadata_api_key', '' );
        $transcript_mode  = get_option( 'ipv_transcript_mode', 'auto' );
        $openai_key       = get_option( 'ipv_openai_api_key', '' );
        $youtube_key      = get_option( 'ipv_youtube_api_key', '' );
        $default_sponsor  = get_option( 'ipv_default_sponsor', 'Biovital – Progetto Italia' );
        $sponsor_link     = get_option( 'ipv_sponsor_link', '' );
        $telegram         = get_option( 'ipv_social_telegram', '' );
        $facebook         = get_option( 'ipv_social_facebook', '' );
        $instagram        = get_option( 'ipv_social_instagram', '' );
        $website          = get_option( 'ipv_social_website', '' );
        $email            = get_option( 'ipv_contact_email', '' );
        $custom_prompt    = get_option( 'ipv_ai_prompt', '' );
        ?>
        <div class="wrap ipv-prod-wrap">
            <div class="ipv-prod-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1">
                            <i class="bi bi-gear-fill text-white me-2"></i>
                            Impostazioni
                        </h1>
                        <p class="text-muted mb-0">Configura API e parametri del canale</p>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-dashboard' ) ); ?>">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-import' ) ); ?>">
                        <i class="bi bi-upload me-1"></i>Importa Video
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-rss' ) ); ?>">
                        <i class="bi bi-rss me-1"></i>Auto-Import RSS
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-queue' ) ); ?>">
                        <i class="bi bi-list-task me-1"></i>Coda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo esc_url( admin_url( 'admin.php?page=ipv-production-settings' ) ); ?>">
                        <i class="bi bi-gear me-1"></i>Impostazioni
                    </a>
                </li>
            </ul>

            <form method="post" action="" class="ipv-form">
                <?php wp_nonce_field( 'ipv_prod_settings_save' ); ?>
                <input type="hidden" name="ipv_save_settings" value="1" />

                <div class="row g-4">
                    <!-- Colonna API Keys -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-key-fill text-primary me-2"></i>
                                    API Keys
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- SupaData API Key -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-cloud me-1"></i>
                                        SupaData API Key
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control ipv-validate" 
                                           name="ipv_supadata_api_key" 
                                           value="<?php echo esc_attr( $supadata_key ); ?>" 
                                           data-validate-type="required"
                                           placeholder="sk-..." />
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Richiesto per generare le trascrizioni dei video
                                    </div>
                                </div>

                                <!-- Modalità Trascrizione -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-toggles me-1"></i>
                                        Modalità Trascrizione
                                    </label>
                                    <select name="ipv_transcript_mode" class="form-select">
                                        <option value="auto" <?php selected( $transcript_mode, 'auto' ); ?>>
                                            Auto (consigliato)
                                        </option>
                                        <option value="native" <?php selected( $transcript_mode, 'native' ); ?>>
                                            Native
                                        </option>
                                        <option value="generate" <?php selected( $transcript_mode, 'generate' ); ?>>
                                            Generate
                                        </option>
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Auto usa i sottotitoli se esistono, altrimenti li genera
                                    </div>
                                </div>

                                <!-- OpenAI API Key -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-robot me-1"></i>
                                        OpenAI API Key
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control ipv-validate" 
                                           name="ipv_openai_api_key" 
                                           value="<?php echo esc_attr( $openai_key ); ?>" 
                                           data-validate-type="required"
                                           placeholder="sk-..." />
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Richiesto per generare le descrizioni AI
                                    </div>
                                </div>

                                <!-- YouTube API Key -->
                                <div class="mb-0">
                                    <label class="form-label">
                                        <i class="bi bi-youtube me-1"></i>
                                        YouTube Data API Key
                                        <span class="badge bg-warning text-dark">Opzionale</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="ipv_youtube_api_key" 
                                           value="<?php echo esc_attr( $youtube_key ); ?>" 
                                           placeholder="AIza..." />
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Per recuperare titoli corretti dei video
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parametri Canale -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-broadcast-pin text-primary me-2"></i>
                                    Parametri Canale
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Sponsor -->
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-award me-1"></i>
                                        Sponsor di Default
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="ipv_default_sponsor" 
                                           value="<?php echo esc_attr( $default_sponsor ); ?>" 
                                           placeholder="Biovital – Progetto Italia" />
                                </div>

                                <!-- Link Sponsor -->
                                <div class="mb-0">
                                    <label class="form-label">
                                        <i class="bi bi-link-45deg me-1"></i>
                                        Link Sponsor
                                    </label>
                                    <input type="url" 
                                           class="form-control" 
                                           name="ipv_sponsor_link" 
                                           value="<?php echo esc_attr( $sponsor_link ); ?>" 
                                           placeholder="https://www.biovital.it" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonna Social & Prompt -->
                    <div class="col-lg-6">
                        <!-- Social Media -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-share-fill text-primary me-2"></i>
                                    Social Media & Contatti
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-telegram text-info me-1"></i>
                                        Telegram
                                    </label>
                                    <input type="url" 
                                           class="form-control" 
                                           name="ipv_social_telegram" 
                                           value="<?php echo esc_attr( $telegram ); ?>" 
                                           placeholder="https://t.me/ilpuntodivista" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-facebook text-primary me-1"></i>
                                        Facebook
                                    </label>
                                    <input type="url" 
                                           class="form-control" 
                                           name="ipv_social_facebook" 
                                           value="<?php echo esc_attr( $facebook ); ?>" 
                                           placeholder="https://facebook.com/ilpuntodivista" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-instagram text-danger me-1"></i>
                                        Instagram
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="ipv_social_instagram" 
                                           value="<?php echo esc_attr( $instagram ); ?>" 
                                           placeholder="@ilpuntodivista_official" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-globe2 text-success me-1"></i>
                                        Sito Web
                                    </label>
                                    <input type="url" 
                                           class="form-control" 
                                           name="ipv_social_website" 
                                           value="<?php echo esc_attr( $website ); ?>" 
                                           placeholder="https://www.ilpuntodivista.it" />
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">
                                        <i class="bi bi-envelope-fill text-warning me-1"></i>
                                        Email Contatto
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           name="ipv_contact_email" 
                                           value="<?php echo esc_attr( $email ); ?>" 
                                           placeholder="info@ilpuntodivista.it" />
                                </div>
                            </div>
                        </div>

                        <!-- Prompt Personalizzato -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-code-square text-primary me-2"></i>
                                    Prompt AI Personalizzato
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle-fill me-2"></i>
                                    Se lasci vuoto questo campo, verrà utilizzato il <strong>GOLDEN PROMPT</strong> integrato nel plugin (350+ righe, ottimizzato per "Il Punto di Vista").
                                </div>
                                <textarea name="ipv_ai_prompt" 
                                          rows="12" 
                                          class="form-control font-monospace" 
                                          placeholder="Lascia vuoto per usare il GOLDEN PROMPT interno..."><?php echo esc_textarea( $custom_prompt ); ?></textarea>
                                <div class="form-text">
                                    <i class="bi bi-lightbulb me-1"></i>
                                    Puoi sovrascrivere il prompt di default con il tuo personalizzato
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pulsante Salva -->
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Tutte le chiavi API vengono salvate in modo sicuro nel database
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-1"></i>
                                Salva Impostazioni
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    private static function save_settings() {
        $fields = [
            'ipv_supadata_api_key',
            'ipv_transcript_mode',
            'ipv_openai_api_key',
            'ipv_youtube_api_key',
            'ipv_default_sponsor',
            'ipv_sponsor_link',
            'ipv_social_telegram',
            'ipv_social_facebook',
            'ipv_social_instagram',
            'ipv_social_website',
            'ipv_contact_email',
            'ipv_ai_prompt',
        ];

        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
                
                // Gestione speciale per textarea
                if ( $field === 'ipv_ai_prompt' ) {
                    $value = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
                }

                update_option( $field, $value );
            }
        }
    }
}
