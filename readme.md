Gettext for Laravel
=========

This library is heavliy inspired (and partially based on) [netson/l4gettext] and you should really use his library instead of mine. 

I wrote this because I wanted to learn more about gettext (and package creating with laravel in general). I do not plan on supporting this library.

Get started
----
First of you need to publish the configuration
```php
php artisan config:publish paxx/gettext
```

After your changes to the config, you can go ahead and add the service provider to the list of providers in app/config/app.php:
```php
'Paxx\Gettext\GettextServiceProvider'
```

You might need to make sure that php can write to resources/lang since that is the folder where the POT-files will be placed in. 

To change locale you can then use:
```php
Gettext::setLocale('sv_SE');
```

Just make sure that you have the locales installed on your system.

After everything is set, you can run
```php
php artisan gettext
```
And your translations will be extracted from the views and added to the POT-file.

Dependencies
----
- gettext
- xgettext (Ships with gettext on most systems)
- msgmerge (Ships with gettext on most systems)

Todo
----
- Write tests
- Tidy stuff up

Version
----
1.0

License
----
MIT

Credits
---
- [Netson] - thanks for creating l4gettext!

[Netson]:https://github.com/netson
[netson/l4gettext]:https://github.com/netson/l4gettext
