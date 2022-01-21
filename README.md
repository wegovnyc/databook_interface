## About Databook

Some description


## Environment

**LAMP stack**
*	php >=7.2.5
*	Apache >=2.2
*	mod_rewrite
*	composer
*	git


## Quick Start

	cd /target/folder

	git clone https://github.com/wegovnyc/research_wegov.git .

	composer update



Databook is based on **Laravel 8.x**. In case of troubles during installation please refer to [Laravel 8 Installation Guide](https://laravel.com/docs/8.x/installation#installation-via-composer) 




Edit ``/target/folder/.env.default``, set APP_URL to actual domain name, rename to ``.env``


Edit ``/target/folder/config/apis.php.default``, rename to ``apis.php``:

- ``geoclient_key`` - Optional. Used for address search in Districts section. API key can be obtained at [NYC API Portal](https://api-portal.nyc.gov/developer) after

- ``airtable_key`` - Optional. Functionality is currently disabled



Set Apache DocumentRoot to ``/target/folder/public``



Set Apache AllowOverride option for section ``<Directory "/target/folder/public">`` ``AllowOverride All``



## Demo Installation

Live deployed system can be found in the [https://databook.wegov.nyc/](https://databook.wegov.nyc/).


## Contributing

Thank you for considering contributing to the Databook! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).


## License

The Laravel framework is open-sourced software licensed under the [MIT license???](https://opensource.org/licenses/MIT).
