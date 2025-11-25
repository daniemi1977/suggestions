# 🇬🇧 UK Import VAT 3.2

Plugin WordPress/WooCommerce per mostrare ai clienti del Regno Unito una stima precisa delle tasse di importazione durante il checkout e nel carrello.

## 📋 Descrizione

Il plugin **UK Import VAT 3.2** permette di mostrare automaticamente una stima delle tasse di importazione UK (IVA + dazi doganali) ai clienti britannici durante il processo di acquisto su WooCommerce.

### ✨ Caratteristiche Principali

- ✅ **Non modifica i prezzi** di WooCommerce
- ✅ **100% compatibile** con tutti i temi e plugin WooCommerce
- ✅ **Calcolo automatico** in tempo reale
- ✅ **Box responsive** e personalizzabile
- ✅ **Pannello admin** completo e intuitivo
- ✅ **Debug mode** con logging avanzato
- ✅ **Zero JavaScript** - nessun conflitto AJAX
- ✅ **Posizionamento flessibile** del box informativo

## 🎯 Obiettivo

Fornire **trasparenza** e **chiarezza** ai clienti UK mostrando esattamente quanto dovranno pagare al corriere per le tasse di importazione, evitando sorprese alla consegna.

## 📦 Installazione

### Metodo 1: Upload manuale

1. Scarica il plugin
2. Carica la cartella `uk-import-vat-plugin` in `/wp-content/plugins/`
3. Attiva il plugin dal menu "Plugin" di WordPress
4. Vai su **WooCommerce → UK VAT** per configurare

### Metodo 2: Da repository GitHub

```bash
cd wp-content/plugins/
git clone https://github.com/daniemi1977/suggestions.git
cd suggestions/uk-import-vat-plugin
```

Poi attiva il plugin da WordPress.

## ⚙️ Configurazione

### 1. Attivazione Plugin

Dopo l'attivazione, vai su:

**WooCommerce → UK VAT**

### 2. Impostazioni Disponibili

| Impostazione | Default | Descrizione |
|-------------|---------|-------------|
| **Ordine Minimo** | 220€ | Soglia minima per mostrare le tasse |
| **Tasso di Cambio** | 0.86 | Conversione EUR → GBP |
| **IVA UK** | 20% | Percentuale IVA britannica |
| **Dazio Cosmetico** | 6.5% | Percentuale dazio per cosmetici |
| **Posizione Box** | Dopo spedizione | Dove mostrare il box |
| **Debug Mode** | Off | Attiva logging avanzato |
| **CSS Personalizzato** | - | CSS custom per il box |

### 3. Configurazione WooCommerce Tasse

⚠️ **Importante:** Il plugin NON rimuove automaticamente l'IVA italiana. Devi configurarlo manualmente in WooCommerce.

#### Passi per rimuovere l'IVA italiana ai clienti UK:

1. Vai su **WooCommerce → Impostazioni → Tasse**
2. Abilita le tasse
3. Crea una classe di tassa **"Zero-rated"** per UK con aliquota 0%
4. Applica questa classe ai prodotti o configura le aliquote per paese

📚 **Guida ufficiale:** [WooCommerce Tax Settings](https://woocommerce.com/document/setting-up-taxes-in-woocommerce/)

## 🧮 Come Funziona

### Formula di Calcolo

```
1. Totale imponibile (EUR) = Subtotal (senza IVA) + Spedizione
2. Conversione GBP = Totale EUR × Tasso di cambio
3. IVA UK (EUR) = (Totale GBP × IVA%) ÷ Tasso di cambio
4. Dazio (EUR) = (Totale GBP × Dazio%) ÷ Tasso di cambio
5. Totale tasse = IVA UK + Dazio
```

### Esempio Pratico

Ordine: **€295,00** (€270 prodotti + €25 spedizione)

```
- Conversione GBP: €295 × 0.86 = £253.70
- IVA UK (20%): £50.74 = €59.00
- Dazio (6.5%): £16.49 = €19.18
- Totale tasse: €78.18
```

Il cliente pagherà queste tasse **direttamente al corriere** alla consegna.

## 🎨 Personalizzazione

### CSS Personalizzato

Puoi personalizzare l'aspetto del box dal pannello admin:

```css
/* Esempio: tema dorato */
.uiv-box {
    background: white;
    border-color: #d4af37;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.uiv-box-title {
    color: #d4af37;
    font-size: 1.3em;
}

.uiv-box-final {
    background: #fffbcc;
    padding: 10px;
    border-radius: 4px;
}
```

### Hook e Filtri

Il plugin supporta hook WordPress per sviluppatori avanzati:

```php
// Modifica il titolo del box
add_filter('uiv_box_title', function($title) {
    return '🇬🇧 Import Taxes Estimate';
});

// Modifica la nota
add_filter('uiv_box_note', function($note) {
    return '*Paid directly to courier on delivery';
});

// Modifica i dati del calcolo
add_filter('uiv_calculation_data', function($calculation) {
    // Modifica i dati prima del rendering
    return $calculation;
});
```

## 🐛 Debug e Logging

### Attivare il Debug

1. Vai su **WooCommerce → UK VAT**
2. Spunta **"Modalità Debug"**
3. Salva le impostazioni

### Visualizzare i Log

I log vengono salvati in:

```
/wp-content/uk_import_vat_debug.log
```

Puoi visualizzarli e cancellarli direttamente dal pannello admin.

### Esempio Log

```
[2025-11-24 23:41:12] Plugin inizializzato. Versione: 3.2.0
[2025-11-24 23:41:12] Opzioni caricate: {"minimum_order":220,"exchange_rate":0.86,...}
[2025-11-24 23:42:35] Box visualizzato con dati: {"total_eur":295,"vat_eur":59,"duty_eur":19.18}
```

## 🧪 Testing

### Come Testare il Plugin

1. **Aggiungi prodotti al carrello** (totale > €220)
2. **Vai al checkout**
3. **Imposta paese** su "Regno Unito (GB)"
4. **Verifica** che appaia il box verde con le tasse

### Checklist di Test

- [ ] Box appare solo per clienti UK
- [ ] Box NON appare per ordini < €220
- [ ] Calcoli sono corretti
- [ ] Box è responsive su mobile
- [ ] Nessun errore AJAX
- [ ] Checkout completa correttamente
- [ ] Prezzi WooCommerce non modificati

## 📱 Compatibilità

### Requisiti Minimi

- WordPress: 5.0+
- PHP: 7.2+
- WooCommerce: 4.0+

### Compatibilità Testata

- ✅ WooCommerce 8.5
- ✅ WordPress 6.4
- ✅ PHP 8.2
- ✅ Tutti i temi WooCommerce-compatible
- ✅ Page builder (Elementor, Divi, ecc.)
- ✅ Gateway di pagamento (Stripe, PayPal, ecc.)

## 🔒 Sicurezza

Il plugin è stato sviluppato seguendo le best practice WordPress:

- ✅ Nessun SQL injection
- ✅ Output sanitizzato
- ✅ Nonce verification
- ✅ Capability checks
- ✅ Escape di tutti gli output

## 📄 Licenza

GPL v2 o successiva

## 👤 Autore

**Daniele Michielon**

- GitHub: [@daniemi1977](https://github.com/daniemi1977)

## 🆘 Supporto

Per problemi o domande:

1. Verifica la documentazione
2. Attiva il debug mode
3. Controlla i log
4. Apri una issue su GitHub

## 📝 Changelog

### Versione 3.2.0 (2025-11-25)

- ✨ Release iniziale
- ✅ Calcolo automatico tasse UK
- ✅ Box frontend responsive
- ✅ Pannello admin completo
- ✅ Debug mode e logging
- ✅ CSS personalizzabile
- ✅ Posizionamento flessibile

## 🙏 Credits

Sviluppato con ❤️ per la community WooCommerce italiana.

---

**⚠️ Nota Importante:** Questo plugin fornisce solo **stime** delle tasse di importazione. I costi effettivi possono variare in base a:

- Fluttuazioni del tasso di cambio
- Classificazione doganale dei prodotti
- Regolamenti doganali in vigore
- Accordi commerciali UK

Consulta sempre un commercialista per informazioni precise sulla fiscalità internazionale.
