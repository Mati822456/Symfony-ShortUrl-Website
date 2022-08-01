# Symfony-ShortUrl-Website
Simple website to shorten long urls after login

![main](images/main.png)

## Table of Contents
* [General Info](#general-info)
* [Technologies](#technologies)
* [Setup](#setup)
* [Project Status](#project-status)
* [Incoming Features](#incoming-features)
* [Acknowledgements](#acknowledgements)
* [Contact](#contact)

## General Info
This website was built with [PHP](https://www.php.net/), MySQL, and [Symfony](https://symfony.com/doc/current/setup.html). When you're logged as a User you can create shortened links and allow them to be in the ranking. Also, you can change your password. As a mod, you can delete websites or if the setting named accepting links is on you can accept or cancel with reason website. Furthermore, you can read messages or delete them. As an Admin, you have access to everything. Starting with users. You can change roles or block users. And with the settings, there is one named accepting links you can only change status.

## Technologies
* Symfony 6.1.2
* Twig 3.4.1
* PHP 8.1.7
* MySQL 8.0.29
* HTML 5
* CSS 3
* JavaScript
* SweetAlert 2
* FontAwesome 6.1.2

## Setup
To run this project you will need to install Symfony, PHP, [Composer](https://getcomposer.org/download/), [NPM](https://www.npmjs.com/package/npm), and MySQL on your local machine.

If you have everything you can run these commands:

```
# Clone this respository
> git clone https://github.com/Mati822456/Symfony-ShortUrl-Website.git

# Go into the respository
> cd Symfony-ShortUrl-Website

# Install Runtime Component
> composer require symfony/runtime

# Install package to compile assets
> npm install --save-dev webpack-notifier

# Compile assets 
> npm run dev

```

`In .env file change db_user, db_password, db_name`

```
# Start server 
> symfony server:start

# Create database
> symfony console doctrine:database:create

# Load migrations
> symfony console doctrine:migrations:migrate

# Create admin, mod, user and some shortened links
> symfony console doctrine:fixtures:load

# Access using
http://localhost:8000

```

Now you can login using created accounts:
```
Role: Admin
Email: admin@db.com
Password: Admin1234

Role: Mod
Email: mod@db.com
Password: Mod1234

Role: User
Email: user@db.com
Password: User1234
```

![admin](images/admin.png)

## Project Status
I'm constantly working on this project. I want to add some new features. :) 

## Incoming Features
* new page dedicated to notifications to tell user which website was deleted or canceled and I want to add button with icon located next to login/logout button
* filtering pages, users and messages
* renew the main page

## Acknowledgements
Thanks to SIMON LURWER for Gradient Banner Cards
`https://dribbble.com/shots/14101951-Banners`\
dcode for bottom navigation bar
`https://codepen.io/dcode-software/pen/wvagWEP`\
Skillthrive for top navigation bar
`https://www.youtube.com/c/Skillthrive`\
Trevor Nestman @FluidOfInsanity for responsive table
`https://codepen.io/FluidOfInsanity/pen/yaLRjd`\
Icon by icons8.com 
`https://icons8.com/icon/stdEXlVErsEe/shorten-urls `

## Contact
Feel free to contact me via email mateusz.zaborski1@gmail.com. :D