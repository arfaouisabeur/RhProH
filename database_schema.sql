-- PostgreSQL Schema for RHPro
-- Converted from MySQL dump

SET client_encoding = 'UTF8';

-- Drop existing tables
DROP TABLE IF EXISTS service_reaction CASCADE;
DROP TABLE IF EXISTS tache CASCADE;
DROP TABLE IF EXISTS salaire CASCADE;
DROP TABLE IF EXISTS prime CASCADE;
DROP TABLE IF EXISTS reponse CASCADE;
DROP TABLE IF EXISTS rating CASCADE;
DROP TABLE IF EXISTS event_participation CASCADE;
DROP TABLE IF EXISTS activite CASCADE;
DROP TABLE IF EXISTS evenement CASCADE;
DROP TABLE IF EXISTS demande_service CASCADE;
DROP TABLE IF EXISTS type_service CASCADE;
DROP TABLE IF EXISTS conge_tt CASCADE;
DROP TABLE IF EXISTS contract CASCADE;
DROP TABLE IF EXISTS projet CASCADE;
DROP TABLE IF EXISTS candidat_offre_favori CASCADE;
DROP TABLE IF EXISTS candidature CASCADE;
DROP TABLE IF EXISTS offre_emploi CASCADE;
DROP TABLE IF EXISTS password_reset_code CASCADE;
DROP TABLE IF EXISTS messenger_messages CASCADE;
DROP TABLE IF EXISTS employe CASCADE;
DROP TABLE IF EXISTS candidat CASCADE;
DROP TABLE IF EXISTS rh CASCADE;
DROP TABLE IF EXISTS "user" CASCADE;

-- Create user table
CREATE TABLE "user" (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    prenom VARCHAR(120) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(40),
    adresse VARCHAR(255),
    role VARCHAR(255),
    avatar_path VARCHAR(500),
    statut VARCHAR(50) DEFAULT 'actif',
    google_id VARCHAR(255)
);

-- Create RH table
CREATE TABLE rh (
    user_id INTEGER PRIMARY KEY REFERENCES "user"(id) ON DELETE CASCADE
);

-- Create Candidat table
CREATE TABLE candidat (
    user_id INTEGER PRIMARY KEY REFERENCES "user"(id) ON DELETE CASCADE,
    niveau_etude VARCHAR(120),
    experience INTEGER NOT NULL DEFAULT 0
);

-- Create Employe table
CREATE TABLE employe (
    user_id INTEGER PRIMARY KEY REFERENCES "user"(id) ON DELETE CASCADE,
    matricule VARCHAR(60) NOT NULL UNIQUE,
    position VARCHAR(120),
    date_embauche DATE NOT NULL
);

-- Create messenger_messages table
CREATE TABLE messenger_messages (
    id SERIAL PRIMARY KEY,
    body TEXT NOT NULL,
    headers TEXT NOT NULL,
    queue_name VARCHAR(190) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    available_at TIMESTAMP NOT NULL,
    delivered_at TIMESTAMP
);
CREATE INDEX idx_messenger_queue ON messenger_messages(queue_name, available_at, delivered_at, id);

-- Create password_reset_code table
CREATE TABLE password_reset_code (
    id SERIAL PRIMARY KEY,
    code VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    user_id INTEGER NOT NULL REFERENCES "user"(id) ON DELETE CASCADE
);

-- Create offre_emploi table
CREATE TABLE offre_emploi (
    id SERIAL PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    localisation VARCHAR(255) NOT NULL,
    type_contrat VARCHAR(100) NOT NULL,
    date_publication DATE NOT NULL,
    date_expiration DATE NOT NULL,
    statut VARCHAR(100) NOT NULL,
    rh_id INTEGER NOT NULL REFERENCES "user"(id),
    latitude DOUBLE PRECISION,
    longitude DOUBLE PRECISION
);

-- Create candidature table
CREATE TABLE candidature (
    id SERIAL PRIMARY KEY,
    date_candidature DATE,
    statut VARCHAR(20) NOT NULL,
    cv_path VARCHAR(500),
    candidat_id INTEGER NOT NULL REFERENCES candidat(user_id) ON DELETE CASCADE,
    offre_emploi_id INTEGER NOT NULL REFERENCES offre_emploi(id) ON DELETE CASCADE,
    cv_original_name VARCHAR(255),
    cv_size BIGINT,
    cv_uploaded_at TIMESTAMP,
    match_score INTEGER,
    match_updated_at TIMESTAMP,
    signature_request_id VARCHAR(255),
    contract_status VARCHAR(50),
    cv_skills TEXT,
    ai_analysis TEXT,
    lettre_motivation TEXT,
    disponibilite VARCHAR(50),
    pretention_salariale INTEGER
);

-- Create candidat_offre_favori table
CREATE TABLE candidat_offre_favori (
    candidat_id INTEGER NOT NULL REFERENCES candidat(user_id) ON DELETE CASCADE,
    offre_emploi_id INTEGER NOT NULL REFERENCES offre_emploi(id) ON DELETE CASCADE,
    PRIMARY KEY (candidat_id, offre_emploi_id)
);

-- Create projet table
CREATE TABLE projet (
    id SERIAL PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    statut VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    rh_id INTEGER NOT NULL REFERENCES rh(user_id),
    responsable_employe_id INTEGER REFERENCES employe(user_id),
    date_debut TIMESTAMP NOT NULL,
    date_fin TIMESTAMP NOT NULL,
    is_meeting_requested BOOLEAN NOT NULL DEFAULT FALSE
);

-- Create contract table
CREATE TABLE contract (
    id SERIAL PRIMARY KEY,
    employe_id INTEGER REFERENCES employe(user_id) ON DELETE CASCADE,
    rh_id INTEGER REFERENCES rh(user_id) ON DELETE CASCADE,
    date_debut VARCHAR(255) NOT NULL,
    date_fin VARCHAR(255),
    type VARCHAR(255),
    statut VARCHAR(255),
    salaire_base VARCHAR(255),
    description VARCHAR(255)
);

-- Create prime table
CREATE TABLE prime (
    id SERIAL PRIMARY KEY,
    montant VARCHAR(255) NOT NULL,
    date_attribution VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    contract_id INTEGER REFERENCES contract(id)
);

-- Create salaire table
CREATE TABLE salaire (
    id SERIAL PRIMARY KEY,
    mois VARCHAR(255) NOT NULL,
    annee VARCHAR(255) NOT NULL,
    montant VARCHAR(255) NOT NULL,
    date_paiement VARCHAR(255),
    statut VARCHAR(255) NOT NULL,
    contract_id INTEGER REFERENCES contract(id)
);

-- Create tache table
CREATE TABLE tache (
    id SERIAL PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    statut VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    projet_id INTEGER NOT NULL REFERENCES projet(id),
    employe_id INTEGER NOT NULL REFERENCES employe(user_id),
    prime_id INTEGER REFERENCES prime(id),
    date_debut TIMESTAMP NOT NULL,
    date_fin TIMESTAMP NOT NULL,
    level VARCHAR(255) NOT NULL
);

-- Create conge_tt table
CREATE TABLE conge_tt (
    id SERIAL PRIMARY KEY,
    type_conge VARCHAR(50) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    statut VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    employe_id INTEGER NOT NULL REFERENCES employe(user_id),
    document_path VARCHAR(255),
    ocr_verified BOOLEAN
);

-- Create type_service table
CREATE TABLE type_service (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    categorie VARCHAR(255) NOT NULL,
    description VARCHAR(255)
);

-- Create demande_service table
CREATE TABLE demande_service (
    id SERIAL PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    date_demande VARCHAR(255) NOT NULL,
    statut VARCHAR(255) NOT NULL,
    employe_id INTEGER REFERENCES employe(user_id),
    etape_workflow VARCHAR(255),
    date_derniere_etape VARCHAR(255),
    priorite VARCHAR(255),
    deadline_reponse VARCHAR(255),
    sla_depasse VARCHAR(255),
    pdf_path VARCHAR(255),
    type_id INTEGER NOT NULL REFERENCES type_service(id) ON DELETE CASCADE
);

-- Create reponse table
CREATE TABLE reponse (
    id SERIAL PRIMARY KEY,
    decision VARCHAR(255) NOT NULL,
    commentaire VARCHAR(255),
    rh_id INTEGER REFERENCES rh(user_id),
    employe_id INTEGER REFERENCES employe(user_id),
    conge_tt_id INTEGER REFERENCES conge_tt(id) ON DELETE CASCADE,
    demande_service_id INTEGER REFERENCES demande_service(id) ON DELETE CASCADE
);

-- Create evenement table
CREATE TABLE evenement (
    id SERIAL PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    date_debut VARCHAR(255) NOT NULL,
    date_fin VARCHAR(255) NOT NULL,
    lieu VARCHAR(255) NOT NULL,
    description VARCHAR(255),
    rh_id INTEGER REFERENCES rh(user_id),
    image_url VARCHAR(255),
    latitude DOUBLE PRECISION,
    longitude DOUBLE PRECISION
);

-- Create activite table
CREATE TABLE activite (
    id SERIAL PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description VARCHAR(255),
    evenement_id INTEGER NOT NULL REFERENCES evenement(id) ON DELETE CASCADE
);

-- Create event_participation table
CREATE TABLE event_participation (
    id SERIAL PRIMARY KEY,
    date_inscription VARCHAR(255) NOT NULL,
    statut VARCHAR(255) NOT NULL,
    evenement_id INTEGER NOT NULL REFERENCES evenement(id) ON DELETE CASCADE,
    employe_id INTEGER REFERENCES employe(user_id)
);

-- Create rating table
CREATE TABLE rating (
    id SERIAL PRIMARY KEY,
    evenement_id INTEGER NOT NULL REFERENCES evenement(id) ON DELETE CASCADE,
    employe_id INTEGER REFERENCES employe(user_id),
    commentaire VARCHAR(255) NOT NULL,
    etoiles VARCHAR(255) NOT NULL,
    date_creation VARCHAR(255) NOT NULL
);

-- Create service_reaction table
CREATE TABLE service_reaction (
    id SERIAL PRIMARY KEY,
    reaction VARCHAR(10) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    user_id INTEGER NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    type_service_id INTEGER NOT NULL REFERENCES type_service(id) ON DELETE CASCADE,
    updated_at TIMESTAMP,
    created_by_id INTEGER NOT NULL REFERENCES "user"(id),
    updated_by_id INTEGER REFERENCES "user"(id),
    UNIQUE(user_id, type_service_id)
);
