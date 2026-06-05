-- ============================================================
--  ColdReach — AI Cold Email Personalizer
--  Database schema
--
--  Usage:
--    mysql -u root -p < config/schema.sql
--  or paste into phpMyAdmin (SQL tab).
-- ============================================================

CREATE DATABASE IF NOT EXISTS coldemail
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE coldemail;

-- ------------------------------------------------------------
--  users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  name               VARCHAR(100),
  email              VARCHAR(100) UNIQUE,
  password           VARCHAR(255),
  plan               ENUM('free','starter','pro','agency') DEFAULT 'free',
  credits            INT DEFAULT 10,
  stripe_customer_id VARCHAR(100),
  created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
--  campaigns
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS campaigns (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT,
  name            VARCHAR(100),
  template        TEXT,
  status          ENUM('pending','processing','done','failed') DEFAULT 'pending',
  total_leads     INT DEFAULT 0,
  processed_leads INT DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
--  leads
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS leads (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  campaign_id       INT,
  first_name        VARCHAR(100),
  last_name         VARCHAR(100),
  email             VARCHAR(100),
  company           VARCHAR(100),
  role              VARCHAR(100),
  linkedin_url      VARCHAR(255),
  website           VARCHAR(255),
  raw_context       TEXT,
  generated_subject VARCHAR(255),
  generated_email   TEXT,
  status            ENUM('pending','done','failed') DEFAULT 'pending',
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
--  payments
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  user_id           INT,
  amount            INT,
  credits_added     INT,
  stripe_payment_id VARCHAR(100),
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Helpful indexes for the cron queue and dashboards
CREATE INDEX idx_leads_status      ON leads (status);
CREATE INDEX idx_leads_campaign    ON leads (campaign_id);
CREATE INDEX idx_campaigns_user    ON campaigns (user_id);
