# Plugin Wablas for Slims 9 Bulian
## How to install the plugin

- Download this plugin and put in `<slims root>/plugins` directory
- Require "guzzlehttp/guzzle": "^7.4" in the file composer.json or 
```
$ composer require guzzlehttp/guzzle:^7.4
```
- Do composer update
```
$ composer update
```
- Go to file `<slims root>/plugins/kamar_slims/kamar_slims.plugin.php` edit row 
```php
$config['library_name'] = 'Perpustakaan Serbaguna'; // your library name,
$config['footer_text'] = 'Harap simpan resi ini sebagai bukti transaksi.'; // closing message,
$config['token'] = 'token'; // token wablas
```
- Activate the plugin, Go to menu System -> Plugin -> Kamar Slims -> Enable

## How to usage
- Try to do a loan/return/extend transaction then a member of your library will receive a transaction notification, if not contact us for guidance