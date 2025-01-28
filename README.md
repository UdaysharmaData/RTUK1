# The "Sport for" API

The REST API is developed with the [Laravel 9.1.0](https://github.com/laravel/laravel/tree/v9.1.0) framework. The official documentation can be found on the [Laravel website](https://laravel.com/docs/9.x).

## Server Requirements
- PHP 8.0 - 8.1

## Installation
You can install the application either by cloning or pulling the repository from Github:
1. Method 1: Clone
    - `git clone git@github.com:sports-media-agency/sport-for-api.git`
2. Method 2: Pull
    - Create a directory for the application: `mkdir -p <directory>`
    - Enter into the directory: `cd <directory>`
    - Initialize git within the directory: `git init`
    - Set the remote origin: `git remote add <name>`. Conventionally, you can set `<name>` to `origin`
    - Pull the repository: `git pull`

Ensure that you are on the `api` branch.

## Setup
Steps 1 to 3 are essential to get the application running. Steps 4 to 6 can be completed later to ensure a smooth running of the application or during development.
1. Copy `.env.example` to `.env`
2. Run `php artisan key:generate` to set your .env's `APP_KEY` value
3. Configure the database within the `.env` file by setting appropriate values for the `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` environment variables.
4. Configure the mail driver within the `.env` file by setting appropriate values for the `MAIL_DRIVER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_ENCRYPTION` environment variables.
5. Create a demo [Stripe](https://stripe.com/) account and configure Stripe by setting appropriate values for the `STRIPE_SECRET` and `STRIPE_PUBLIC` environment variables.
6. Register a new site at [Google Recaptcha](https://www.google.com/recaptcha) using the reCAPTCHA v2 ["I'm not a robot" Checkbox] and configure Google Recaptcha by setting appropriate values for the `RECAPTCHA_SECRET_KEY` environment variable.

## Running the Application
- Run database migrations: `php artisan migrate`
- Seed the database by importing an SQL file of the production database
- Serve the application: `php artisan serve`. By default, it will run on port :8000


## Deploying Passport
- When deploying Passport to your application's servers for the first time, you will likely need to run the `passport:keys` command. This command generates the encryption keys Passport needs in order to generate access tokens. The generated keys are not typically kept in source control:
`php artisan passport:keys`
- After generating encryption keys, you'll need to create a Personal Access Client to facilitate issuance of Personal Access Tokens. Simply run:
`php artisan passport:client --personal`
- Follow the prompt by providing a Client Name, e.g: `RunThrough`, and then press enter to continue. With any luck you should have a newly created client in the `oauth_clients` database table, and a new pair of credentials will be displayed in your terminal. Note that the Client secret will be encrypted on the database record, so it is important to
copy and paste these pair (as displayed in your terminal), especially the unencrypted version of the secret key, and safely store in your `.env` file, respectively under `PASSPORT_PERSONAL_ACCESS_CLIENT_ID` and `PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET` keys.


## Settings
- API requests will require a `X-Client-Key` header for identification on the system. To obtain a key for this value, run `php artisan client:generate-key RunThrough`; `RunThrough` being, the `name` of the API client making the request to the server.

<b>NB.</b> Ensure the appropriate database tables has pre-populated with data matching the values being referenced above.

## Testing
Run test suites: `php artisan test`

## Socialite Auth
- Add provider key-values to `.env` for each provider being supported per site/platform for authentication. Example keys for `RunThrough` Platform below:
  ### Facebook
  - `RTHUB_FACEBOOK_CLIENT_ID=1454754138326444`
  - `RTHUB_FACEBOOK_CLIENT_SECRET=5d8e0308df7ea9970512163adf140397`
  - `RTHUB_FACEBOOK_CLIENT_REDIRECT_URL=`
  ### Google
  - `RTHUB_GOOGLE_CLIENT_ID=1047537031287-eaqd28r2i5ftmt0qcrg2vqhnrtf1nk7p.apps.googleusercontent.com`
  - `RTHUB_GOOGLE_CLIENT_SECRET=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`
  - `RTHUB_GOOGLE_CLIENT_REDIRECT_URL=`
    
### Important Note
`RTHUB_FACEBOOK_CLIENT_REDIRECT_URL=` and `RTHUB_GOOGLE_CLIENT_REDIRECT_URL=` values can be filled with a simple custom artisan command specifying either the site/platform `code` or `name` attribute, and the social network provider, like so:
- `php artisan client:make-socials-callback-url rthub facebook`
and
- `php artisan client:make-socials-callback-url rthub google`
respectfully.

Also, redirect links for buttons on the frontend/client-site can be programmatically generated in a similar fashion; The third parameter in this case accepts the string value of the URL+path where the button will be clicked from. Example:
### Register page of runthrough.runthroughhub.com
`php artisan client:make-socials-redirect-url rthub github runthrough.runthroughhub.com/auth/register`

## Database
- You need to seed the database with data from the [sport-for-api](https://github.com/sports-media-agency/sport-for-api) database. Ensure to setup the values for the environment variables below:-

```
DB_HOST_2=
DB_PORT_2=
DB_DATABASE_2=
DB_USERNAME_2=
DB_PASSWORD_2=
```
- The seeder seeds data for all the sites (platforms) present in the sites table of the [sport-for-api](https://github.com/sports-media-agency/sport-for-api) database. Set the domain name of the site for which the data is to be seeded under the .env variable `X_Seeded_Site`. The X_Seeded_Site is used to ensure that when creating a new record for a model that has the site_id property, the set site is used instead of creating a new site for the record (through the Site::factory()).
- Use the command below to seed the data.

```
php artisan db:seed
```

## Third Parties Keys & Tokens
#### LDT_TOKEN (Lets Do This Token)
- This token can be obtained [here](https://sportstechsolutions.slack.com/archives/C02TBFKKHM2/p1667469681807239).

#### TWITTER_BEARER_TOKEN
- Authenticate to the twitter developer account using [these](https://sportstechsolutions.slack.com/archives/C02T97REQ0K/p1645701603057659) credentials and obtain the twitter bearer token.

#### GOOGLE_API_KEY
- Request for this from one of the developers.

## API Documentation
We use Scribe to generate the API documentation. Use the command below to generate the API documentation.
```
php artisan scribe:generate
```
