Geforp2 : gestion des formations
======

Geforp2 : nouvelle version sous Symfony 5.4

Configuration requise
------------

### PHP

* version 7.4 minimum 
* extensions :
    * json
    * ctype
* modules :
    * pdo_mysql
    * openssl
    * apc
    * mbstring
    * curl
    * fileinfo

### Symfony5.4

### MySQL

### Unoconv

### Shibboleth

-----------------
Prérequis
------------

### Prérequis

- Composer installé (>=2)
- git installé
- Installer node v0.12.17
- Installer npm v2.15.1
- Installer bower : npm install bower@1.8.12 -g
- Installer gulp : npm install gulp@3.9.1 -g. Si besoin npm link gulp (local version)

-----------------
Installation
-----------------

### Projet

* git clone https://weblab.univ-amu.fr/polen/geforp2.git
* composer install
* Renommer le fichier .env.sample en .env et configurer la base de données (user et base de données)

### Commandes base de données
* php bin/console doctrine:database:create
* php bin/console make:migration
* php bin/console doctrine:migrations:migrate

### Angular
* Commandes : 
  * bower install (bower.json)
  * Vérifier le répertoire node_modules (npm install (package.json))
  * Exposer les routes pour js :
    * php bin/console fos:js-routing:dump --target=public/js/fos_js_routes.js
  * gulp 