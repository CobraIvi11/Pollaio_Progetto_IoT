-- ============================================================
--  DATABASE: pollaio_iot
--  Progetto: Pollaio IoT WebApp
--  Autore:   CobraIvi11
-- ============================================================

CREATE DATABASE IF NOT EXISTS pollaio_iot
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pollaio_iot;

-- ------------------------------------------------------------
-- TABELLA: utenti
-- Gestisce gli account degli utenti (login/registrazione)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utenti (
                                      id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                                      nome          VARCHAR(50)     NOT NULL,
    cognome       VARCHAR(50)     NOT NULL,
    email         VARCHAR(150)    NOT NULL UNIQUE,
    password      VARCHAR(255)    NOT NULL,           -- hash bcrypt
    creato_il     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    aggiornato_il DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
    ) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABELLA: dati_sensori
-- Storico di tutte le letture ricevute via MQTT dall'ESP32
-- Struttura allineata al listener (listen.php):
--   payload JSON => { "acqua": ..., "luce": ..., "temperatura": ..., "umidita": ... }
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS dati_sensori (
                                            id            BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
                                            acqua_perc    DECIMAL(5,2)     DEFAULT NULL COMMENT 'Livello acqua in %',
    luce_val      DECIMAL(7,2)     DEFAULT NULL COMMENT 'Valore luminosità (lux o 0-100)',
    temperatura   DECIMAL(5,2)     DEFAULT NULL COMMENT 'Temperatura in °C',
    umidita       DECIMAL(5,2)     DEFAULT NULL COMMENT 'Umidità relativa in %',
    ricevuto_il   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp ricezione MQTT',
    PRIMARY KEY (id),
    INDEX idx_tempo (ricevuto_il)
    ) ENGINE=InnoDB COMMENT='Dati telemetria ESP32 dal topic pollaio/telemetria';


-- ------------------------------------------------------------
-- INSERIMENTO DATI DI ESEMPIO (Tabella utenti)
-- Nota: Le password sotto sono stringhe in chiaro di esempio.
-- In produzione usa hash generati con password_hash() in PHP.
-- ------------------------------------------------------------
INSERT INTO utenti (nome, cognome, email, password) VALUES
                                                        ('Mario', 'Rossi', 'mario.rossi@example.com', 'Password1234!'),
                                                        ('Luigi', 'Verdi', 'luigi.verdi@example.com', 'Verdi1234!'),
                                                        ('Giulia', 'Bianchi', 'giulia.bianchi@example.com', 'Bianchi1234!');