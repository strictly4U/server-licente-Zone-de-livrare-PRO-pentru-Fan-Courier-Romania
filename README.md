# HgE PRO: Additional Shipping Services for FAN Courier Romania

**Version:** 2.0.0
**Requires:** HgE: Shipping Zones for FAN Courier Romania (Standard) v1.0.3+
**Author:** Hurubaru George Emanuel

## Description

Premium WordPress plugin that extends the Standard FAN Courier plugin with 9 additional shipping services:

1. **FANBox** - Locker delivery
2. **Express Loco 2H** - Ultra-fast 2-hour delivery
3. **RedCode** - Same-day delivery (max 5kg)
4. **CollectPoint PayPoint** - Pickup from PayPoint network
5. **CollectPoint OMV/Petrom** - Pickup from gas stations
6. **Produse Albe** - Specialized for electronics (insurance required)
7. **Cargo** - Large/heavy packages (>30kg)
8. **Export** - International delivery

## Architecture

### Plugin Relationship

```
Standard Plugin (Base)
    ↓ extends
PRO Plugin (Extension)
    ↓ reuses
Standard's API Client, Logger, Admin Order
```

### Key Design Principles

- ✅ **NO code duplication** - Reuses Standard's classes
- ✅ **Metadata-driven** - Service Registry Pattern
- ✅ **DRY architecture** - Abstract Base Class for all services
- ✅ **WordPress compliant** - 100% WordPress Coding Standards
- ✅ **Backward compatible** - Works with Standard 1.0.3+

## Directory Structure

```
hge-zone-de-livrare-pentru-fan-courier-romania-pro/
├── woo-fancourier-pro.php              # Main plugin file
├── uninstall.php                        # Cleanup on deletion
├── README.md                            # This file
│
├── includes/
│   ├── class-hgezlpfcr-pro-settings.php           # Settings extension
│   ├── class-hgezlpfcr-pro-automation.php         # Auto AWB/Order completion
│   ├── class-hgezlpfcr-pro-service-registry.php   # Service management
│   │
│   ├── abstract/
│   │   └── class-hgezlpfcr-pro-shipping-abstract.php  # Base class for all services
│   │
│   ├── shipping/                        # Shipping method classes
│   │   ├── class-hgezlpfcr-pro-shipping-fanbox.php
│   │   ├── class-hgezlpfcr-pro-shipping-express-loco.php
│   │   ├── class-hgezlpfcr-pro-shipping-redcode.php
│   │   ├── class-hgezlpfcr-pro-shipping-collectpoint-paypoint.php
│   │   ├── class-hgezlpfcr-pro-shipping-collectpoint-omv.php
│   │   ├── class-hgezlpfcr-pro-shipping-produse-albe.php
│   │   ├── class-hgezlpfcr-pro-shipping-cargo.php
│   │   └── class-hgezlpfcr-pro-shipping-export.php
│   │
│   └── selectors/                       # Pickup point selectors
│       ├── class-hgezlpfcr-pro-fanbox-selector.php
│       ├── class-hgezlpfcr-pro-paypoint-selector.php
│       └── class-hgezlpfcr-pro-omv-selector.php
│
├── assets/
│   ├── js/
│   │   ├── pro-checkout.js              # Common checkout logic
│   │   ├── fanbox-map.js                # FANBox map integration
│   │   ├── paypoint-selector.js         # PayPoint selector
│   │   └── omv-selector.js              # OMV/Petrom selector
│   └── css/
│       └── pro-checkout.css             # Checkout styles
│
├── templates/                           # Frontend templates
│   ├── fanbox-selector.php
│   ├── paypoint-selector.php
│   └── omv-selector.php
│
└── languages/                           # Translations
    └── README.md                        # Translation guide
```

## Installation

1. Install and activate **HgE: Shipping Zones for FAN Courier Romania** (Standard) first
2. Upload this PRO plugin to `/wp-content/plugins_dev/`
3. Activate through WordPress admin
4. Configure at **WooCommerce > Settings > Fan Courier > PRO**

## Configuration

### Enable Services

Go to **WooCommerce > Settings > Fan Courier > PRO > Servicii PRO**

Enable desired services:
- ☐ Enable FANBox
- ☐ Enable Express Loco 2H
- ☐ Enable RedCode
- etc.

### Configure Shipping Zones

Go to **WooCommerce > Settings > Shipping > Shipping Zones**

For each zone:
1. Click "Add shipping method"
2. Select enabled PRO services
3. Configure pricing (dynamic/fixed)

## Development

### Service Implementation Status

| Service | Status | Priority | Complexity |
|---------|--------|----------|------------|
| FANBox | 🔄 Pending | 🔴 High | ⭐⭐⭐⭐ |
| Express Loco | 🔄 Pending | 🟡 Medium | ⭐⭐⭐ |
| RedCode | 🔄 Pending | 🔴 High | ⭐⭐⭐ |
| PayPoint | 🔄 Pending | 🟢 Normal | ⭐⭐⭐⭐ |
| OMV/Petrom | 🔄 Pending | 🟢 Normal | ⭐⭐⭐⭐ |
| Produse Albe | 🔄 Pending | 🟡 Medium | ⭐⭐⭐ |
| Cargo | 🔄 Pending | 🟡 Medium | ⭐⭐⭐ |
| Export | 🔄 Pending | 🟢 Low | ⭐⭐⭐⭐⭐ |

### Phase 0: Infrastructure ✅ COMPLETED

- [x] WordPress compliant plugin header
- [x] Enhanced dependency checking
- [x] Activation/Deactivation/Uninstall hooks
- [x] Internationalization setup
- [x] Service Registry Pattern
- [x] Abstract Base Class
- [x] Directory structure
- [x] Assets (CSS/JS)

### Next Phases

- **Phase 1:** Implement FANBox (highest priority)
- **Phase 2:** Implement Express Loco & RedCode
- **Phase 3:** Implement CollectPoint services
- **Phase 4:** Implement specialized services

## Technical Details

### Class Naming Convention

- Standard plugin: `HGEZLPFCR_ClassName`
- PRO plugin: `HGEZLPFCR_Pro_ClassName`

### Text Domain

`hge-zone-de-livrare-pentru-fan-courier-romania-pro`

### Required Hooks in Standard Plugin

The Standard plugin must provide these filters/actions for PRO compatibility:

```php
// In HGEZLPFCR_Admin_Order::create_awb_for_order()
$shipment_data = apply_filters('hgezlpfcr_awb_shipment_data', $shipment_data, $order);

// After AWB generated
do_action('hgezlpfcr_awb_generated_successfully', $order_id, $awb_number);
```

## Support

- **GitHub:** https://github.com/georgeshurubaru/FcRapid1923
- **Documentation:** https://github.com/georgeshurubaru/FcRapid1923/wiki
- **Issues:** https://github.com/georgeshurubaru/FcRapid1923/issues

## License

GPL-2.0+

## Changelog

### 2.0.0 - 2025-01-19
- Initial PRO version release
- Infrastructure complete (Service Registry, Abstract Base Class)
- Ready for service implementations
- 100% WordPress compliant
- Full compatibility with Standard plugin 1.0.3+

### 1.0.0 - 2024-10-28
- Basic automation features only (Auto AWB, Auto Order Completion)
