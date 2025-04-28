# Debian Preseed Generator

Een webapplicatie gebouwd met Laravel, Inertia.js en Vue.js om Debian 12 preseed configuratiebestanden te genereren via een gebruiksvriendelijke interface.

**Status:** In ontwikkeling. Gebruik op eigen risico.

## Features

*   **Webgebaseerde Interface:** Eenvoudig formulier om preseed opties te configureren.
*   **Dynamische Preview:** Genereer een live preview van het preseed bestand op basis van de ingevoerde waarden.
*   **Configuratie Opslaan:** Sla gegenereerde preseed configuraties op in de database.
*   **Unieke Toegang:** Elke opgeslagen configuratie is toegankelijk via een unieke URL (`/preseed/{hash_id}`).
*   **Uitgebreide Opties:** Ondersteuning voor:
    *   Lokalisatie (taal, land, toetsenbord)
    *   Netwerk (DHCP/Statisch, hostname, domain)
    *   Software Mirror (protocol, host, directory, proxy)
    *   Gebruikersaccounts (root aan/uit, standaard gebruiker, wachtwoord hashing)
    *   Partitionering (Begeleid: regular, LVM, crypto)
    *   Software Selectie (Tasksel taken, extra pakketten)
    *   Tijdzone
    *   Bootloader (GRUB)
    *   Late Commands (commando's na installatie)
*   **Basis CIS Hardening:** Optionele checkbox om basis CIS benchmark hardening toe te passen (via `late_command` en specifieke partitionering). **Let op:** Dit garandeert geen volledige compliance.
*   **Wachtwoord Hashing:** Wachtwoorden worden (indien ingevuld) gehasht met SHA-512 voor gebruik in het preseed bestand.

## Technology Stack

*   **Backend:** Laravel 12+
*   **Frontend:** Vue.js 3 (via Inertia.js)
*   **Styling:** Tailwind CSS
*   **Database:** MySQL / MariaDB (of andere door Laravel ondersteunde DB)
*   **Server:** Nginx / Apache

## Installatie

1.  **Clone de repository:**
    ```bash
    git clone https://github.com/rroethof/preseed.roethof.eu.git <project-directory>
    cd <project-directory>
    ```

2.  **Installeer PHP dependencies:**
    ```bash
    composer install
    ```

3.  **Installeer Node.js dependencies:**
    ```bash
    npm install
    ```

4.  **Maak een `.env` bestand aan:**
    Kopieer `.env.example` naar `.env`.
    ```bash
    cp .env.example .env
    ```

5.  **Genereer een applicatiesleutel:**
    ```bash
    php artisan key:generate
    ```

6.  **Configureer je database:**
    Pas de `DB_*` variabelen in je `.env` bestand aan met je database gegevens.
    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=jouw_database_naam
    DB_USERNAME=jouw_database_gebruiker
    DB_PASSWORD=jouw_database_wachtwoord
    ```

7.  **Voer database migraties uit:**
    Dit maakt de benodigde tabellen aan (o.a. `preseed_configs`, `users`).
    ```bash
    php artisan migrate
    ```

8.  **(Optioneel) Seed de database:**
    Als je testgebruikers of andere initiële data wilt toevoegen.
    ```bash
    php artisan db:seed
    ```

9.  **Compileer frontend assets:**
    ```bash
    npm run build
    ```

10. **Configureer je webserver:**
    *   Zorg ervoor dat de document root van je webserver (bv. Nginx of Apache) wijst naar de `/public` directory van het project.
    *   Zorg voor de juiste URL rewriting (standaard Laravel `.htaccess` of Nginx configuratie).

11. **Stel bestandspermissies in:**
    Zorg ervoor dat de webserver schrijfrechten heeft op de `storage` en `bootstrap/cache` directories.
    ```bash
    chown -R www-data:www-data storage bootstrap/cache # Pas 'www-data' aan indien nodig
    chmod -R 775 storage bootstrap/cache
    ```

## Configuratie

De belangrijkste configuratie vindt plaats in het `.env` bestand:

*   `APP_URL`: Stel de hoofd-URL van je applicatie in.
*   `DB_*`: Database connectie details.
*   `MAIL_*`: Mail driver configuratie (indien nodig voor bv. wachtwoord resets).

## Gebruik

1.  Navigeer naar de hoofdpagina van de applicatie (standaard `/` of `/preseed`).
2.  Vul het formulier in met de gewenste Debian installatie opties.
3.  Gebruik de "Genereer Preview" knop om een voorbeeld van het preseed bestand te zien.
4.  Klik op "Genereer & Sla Preseed Configuratie Op" om het formulier te valideren, de configuratie op te slaan en de preseed inhoud te genereren.
5.  Bij succes verschijnt een modal (pop-up) met een link naar de unieke URL van de opgeslagen configuratie (bv. `/preseed/aBcDeF123456`).
6.  Navigeren naar deze unieke URL toont de gegenereerde preseed inhoud als platte tekst, klaar voor gebruik.

## Development

Om de applicatie lokaal te ontwikkelen:

1.  Zorg dat je alle installatiestappen hebt voltooid.
2.  Start de Vite development server (voor hot-reloading van frontend assets):
    ```bash
    npm run dev
    ```
3.  Start de ingebouwde PHP server (of gebruik Valet/Laragon/etc.):
    ```bash
    php artisan serve
    ```
4.  Open de applicatie in je browser (meestal `http://127.0.0.1:8000`).

Om productie-assets te bouwen:
```bash
npm run build
