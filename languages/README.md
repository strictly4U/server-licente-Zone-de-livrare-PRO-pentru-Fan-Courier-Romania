# Translations for HgE PRO

This directory contains translation files for the HgE PRO: Additional Shipping Services for FAN Courier Romania plugin.

## Text Domain
`hge-zone-de-livrare-pentru-fan-courier-romania-pro`

## Translation Files

- `*.pot` - Portable Object Template (source file for translators)
- `*.po` - Portable Object (editable translation file)
- `*.mo` - Machine Object (compiled translation file used by WordPress)

## How to Translate

### For WordPress.org (Automatic)
WordPress.org automatically provides translation management since WordPress 4.6+. No manual translation files needed for languages available on translate.wordpress.org.

### For Custom Translations

1. **Generate POT file** (for developers):
   ```bash
   wp i18n make-pot . languages/hge-zone-de-livrare-pentru-fan-courier-romania-pro.pot
   ```

2. **Translate**:
   - Use [Poedit](https://poedit.net/) or similar tool
   - Open the `.pot` file
   - Translate strings
   - Save as `.po` and `.mo` files

3. **File naming convention**:
   - Romanian: `hge-zone-de-livrare-pentru-fan-courier-romania-pro-ro_RO.po`
   - English: `hge-zone-de-livrare-pentru-fan-courier-romania-pro-en_US.po`

## Supported Languages

- Romanian (ro_RO) - Primary
- English (en_US) - Fallback

## Developer Notes

All translatable strings must use:
- `__()` - Returns translated string
- `_e()` - Echoes translated string
- `_n()` - Plural forms
- `esc_html__()` - Escaped translation
- `esc_html_e()` - Escaped echo translation
- `esc_attr__()` - Attribute translation

Example:
```php
__('FANBox Locker', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro')
```

## Translation Context

Use `_x()` for context-specific translations:
```php
_x('Open', 'button label', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro')
_x('Open', 'shop status', 'hge-zone-de-livrare-pentru-fan-courier-romania-pro')
```
