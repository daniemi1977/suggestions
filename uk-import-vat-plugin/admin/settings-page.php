<?php
/**
 * Pagina delle impostazioni admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = UK_Import_VAT::get_instance();

// Gestione salvataggio
if (isset($_POST['uiv_save_settings']) && check_admin_referer('uiv_settings_nonce')) {
    $new_options = array(
        'minimum_order' => floatval($_POST['uiv_minimum_order']),
        'exchange_rate' => floatval($_POST['uiv_exchange_rate']),
        'vat_rate' => floatval($_POST['uiv_vat_rate']),
        'duty_rate' => floatval($_POST['uiv_duty_rate']),
        'box_position' => sanitize_text_field($_POST['uiv_box_position']),
        'debug_mode' => isset($_POST['uiv_debug_mode']),
        'custom_css' => wp_strip_all_tags($_POST['uiv_custom_css'])
    );

    $plugin->update_options($new_options);

    echo '<div class="notice notice-success is-dismissible"><p><strong>Impostazioni salvate con successo!</strong></p></div>';
}

// Carica opzioni correnti
$minimum_order = $plugin->get_option('minimum_order', 220);
$exchange_rate = $plugin->get_option('exchange_rate', 0.86);
$vat_rate = $plugin->get_option('vat_rate', 20);
$duty_rate = $plugin->get_option('duty_rate', 6.5);
$box_position = $plugin->get_option('box_position', 'after_shipping');
$debug_mode = $plugin->get_option('debug_mode', false);
$custom_css = $plugin->get_option('custom_css', '');

?>

<div class="wrap">
    <h1>⚙️ UK Import VAT 3.2 - Impostazioni</h1>

    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; margin: 20px 0; border-radius: 4px;">
        <h2 style="margin-top: 0;">📌 Informazioni sul Plugin</h2>
        <p><strong>Versione:</strong> <?php echo UIV_VERSION; ?></p>
        <p><strong>Descrizione:</strong> Questo plugin mostra ai clienti UK una stima precisa delle tasse di importazione (IVA + dazi) durante il checkout e nel carrello.</p>
        <p><strong>⚠️ Importante:</strong> Il plugin NON modifica i prezzi dei prodotti. Per rimuovere l'IVA italiana ai clienti UK, configura le tasse in <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=tax'); ?>">WooCommerce → Impostazioni → Tasse</a>.</p>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('uiv_settings_nonce'); ?>

        <table class="form-table" role="presentation">
            <tbody>
                <!-- Ordine minimo -->
                <tr>
                    <th scope="row">
                        <label for="uiv_minimum_order">Ordine Minimo (€)</label>
                    </th>
                    <td>
                        <input type="number"
                               name="uiv_minimum_order"
                               id="uiv_minimum_order"
                               value="<?php echo esc_attr($minimum_order); ?>"
                               step="0.01"
                               min="0"
                               class="regular-text">
                        <p class="description">
                            Soglia minima (in Euro) sotto la quale le tasse UK non vengono mostrate. Default: 220€
                        </p>
                    </td>
                </tr>

                <!-- Tasso di cambio -->
                <tr>
                    <th scope="row">
                        <label for="uiv_exchange_rate">Tasso di Cambio EUR → GBP</label>
                    </th>
                    <td>
                        <input type="number"
                               name="uiv_exchange_rate"
                               id="uiv_exchange_rate"
                               value="<?php echo esc_attr($exchange_rate); ?>"
                               step="0.0001"
                               min="0"
                               class="regular-text">
                        <p class="description">
                            Tasso di conversione da Euro a Sterline. Esempio: 0.86 significa che 1€ = 0.86£. Verifica il tasso corrente su <a href="https://www.xe.com/it/currencyconverter/convert/?Amount=1&From=EUR&To=GBP" target="_blank">XE.com</a>
                        </p>
                    </td>
                </tr>

                <!-- IVA UK -->
                <tr>
                    <th scope="row">
                        <label for="uiv_vat_rate">IVA UK (%)</label>
                    </th>
                    <td>
                        <input type="number"
                               name="uiv_vat_rate"
                               id="uiv_vat_rate"
                               value="<?php echo esc_attr($vat_rate); ?>"
                               step="0.1"
                               min="0"
                               max="100"
                               class="regular-text">
                        <p class="description">
                            Percentuale IVA applicata nel Regno Unito. Default: 20%
                        </p>
                    </td>
                </tr>

                <!-- Dazio -->
                <tr>
                    <th scope="row">
                        <label for="uiv_duty_rate">Dazio Cosmetico (%)</label>
                    </th>
                    <td>
                        <input type="number"
                               name="uiv_duty_rate"
                               id="uiv_duty_rate"
                               value="<?php echo esc_attr($duty_rate); ?>"
                               step="0.1"
                               min="0"
                               max="100"
                               class="regular-text">
                        <p class="description">
                            Percentuale dazio doganale per prodotti cosmetici. Default: 6.5%
                        </p>
                    </td>
                </tr>

                <!-- Posizione box -->
                <tr>
                    <th scope="row">
                        <label for="uiv_box_position">Posizione del Box</label>
                    </th>
                    <td>
                        <select name="uiv_box_position" id="uiv_box_position" class="regular-text">
                            <option value="after_shipping" <?php selected($box_position, 'after_shipping'); ?>>
                                Dopo le spese di spedizione
                            </option>
                            <option value="before_total" <?php selected($box_position, 'before_total'); ?>>
                                Prima del totale ordine
                            </option>
                        </select>
                        <p class="description">
                            Scegli dove visualizzare il box delle tasse UK nel checkout e nel carrello.
                        </p>
                    </td>
                </tr>

                <!-- Debug mode -->
                <tr>
                    <th scope="row">
                        <label for="uiv_debug_mode">Modalità Debug</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="uiv_debug_mode"
                                   id="uiv_debug_mode"
                                   value="1"
                                   <?php checked($debug_mode, true); ?>>
                            Attiva il logging avanzato
                        </label>
                        <p class="description">
                            Se attivato, il plugin registrerà tutte le operazioni in: <code><?php echo UIV_LOG_FILE; ?></code>
                            <?php if (file_exists(UIV_LOG_FILE)): ?>
                                <br><a href="<?php echo content_url('uk_import_vat_debug.log'); ?>" target="_blank">📄 Visualizza log</a> |
                                <a href="#" onclick="if(confirm('Vuoi cancellare il file di log?')) { fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=uiv_clear_log').then(() => location.reload()); } return false;">🗑️ Cancella log</a>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>

                <!-- CSS personalizzato -->
                <tr>
                    <th scope="row">
                        <label for="uiv_custom_css">CSS Personalizzato</label>
                    </th>
                    <td>
                        <textarea name="uiv_custom_css"
                                  id="uiv_custom_css"
                                  rows="10"
                                  class="large-text code"
                                  placeholder=".uiv-box {&#10;    background: white;&#10;    border-color: #d4af37;&#10;}"><?php echo esc_textarea($custom_css); ?></textarea>
                        <p class="description">
                            Inserisci CSS personalizzato per modificare l'aspetto del box delle tasse. Il CSS viene iniettato direttamente nel <code>&lt;head&gt;</code>.
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <input type="submit"
                   name="uiv_save_settings"
                   id="submit"
                   class="button button-primary"
                   value="💾 Salva Impostazioni">
        </p>
    </form>

    <!-- Guida configurazione WooCommerce -->
    <div style="background: #fffbcc; border-left: 4px solid #ffb900; padding: 20px; margin: 20px 0;">
        <h2 style="margin-top: 0;">💡 Come Configurare WooCommerce per Clienti UK</h2>
        <p>Per rimuovere l'IVA italiana ai clienti del Regno Unito, segui questi passi:</p>
        <ol>
            <li>Vai su <strong><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=tax'); ?>">WooCommerce → Impostazioni → Tasse</a></strong></li>
            <li>Assicurati che l'opzione <strong>"Abilita tasse"</strong> sia attiva</li>
            <li>Crea una classe di tassa <strong>"Zero-rated"</strong> per il Regno Unito con aliquota 0%</li>
            <li>Nei prodotti, imposta <strong>classe tassa = "Zero rate"</strong> oppure configura le aliquote per paese</li>
        </ol>
        <p><strong>🔗 Documentazione ufficiale WooCommerce:</strong> <a href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/" target="_blank">Setting up Taxes</a></p>
    </div>

    <!-- Testing -->
    <div style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin: 20px 0;">
        <h2 style="margin-top: 0;">🧪 Come Testare il Plugin</h2>
        <ol>
            <li>Vai sul <strong>frontend del sito</strong></li>
            <li>Aggiungi prodotti al carrello (per un totale superiore a <?php echo number_format($minimum_order, 2); ?>€)</li>
            <li>Vai al <strong>checkout</strong></li>
            <li>Imposta il paese di fatturazione/spedizione su <strong>"Regno Unito (GB)"</strong></li>
            <li>Il box delle tasse UK dovrebbe apparire automaticamente</li>
        </ol>
        <p><strong>Nota:</strong> Se il debug è attivo, controlla il file di log per vedere i calcoli in tempo reale.</p>
    </div>
</div>

<style>
.wrap h1 {
    margin-bottom: 20px;
}

.form-table th {
    width: 250px;
}

.regular-text {
    width: 350px;
}

code {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 13px;
}
</style>

<?php
// AJAX per cancellare il log
add_action('wp_ajax_uiv_clear_log', function() {
    if (file_exists(UIV_LOG_FILE)) {
        @unlink(UIV_LOG_FILE);
    }
    wp_send_json_success();
});
?>
