The [Databook Interface](http://databook.wegov.nyc) is a data-driven website for viewing dozens of NYC Open Datasets that have been integrated together through our data normalization process.

This appp is built in PHP using the Laravel framework.

## Background

Thanks to the diligent work of New York City's Mayor's Office of Data Analytics (MODA) and their many Open Data Coordinators, NYC's government has over 3,000 datasets in its Open Data portal. Many of these datasets are updated at regular intervals ranging from once a day to once a year. These regularly updated datasets present us with an unprecedented opportunity to access, analyze and visualize government data so we can better understand how our city works and determine how we can improve it.

But before we can build apps that use this data to its full potential, we need to normalize it. 

That's why we've put together a data transformation pipeline. Here's how it works.

![image](https://user-images.githubusercontent.com/444311/150594795-539fca66-3124-4d80-8619-867e776176ba.png)
[Check out the full Databook Slideshow](https://www.notion.so/WeGovNYC-eec7e04a5cfb4cf6b801a927d148c9cb)

### 1. Index

We're constantly evaluating and monitoring NYC Open Data sets for potential inclusion into the Databook. You can see all the Datasets we've looked at [here](https://airtable.com/shru4lRGkm4REB3t5)

Want us to normalize some data about NYC? [Let us know](https://airtable.com/shr8UkMTenFONmaUK).


### 2. Normalize

We've built a custom web application that takes data from our Airtable Data Repo and let's us normalize it by matching the dataset's unique entries in one or more columns to entries in our canonical index datasets. 

Currently we're normalizing the following types of data:

- Organizations
- City Council Districts
- Community Districts
- Neighborhood Tabulation Areas
- Civil Service Titles

We're exploring the possibility of expanding our normalization datasets to include:

- Capital Project IDs

Want us to normalize some data? [Let us know](https://airtable.com/shr8UkMTenFONmaUK).

You can read more about how our normalization tool works [here](https://docs.google.com/presentation/d/1-mlKgb3q6djnvEo-87BmUfhGy5jvF6CTIk0-4U8Vllg/edit?usp=sharing). 

### 3. Archive

We're normalizing over 25 datasets from NYC government's official open data portal and republishing them to AWS S3 buckets for anyone to download. 

You can download the latest normalized data using via the "Output URL" link of the datasets [here](https://airtable.com/shrTeXDJ0ElDrPipE).

### 4. Republish

We then load these datasets into an open source implementation of CartoDB so the data can be easily accessed via Carto's standard SQL API. 

We're planning to migrate away from the Carto software and instead use our own AWS database so that we can have better control of the system and offer our data via a more user frield API gateway. 

If you're interested in accessing our data via API or have opinions and ideas about how we can improve access to our normalizes data, please [let us know](https://www.notion.so/Contact-Us-54b075fa86ec47ebae48dae1595afc2c) and/or put up an issue on the this Github about it. 

### 5. Develop

We've done this data normalization work for the purpose of making it easier for people to build apps with NYC Open Data — including ourselves!

We've built the [Databook Interface](http://databook.wegov.nyc) to experiment with this data and to see what a data-driven website for NYC government could look like.

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

Thank you for considering contributing to the Databook! 

We'd love to work with you to incorporate your ideas, designs and code into our project.

We're committed to working in the open. Please consider collaborating with us!

Here are a few ways to connect with us:

- [Subscribe to our Newsletter](https://www.notion.so/Newsletter-a05ea3bf416848e381c9fb9df93b2ec5)
- [Join our Slack](https://join.slack.com/t/wegovnyc/shared_invite/zt-ydyfsw37-FJ44MKk9eHrwhk73XE9r~g)
- [Send us a Message](https://www.notion.so/Contact-Us-54b075fa86ec47ebae48dae1595afc2c)
- [Make a Donation](https://opencollective.com/wegovnyc)

We're also actively monitoring the Github Issue queue in this repository so please feel free to post to it and we'll respond forthwith!

## License

Copyright 2022 Sarapis Foundation Inc

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

