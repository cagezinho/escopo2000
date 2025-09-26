-- Banco de dados para EscopoSEO
-- Estrutura completa para análise de SEO técnico, conteúdo e IA

CREATE DATABASE IF NOT EXISTS escopo_seo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE escopo_seo;

-- Tabela de análises
CREATE TABLE `analyses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `url` varchar(500) NOT NULL,
    `domain` varchar(255) NOT NULL,
    `max_pages` int(11) DEFAULT 100,
    `respect_robots` tinyint(1) DEFAULT 1,
    `include_external` tinyint(1) DEFAULT 0,
    `status` enum('pending','running','completed','failed') DEFAULT 'pending',
    `progress` int(3) DEFAULT 0,
    `total_pages_found` int(11) DEFAULT 0,
    `total_pages_analyzed` int(11) DEFAULT 0,
    `start_time` timestamp NULL DEFAULT NULL,
    `end_time` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_domain` (`domain`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de páginas analisadas
CREATE TABLE `pages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `analysis_id` int(11) NOT NULL,
    `url` varchar(2000) NOT NULL,
    `canonical_url` varchar(2000) DEFAULT NULL,
    `status_code` int(3) NOT NULL,
    `redirect_url` varchar(2000) DEFAULT NULL,
    `content_type` varchar(100) DEFAULT NULL,
    `page_size` bigint(20) DEFAULT 0,
    `load_time` int(11) DEFAULT 0,
    `ttfb` int(11) DEFAULT 0,
    `response_headers` text,
    `is_indexable` tinyint(1) DEFAULT 1,
    `robots_meta` varchar(500) DEFAULT NULL,
    `is_internal` tinyint(1) DEFAULT 1,
    `depth` int(3) DEFAULT 0,
    `discovered_at` timestamp NULL DEFAULT NULL,
    `analyzed_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`analysis_id`) REFERENCES `analyses`(`id`) ON DELETE CASCADE,
    INDEX `idx_analysis_url` (`analysis_id`, `url`(191)),
    INDEX `idx_status_code` (`status_code`),
    INDEX `idx_is_indexable` (`is_indexable`),
    INDEX `idx_load_time` (`load_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de conteúdo das páginas
CREATE TABLE `page_content` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `page_id` int(11) NOT NULL,
    `title` varchar(1000) DEFAULT NULL,
    `title_length` int(3) DEFAULT 0,
    `meta_description` text DEFAULT NULL,
    `meta_description_length` int(3) DEFAULT 0,
    `h1` varchar(1000) DEFAULT NULL,
    `h1_count` int(3) DEFAULT 0,
    `h2_count` int(3) DEFAULT 0,
    `h3_count` int(3) DEFAULT 0,
    `h4_count` int(3) DEFAULT 0,
    `h5_count` int(3) DEFAULT 0,
    `h6_count` int(3) DEFAULT 0,
    `word_count` int(11) DEFAULT 0,
    `paragraph_count` int(11) DEFAULT 0,
    `image_count` int(11) DEFAULT 0,
    `images_without_alt` int(11) DEFAULT 0,
    `internal_links_count` int(11) DEFAULT 0,
    `external_links_count` int(11) DEFAULT 0,
    `content_hash` varchar(64) DEFAULT NULL,
    `language` varchar(10) DEFAULT NULL,
    `readability_score` decimal(5,2) DEFAULT NULL,
    `keyword_density` json DEFAULT NULL,
    `main_keywords` json DEFAULT NULL,
    `content_analysis` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE,
    INDEX `idx_title_length` (`title_length`),
    INDEX `idx_meta_description_length` (`meta_description_length`),
    INDEX `idx_word_count` (`word_count`),
    INDEX `idx_h1_count` (`h1_count`),
    INDEX `idx_content_hash` (`content_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de links encontrados
CREATE TABLE `links` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `source_page_id` int(11) NOT NULL,
    `target_url` varchar(2000) NOT NULL,
    `target_page_id` int(11) DEFAULT NULL,
    `anchor_text` varchar(1000) DEFAULT NULL,
    `link_type` enum('internal','external','mailto','tel','file') NOT NULL,
    `is_follow` tinyint(1) DEFAULT 1,
    `position` int(11) DEFAULT NULL,
    `is_navigation` tinyint(1) DEFAULT 0,
    `is_footer` tinyint(1) DEFAULT 0,
    `is_sidebar` tinyint(1) DEFAULT 0,
    `is_content` tinyint(1) DEFAULT 1,
    `status_checked` tinyint(1) DEFAULT 0,
    `status_code` int(3) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`source_page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`target_page_id`) REFERENCES `pages`(`id`) ON DELETE SET NULL,
    INDEX `idx_source_page` (`source_page_id`),
    INDEX `idx_target_page` (`target_page_id`),
    INDEX `idx_link_type` (`link_type`),
    INDEX `idx_target_url` (`target_url`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de imagens
CREATE TABLE `images` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `page_id` int(11) NOT NULL,
    `src` varchar(2000) NOT NULL,
    `alt` varchar(1000) DEFAULT NULL,
    `title` varchar(1000) DEFAULT NULL,
    `width` int(11) DEFAULT NULL,
    `height` int(11) DEFAULT NULL,
    `file_size` int(11) DEFAULT NULL,
    `format` varchar(20) DEFAULT NULL,
    `is_lazy_loaded` tinyint(1) DEFAULT 0,
    `is_responsive` tinyint(1) DEFAULT 0,
    `loading_attribute` varchar(20) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE,
    INDEX `idx_page_id` (`page_id`),
    INDEX `idx_alt_missing` (`alt`),
    INDEX `idx_file_size` (`file_size`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de dados estruturados
CREATE TABLE `structured_data` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `page_id` int(11) NOT NULL,
    `type` varchar(100) NOT NULL,
    `schema_type` varchar(100) NOT NULL,
    `data` json NOT NULL,
    `is_valid` tinyint(1) DEFAULT NULL,
    `validation_errors` text DEFAULT NULL,
    `position` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE,
    INDEX `idx_page_type` (`page_id`, `type`),
    INDEX `idx_schema_type` (`schema_type`),
    INDEX `idx_is_valid` (`is_valid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de problemas/oportunidades identificados
CREATE TABLE `issues` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `analysis_id` int(11) NOT NULL,
    `page_id` int(11) DEFAULT NULL,
    `category` enum('technical','content','ai','performance','accessibility') NOT NULL,
    `type` varchar(100) NOT NULL,
    `severity` enum('low','medium','high','critical') DEFAULT 'medium',
    `title` varchar(500) NOT NULL,
    `description` text NOT NULL,
    `recommendation` text DEFAULT NULL,
    `impact_score` int(3) DEFAULT 50,
    `effort_score` int(3) DEFAULT 50,
    `priority_score` decimal(5,2) DEFAULT NULL,
    `data` json DEFAULT NULL,
    `status` enum('open','in_progress','resolved','ignored') DEFAULT 'open',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`analysis_id`) REFERENCES `analyses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE,
    INDEX `idx_analysis_category` (`analysis_id`, `category`),
    INDEX `idx_severity` (`severity`),
    INDEX `idx_priority` (`priority_score`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configurações do robots.txt
CREATE TABLE `robots_analysis` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `analysis_id` int(11) NOT NULL,
    `robots_url` varchar(500) NOT NULL,
    `content` text DEFAULT NULL,
    `is_accessible` tinyint(1) DEFAULT 0,
    `error_message` varchar(500) DEFAULT NULL,
    `user_agents` json DEFAULT NULL,
    `disallowed_paths` json DEFAULT NULL,
    `allowed_paths` json DEFAULT NULL,
    `sitemap_urls` json DEFAULT NULL,
    `crawl_delay` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`analysis_id`) REFERENCES `analyses`(`id`) ON DELETE CASCADE,
    INDEX `idx_analysis_id` (`analysis_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de análise do sitemap
CREATE TABLE `sitemap_analysis` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `analysis_id` int(11) NOT NULL,
    `sitemap_url` varchar(500) NOT NULL,
    `type` enum('xml','txt','index') DEFAULT 'xml',
    `is_accessible` tinyint(1) DEFAULT 0,
    `error_message` varchar(500) DEFAULT NULL,
    `total_urls` int(11) DEFAULT 0,
    `valid_urls` int(11) DEFAULT 0,
    `invalid_urls` int(11) DEFAULT 0,
    `last_modified` timestamp NULL DEFAULT NULL,
    `size_bytes` int(11) DEFAULT NULL,
    `urls` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`analysis_id`) REFERENCES `analyses`(`id`) ON DELETE CASCADE,
    INDEX `idx_analysis_id` (`analysis_id`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de análise de performance
CREATE TABLE `performance_metrics` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `page_id` int(11) NOT NULL,
    `dns_time` int(11) DEFAULT 0,
    `connect_time` int(11) DEFAULT 0,
    `ttfb` int(11) DEFAULT 0,
    `download_time` int(11) DEFAULT 0,
    `total_time` int(11) DEFAULT 0,
    `redirect_count` int(3) DEFAULT 0,
    `resource_count` int(11) DEFAULT 0,
    `css_files` int(11) DEFAULT 0,
    `js_files` int(11) DEFAULT 0,
    `image_files` int(11) DEFAULT 0,
    `font_files` int(11) DEFAULT 0,
    `performance_score` decimal(5,2) DEFAULT NULL,
    `largest_contentful_paint` int(11) DEFAULT NULL,
    `first_input_delay` int(11) DEFAULT NULL,
    `cumulative_layout_shift` decimal(5,3) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE,
    INDEX `idx_page_id` (`page_id`),
    INDEX `idx_total_time` (`total_time`),
    INDEX `idx_performance_score` (`performance_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de análise E-E-A-T
CREATE TABLE `eat_analysis` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `page_id` int(11) NOT NULL,
    `experience_score` decimal(5,2) DEFAULT 0,
    `expertise_score` decimal(5,2) DEFAULT 0,
    `authoritativeness_score` decimal(5,2) DEFAULT 0,
    `trustworthiness_score` decimal(5,2) DEFAULT 0,
    `total_score` decimal(5,2) DEFAULT 0,
    `has_author_info` tinyint(1) DEFAULT 0,
    `has_publication_date` tinyint(1) DEFAULT 0,
    `has_update_date` tinyint(1) DEFAULT 0,
    `has_contact_info` tinyint(1) DEFAULT 0,
    `has_about_page` tinyint(1) DEFAULT 0,
    `has_privacy_policy` tinyint(1) DEFAULT 0,
    `has_terms_service` tinyint(1) DEFAULT 0,
    `external_citations` int(11) DEFAULT 0,
    `authoritative_links` int(11) DEFAULT 0,
    `social_proof_signals` json DEFAULT NULL,
    `content_quality_indicators` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE,
    INDEX `idx_page_id` (`page_id`),
    INDEX `idx_total_score` (`total_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de cluster de conteúdo
CREATE TABLE `content_clusters` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `analysis_id` int(11) NOT NULL,
    `cluster_name` varchar(255) NOT NULL,
    `main_topic` varchar(500) NOT NULL,
    `related_keywords` json DEFAULT NULL,
    `page_count` int(11) DEFAULT 0,
    `content_gap_score` decimal(5,2) DEFAULT NULL,
    `opportunity_level` enum('low','medium','high') DEFAULT 'medium',
    `recommendations` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`analysis_id`) REFERENCES `analyses`(`id`) ON DELETE CASCADE,
    INDEX `idx_analysis_id` (`analysis_id`),
    INDEX `idx_opportunity_level` (`opportunity_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de relacionamento páginas-clusters
CREATE TABLE `page_clusters` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `page_id` int(11) NOT NULL,
    `cluster_id` int(11) NOT NULL,
    `relevance_score` decimal(5,2) DEFAULT NULL,
    `is_primary` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`cluster_id`) REFERENCES `content_clusters`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_page_cluster` (`page_id`, `cluster_id`),
    INDEX `idx_relevance_score` (`relevance_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs de progresso
CREATE TABLE `analysis_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `analysis_id` int(11) NOT NULL,
    `step` varchar(100) NOT NULL,
    `message` text NOT NULL,
    `level` enum('info','warning','error','debug') DEFAULT 'info',
    `data` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`analysis_id`) REFERENCES `analyses`(`id`) ON DELETE CASCADE,
    INDEX `idx_analysis_step` (`analysis_id`, `step`),
    INDEX `idx_level` (`level`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir dados de exemplo de configuração
INSERT INTO `analyses` (`url`, `domain`, `max_pages`, `status`) VALUES 
('https://exemplo.com.br', 'exemplo.com.br', 100, 'completed');

-- Criar usuário específico para a aplicação (opcional)
-- CREATE USER 'escopo_seo'@'localhost' IDENTIFIED BY 'senha_segura_aqui';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON escopo_seo.* TO 'escopo_seo'@'localhost';
-- FLUSH PRIVILEGES;
