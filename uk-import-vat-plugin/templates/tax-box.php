<?php
/**
 * Template del box tasse UK
 *
 * Variabili disponibili:
 * $calculation - Array con i dati del calcolo
 */

if (!defined('ABSPATH')) {
    exit;
}

// Applica filtri al calcolo
$calculation = uiv_apply_calculation_filters($calculation);

// Estrai i dati
$total_eur = $calculation['total_eur'];
$total_gbp = $calculation['total_gbp'];
$vat_eur = $calculation['vat_eur'];
$duty_eur = $calculation['duty_eur'];
$total_tax = $calculation['total_tax'];
$vat_rate = $calculation['vat_rate'];
$duty_rate = $calculation['duty_rate'];

?>

<tr class="<?php echo esc_attr(uiv_get_box_classes()); ?>">
    <td colspan="2">
        <div class="uiv-box-inner">
            <div class="uiv-box-title">
                <?php echo esc_html(uiv_get_box_title()); ?>
            </div>

            <div class="uiv-box-total">
                <strong>Totale ordine:</strong> <?php echo uiv_format_eur($total_eur); ?>
                (≈<?php echo uiv_format_gbp($total_gbp); ?>)
            </div>

            <ul class="uiv-box-breakdown">
                <li>
                    <strong>IVA UK (<?php echo esc_html($vat_rate); ?>%):</strong>
                    <?php echo uiv_format_eur($vat_eur); ?>
                </li>
                <li>
                    <strong>Dazi cosmetici (<?php echo esc_html($duty_rate); ?>%):</strong>
                    <?php echo uiv_format_eur($duty_eur); ?>
                </li>
            </ul>

            <div class="uiv-box-final">
                <strong>Totale stimato da pagare all'importazione:</strong>
                <?php echo uiv_format_eur($total_tax); ?>
            </div>

            <div class="uiv-box-note">
                <?php echo esc_html(uiv_get_box_note()); ?>
            </div>
        </div>
    </td>
</tr>
