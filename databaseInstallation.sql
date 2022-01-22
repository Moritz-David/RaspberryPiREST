# Datenbank erstellen
CREATE DATABASE 'temperatur';
# Tabelle erstellen
USE temperatur;
CREATE TABLE IF NOT EXISTS messungen
(
    id         INT AUTO_INCREMENT,
    temperatur DECIMAL(5, 1),
    raum       VARCHAR(10),
    measuredAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

# !!!!!!! ACHTUNG !!!!!!!
# myuser durch den neuen Benutzernamen ersetzen
# mypassword durchh das neue Passwort ersetzen
CREATE USER 'myuser'@localhost IDENTIFIED BY 'mypassword';
GRANT USAGE ON *.* TO 'myuser'@localhost IDENTIFIED BY 'mypassword';
GRANT ALL privileges ON temperatur.* TO 'myuser'@localhost;
FLUSH PRIVILEGES;
# !!!!!!! ACHTUNG !!!!!!!