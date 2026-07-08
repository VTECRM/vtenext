# VTENext CE — Docker Setup

Setup Docker per eseguire [VTENext CE](https://github.com/VTECRM/vtenext) in locale per sviluppo e testing.

## Stack

| Servizio | Immagine | Porta default |
|----------|----------|---------------|
| VTENext (PHP 8.1 + Apache) | build locale | 8080 |
| MySQL 8.0 | `mysql:8.0` | 3306 |
| phpMyAdmin *(opzionale)* | `phpmyadmin:latest` | 8081 |

## Avvio rapido

```bash
# 1. Clona questo repo o copia la cartella docker/
cd mcp/vtenext/docker

# 2. Configura le variabili d'ambiente
cp .env.example .env

# 3. Build e avvio
docker compose up -d --build

# 4. Apri il wizard di installazione
open http://localhost:8080
```

Durante il wizard inserisci:

| Campo | Valore |
|-------|--------|
| DB Host | `db` |
| DB Name | `vtenext` |
| DB User | `vtenext` |
| DB Password | `vtenext` |

> La prima build richiede qualche minuto — deve clonare il repo GitHub e installare le dipendenze Composer.

## phpMyAdmin (opzionale)

```bash
docker compose --profile tools up -d
# Apri http://localhost:8081
```

## Comandi utili

```bash
# Logs in tempo reale
docker compose logs -f vtenext

# Shell nel container
docker exec -it vtenext_app bash

# Ricrea tutto da zero (attenzione: cancella il DB)
docker compose down -v && docker compose up -d --build

# Stop senza cancellare i dati
docker compose stop
```

## Struttura file

```
docker/
├── Dockerfile          # PHP 8.1 + Apache + estensioni VTENext
├── docker-compose.yml  # Orchestrazione servizi
├── php.ini             # Impostazioni PHP richieste da VTENext
├── vhost.conf          # VirtualHost Apache
├── mysql.cnf           # Configurazione MySQL (utf8mb4, native auth)
├── entrypoint.sh       # Script di avvio (crea dir, fix permessi)
├── .env.example        # Template variabili d'ambiente
└── README.md           # Questo file
```

## Estensioni PHP installate

`curl` · `imap` · `xml` · `gd` · `bcmath` · `mbstring` · `zip` · `pdo_mysql` · `mysqli` · `intl` · `opcache` · `apcu`

## Note

- Il sorgente VTENext viene clonato da `https://github.com/VTECRM/vtenext` al momento della build
- Per usare un branch/tag specifico: `docker compose build --build-arg VTENEXT_BRANCH=<tag>`
- I dati sono persistenti nei volumi Docker — sopravvivono ai restart
- Per un ambiente di produzione: aggiungi HTTPS via reverse proxy (Nginx + Let's Encrypt)
