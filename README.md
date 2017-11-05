# Wink
The personal, minimalist, no-database web tools for centralizing URL.

## Installation

- Clone or download the Wink source code
- Edit the configuration file `config.php`
  - **BASE_URL**: Specify the full URL of the website
  - **SITE_TITLE**: Eventually, give a personal name to the website
- Generate authentication keys in `config.php`
- That's all, you can use Wink ;)

## Use Wink

Anyone can read the list of links published on Wink. To add a link, you must have one of the authentication keys and enter it in the URL: [https://wink.tld/?apikey=put_a_unique_token_here](https://wink.tld/?apikey=) and you will have access to the publication form. Published messages must not be empty and must contain at least one link.
