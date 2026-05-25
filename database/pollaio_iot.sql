-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 18, 2026 alle 10:19
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pollaio_iot`
--
CREATE DATABASE IF NOT EXISTS `pollaio_iot` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pollaio_iot`;

-- --------------------------------------------------------

--
-- Struttura della tabella `dati_sensori`
--

DROP TABLE IF EXISTS `dati_sensori`;
CREATE TABLE IF NOT EXISTS `dati_sensori` (
                                              `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `acqua_perc` decimal(5,2) DEFAULT NULL COMMENT 'Livello acqua in %',
    `luce_val` decimal(7,2) DEFAULT NULL COMMENT 'Valore luminosità (lux o 0-100)',
    `temperatura` decimal(5,2) DEFAULT NULL COMMENT 'Temperatura in °C',
    `umidita` decimal(5,2) DEFAULT NULL COMMENT 'Umidità relativa in %',
    `ricevuto_il` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Timestamp ricezione MQTT',
    PRIMARY KEY (`id`),
    KEY `idx_tempo` (`ricevuto_il`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dati telemetria ESP32 dal topic pollaio/telemetria';

-- --------------------------------------------------------

--
-- Struttura della tabella `orari_mangime`
--

DROP TABLE IF EXISTS `orari_mangime`;
CREATE TABLE IF NOT EXISTS `orari_mangime` (
                                               `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_utente` int(10) UNSIGNED NOT NULL,
    `pasto_mattina` time NOT NULL,
    `pasto_pomeriggio` time NOT NULL,
    `data_creazione` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_utente` (`id_utente`)
    ) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `orari_mangime`
--

INSERT INTO `orari_mangime` (`id`, `id_utente`, `pasto_mattina`, `pasto_pomeriggio`, `data_creazione`) VALUES
                                                                                                           (9, 10, '08:30:00', '17:00:00', '2026-05-16 16:25:01'),
                                                                                                           (10, 8, '08:00:00', '17:50:00', '2026-05-16 16:28:35');

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

DROP TABLE IF EXISTS `utenti`;
CREATE TABLE IF NOT EXISTS `utenti` (
                                        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome` varchar(50) NOT NULL,
    `cognome` varchar(50) NOT NULL,
    `email` varchar(150) NOT NULL,
    `password` varchar(255) NOT NULL,
    `pin` varchar(255) DEFAULT NULL COMMENT 'PIN app mobile (hash SHA2)',
    `creato_il` datetime NOT NULL DEFAULT current_timestamp(),
    `aggiornato_il` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`id`, `nome`, `cognome`, `email`, `password`, `creato_il`, `aggiornato_il`) VALUES
                                                                                                      (8, 'Mario', 'Rossi', 'mario.rossi@example.com', '*6F44F3EB8058831613BCEF7D504F04AF5BD34F78', '2026-05-16 11:10:47', '2026-05-16 11:10:47'),
                                                                                                      (9, 'ivan', 'viero', 'ivanviero@example.com', '*6F44F3EB8058831613BCEF7D504F04AF5BD34F78', '2026-05-16 15:09:07', '2026-05-16 15:09:07'),
                                                                                                      (10, 'mattia', 'boaro', 'boaro@example.com', '*91169DB308171F2A1F7FB353D835EFDB4B8A72C9', '2026-05-16 18:17:27', '2026-05-16 18:17:27');

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `orari_mangime`
--
ALTER TABLE `orari_mangime`
    ADD CONSTRAINT `orari_mangime_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
