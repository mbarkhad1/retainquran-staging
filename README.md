# Getting started

## Installation

Please check the official laravel installation guide for server requirements before you start. [Official Documentation](https://laravel.com/docs/5.4/installation#installation)

Alternative installation is possible without local dependencies relying on [Docker](#docker).

Clone the repository

    git clone https://dynamologic.codebasehq.com/retain-quran/rq-ss.git

Switch to the repo folder

    cd rq-ss

Install all the dependencies using composer

    composer install

Copy the example env file and make the required configuration changes in the .env file

    cp .env.example .env

Generate a new application key

    php artisan key:generate

Generate a new JWT authentication secret key

    php artisan jwt:generate

Run the database migrations (**Set the database connection in .env before migrating**)

    php artisan migrate

Start the local development server

    php artisan serve

You can now access the server at http://localhost:8000

**TL;DR command list**

    git clone https://dynamologic.codebasehq.com/retain-quran/rq-ss.git
    cd rq-ss
    composer install
    cp .env.example .env
    php artisan key:generate
    php artisan jwt:generate

**Make sure you set the correct database connection information before running the migrations** [Environment variables](#environment-variables)

    php artisan migrate
    php artisan serve

## Database seeding

**Populate the database with seed data with relationships which includes users, articles, comments, tags, favorites and follows. This can help you to quickly start testing the api or couple a frontend and start using it with ready content.**

Open the DummyDataSeeder and set the property values as per your requirement

    database/seeds/DummyDataSeeder.php

Run the database seeder and you're done

    php artisan db:seed

**_Note_** : It's recommended to have a clean database before seeding. You can refresh your migrations at any point to clean the database by running the following command

    php artisan migrate:refresh

## Docker

To install with [Docker](https://www.docker.com) see the 'Dockerfile' at the root of the repository. 

## Folders

-   `app` - Contains all the Eloquent models
-   `app/Http/Controllers/Api` - Contains all the api controllers
-   `app/Http/Middleware` - Contains the JWT auth middleware
-   `app/Http/Requests/Api` - Contains all the api form requests
-   `config` - Contains all the application configuration files
-   `database/factories` - Contains the model factory for all the models
-   `database/migrations` - Contains all the database migrations
-   `database/seeds` - Contains the database seeder
-   `routes` - Contains all the api routes defined in api.php file
-   `tests` - Contains all the application tests
-   `tests/Feature/Api` - Contains all the api tests

## Environment variables

-   `.env` - Environment variables can be set in this file

**_Note_** : You can quickly set the database information and other variables in this file and have the application fully working.

---

# Testing API

Run the laravel development server

    php artisan serve

The api can now be accessed at

    http://localhost:8000/api

Request headers

| **Required** | **Key**          | **Value**        |
| ------------ | ---------------- | ---------------- |
| Yes          | Content-Type     | application/json |
| Yes          | X-Requested-With | XMLHttpRequest   |
| Yes          | Authorization    | Token {JWT}      |

Refer the [api specification](#api-specification) for more info.

---

# Authentication

This applications uses JSON Web Token (JWT) to handle authentication. The token is passed with each request using the `Authorization` header with `Token` scheme. The JWT authentication middleware handles the validation and authentication of the token. Please check the following sources to learn more about JWT.

-   https://jwt.io/introduction/
-   https://self-issued.info/docs/draft-ietf-oauth-json-web-token.html

---

# Cross-Origin Resource Sharing (CORS)

This applications has CORS enabled by default on all API endpoints. The default configuration allows requests from `http://localhost:3000` and `http://localhost:4200` to help speed up your frontend testing. The CORS allowed origins can be changed by setting them in the config file. Please check the following sources to learn more about CORS.

-   https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
-   https://en.wikipedia.org/wiki/Cross-origin_resource_sharing
-   https://www.w3.org/TR/cors