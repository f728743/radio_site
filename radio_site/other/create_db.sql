DROP TABLE IF EXISTS `ban_reasons`;
CREATE TABLE `ban_reasons` (
  `id` int NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

INSERT INTO `ban_reasons` VALUES 
  (1,'iOS App Transport Security','Станция не поддерживает HTTPS и блокируется политикой безопасности iOS','2025-10-13 08:56:36'),
  (2,'Invalid Stream URL','URL потока невалиден или недоступен','2025-10-13 08:56:36'),
  (3,'Copyright Issues','Проблемы с авторскими правами','2025-10-13 08:56:36'),
  (4,'Malicious Content','Обнаружено вредоносное содержимое','2025-10-13 08:56:36'),
  (5,'Regional Restrictions','Ограничения по региону','2025-10-13 08:56:36'),
  (6,'Technical Issues','Технические проблемы с потоком','2025-10-13 08:56:36'),
  (7,'Duplicate Station','Дублирующая станция с одинаковым URL потока','2025-10-13 08:56:36');


DROP TABLE IF EXISTS `banned_stations`;
CREATE TABLE `banned_stations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `stationuuid` varchar(255) DEFAULT NULL,
  `reason_id` int DEFAULT NULL,
  `banned_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stationuuid` (`stationuuid`),
  KEY `idx_reason_id` (`reason_id`),
  CONSTRAINT `fk_banned_stations_reason_id` FOREIGN KEY (`reason_id`) REFERENCES `ban_reasons` (`id`)
);

DROP TABLE IF EXISTS `radio_stations`;
CREATE TABLE `radio_stations` (
  `stationuuid` varchar(255) NOT NULL,
  `name` varchar(500) DEFAULT NULL,
  `url` text,
  `url_resolved` text,
  `homepage` text,
  `favicon` text,
  `tags` text,
  `country` varchar(100) DEFAULT NULL,
  `countrycode` varchar(10) DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `languagecodes` varchar(50) DEFAULT NULL,
  `votes` int DEFAULT NULL,
  `lastchangetime` datetime DEFAULT NULL,
  `codec` varchar(50) DEFAULT NULL,
  `bitrate` int DEFAULT NULL,
  `hls` tinyint(1) DEFAULT NULL,
  `lastcheckok` tinyint(1) DEFAULT NULL,
  `lastchecktime` datetime DEFAULT NULL,
  `lastcheckoktime` datetime DEFAULT NULL,
  `lastlocalchecktime` datetime DEFAULT NULL,
  `clicktimestamp` datetime DEFAULT NULL,
  `clickcount` int DEFAULT NULL,
  `clicktrend` int DEFAULT NULL,
  `ssl_error` int DEFAULT NULL,
  `geo_lat` float DEFAULT NULL,
  `geo_long` float DEFAULT NULL,
  PRIMARY KEY (`stationuuid`),
  KEY `idx_url_resolved` (`url_resolved`(100)),
  KEY `idx_url` (`url`(100)),
  KEY `idx_votes` (`votes`),
  KEY `idx_clickcount` (`clickcount`),
  KEY `idx_rs_url_resolved` (`url_resolved`(100)),
  KEY `idx_rs_url` (`url`(100)),
  KEY `idx_rs_votes` (`votes`),
  KEY `idx_rs_clickcount` (`clickcount`),
  KEY `idx_rs_duplicate_search` (`url_resolved`(100),`url`(100)),
  KEY `idx_rs_popularity` (`votes` DESC,`clickcount` DESC)
);

-- Добавляем в other/create_db.sql
DROP TABLE IF EXISTS `image_cache`;
CREATE TABLE `image_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `stationuuid` varchar(255) NOT NULL,
  `original_url` varchar(1000) NOT NULL,
  `local_path` varchar(255) NOT NULL,
  `file_size` int DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `status` enum('pending','success','error') DEFAULT 'pending',
  `error_message` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_original_url` (`original_url`(255)),
  KEY `idx_stationuuid` (`stationuuid`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
);