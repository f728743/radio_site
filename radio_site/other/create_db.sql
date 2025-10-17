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

-- Таблица серий радиостанций Sim Radio
CREATE TABLE IF NOT EXISTS `sim_radio_series` (
  `id` VARCHAR(50) NOT NULL,
  `url` TEXT,
  `title` VARCHAR(255) NOT NULL,
  `logo` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица радиостанций Sim Radio внутри серий
CREATE TABLE IF NOT EXISTS `sim_radio_stations` (
  `id` VARCHAR(100) NOT NULL,
  `series_id` VARCHAR(50) NOT NULL,
  `logo` VARCHAR(255),
  `tags` TEXT,
  `title` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_series_id` (`series_id`),
  KEY `idx_station_title` (`title`),
  KEY `idx_series_title` (`series_id`, `title`),
  KEY `idx_station_tags` (`tags`(255)),
  KEY `idx_series_tags` (`series_id`, `tags`(255)),
  CONSTRAINT `fk_stations_series` 
    FOREIGN KEY (`series_id`) 
    REFERENCES `sim_radio_series` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставляем серии радиостанций
INSERT INTO `sim_radio_series` (`id`, `url`, `title`, `logo`) VALUES
('gta_4_radio', 'https://media.githubusercontent.com/media/maxerohingta/convert_gta5_audio/refs/heads/main/converted_m4a/new_sim_radio_stations.json', 'GTA IV Radio', 'gta_iv'),
('gta_5_radio', 'https://media.githubusercontent.com/media/maxerohingta/convert_gta4_audio/refs/heads/main/result/new_sim_radio_stations.json', 'GTA V Radio', 'textures/gta_5_radio');

-- Вставляем радиостанции для GTA IV
INSERT INTO `sim_radio_stations` (`id`, `series_id`, `logo`, `tags`, `title`) VALUES
('radio_afro_beat', 'gta_4_radio', 'radio_afro_beat/radio_afro_beat', 'funk and afrobeat', 'IF99 - International Funk'),
('radio_babylon', 'gta_4_radio', 'radio_babylon/radio_babylon', 'reggae, dub', 'Tuff Gong Radio'),
('radio_classical_ambient', 'gta_4_radio', 'radio_classical_ambient/radio_classical_ambient', 'smbient', 'The Journey'),
('radio_dance_rock', 'gta_4_radio', 'radio_dance_rock/radio_dance_rock', 'contemporary alternative, indie, electronic rock', 'Radio Broker'),
('radio_fusion_fm', 'gta_4_radio', 'radio_fusion_fm/radio_fusion_fm', 'jazz', 'Fusion FM'),
('radio_hardcore', 'gta_4_radio', 'radio_hardcore/radio_hardcore', 'hardcore punk', 'Liberty City Hardcore'),
('radio_jazz_nation', 'gta_4_radio', 'radio_jazz_nation/radio_jazz_nation', 'classic jazz', 'Jazz Nation Radio 108.5'),
('radio_k109_the_studio', 'gta_4_radio', 'radio_k109_the_studio/radio_k109_the_studio', 'disco', 'K109 The Studio'),
('radio_liberty_rock', 'gta_4_radio', 'radio_liberty_rock/radio_liberty_rock', 'classic rock, alternative rock', 'Liberty Rock Radio'),
('radio_meditation', 'gta_4_radio', 'radio_meditation/radio_meditation', 'ambient', 'Self-Actualization FM'),
('radio_san_juan_sounds', 'gta_4_radio', 'radio_san_juan_sounds/radio_san_juan_sounds', 'reggaeton, latin hiphop, merengue, bachata', 'San Juan Sounds'),
('radio_the_vibe', 'gta_4_radio', 'radio_the_vibe/radio_the_vibe', 'soul and r&b.', 'The Vibe 98.8'),
('radio_vcfm', 'gta_4_radio', 'radio_vcfm/radio_vcfm', '80s Pop', 'Vice City FM'),
('radio_vladivostok', 'gta_4_radio', 'radio_vladivostok/radio_vladivostok', 'eastern european', 'Vladivostok FM'),
('radio_beat_95', 'gta_4_radio', 'radio_beat_95/radio_beat_95', 'contemporary hiphop', 'The Beat 102.7'),
('radio_bobby_konders', 'gta_4_radio', 'radio_bobby_konders/radio_bobby_konders', 'dancehall', 'Massive B Soundsystem 96.9'),
('radio_dance_mix', 'gta_4_radio', 'radio_dance_mix/radio_dance_mix', 'electro house', 'Electro-Choc'),
('radio_ny_classics', 'gta_4_radio', 'radio_ny_classics/radio_ny_classics', 'classic hiphop', 'The Classics 104.1'),
('radio_ramjamfm', 'gta_4_radio', 'radio_ramjamfm/radio_ramjamfm', 'reggae', 'RamJam FM'),
('radio_lazlow', 'gta_4_radio', 'radio_lazlow/radio_lazlow', 'talk', 'Integrity 2.0'),
('radio_plr', 'gta_4_radio', 'radio_plr/radio_plr', 'talk', 'Public Liberty Radio'),
('radio_wktt', 'gta_4_radio', 'radio_wktt/radio_wktt', 'talk', 'WKTT Radio');

-- Вставляем радиостанции для GTA V
INSERT INTO `sim_radio_stations` (`id`, `series_id`, `logo`, `tags`, `title`) VALUES
('radio_01_class_rock', 'gta_5_radio', 'textures/radio_01_class_rock', 'classic rock, soft rock, pop rock', 'Los Santos Rock Radio'),
('radio_02_pop', 'gta_5_radio', 'textures/radio_02_pop', 'pop, electronic dance, electro house', 'Non-Stop Pop FM'),
('radio_03_hiphop_new', 'gta_5_radio', 'textures/radio_03_hiphop_new', 'modern hiphop, trap', 'Radio Los Santos'),
('radio_04_punk', 'gta_5_radio', 'textures/radio_04_punk', 'punk rock, hardcore punk, grunge', 'Channel X'),
('radio_05_talk_01', 'gta_5_radio', 'textures/radio_05_talk_01', 'talk', 'WCTR: West Coast Talk Radio'),
('radio_06_country', 'gta_5_radio', 'textures/radio_06_country', 'country, rockabilly', 'Rebel Radio'),
('radio_07_dance_01', 'gta_5_radio', 'textures/radio_07_dance_01', 'electronic', 'Soulwax FM'),
('radio_08_mexican', 'gta_5_radio', 'textures/radio_08_mexican', 'mexican, latin', 'East Los FM'),
('radio_09_hiphop_old', 'gta_5_radio', 'textures/radio_09_hiphop_old', 'golden age hiphop, gangsta rap', 'West Coast Classics'),
('radio_11_talk_02', 'gta_5_radio', 'textures/radio_11_talk_02', 'talk', 'Blaine County Talk Radio'),
('radio_12_reggae', 'gta_5_radio', 'textures/radio_12_reggae', 'reggae, dancehall, dub', 'Blue Ark'),
('radio_13_jazz', 'gta_5_radio', 'textures/radio_13_jazz', 'lounge, chillwave, jazz-funk', 'WorldWide FM'),
('radio_14_dance_02', 'gta_5_radio', 'textures/radio_14_dance_02', 'idm,midwest hiphop', 'FlyLo FM'),
('radio_15_motown', 'gta_5_radio', 'textures/radio_15_motown', 'classic soul, disco, gospel', 'The Lowdown 91.1'),
('radio_16_silverlake', 'gta_5_radio', 'textures/radio_16_silverlake', 'indie pop, synthpop, indietronica, chillwave', 'Radio Mirror Park'),
('radio_17_funk', 'gta_5_radio', 'textures/radio_17_funk', 'funk, r&b', 'Space 103.2'),
('radio_18_90s_rock', 'gta_5_radio', 'textures/radio_18_90s_rock', 'garage rock, alternative rock, noise rock', 'Vinewood Boulevard Radio'),
('radio_20_thelab', 'gta_5_radio', 'textures/radio_20_thelab', 'hiphop', 'The Lab'),
('radio_21_dlc_xm17', 'gta_5_radio', 'textures/radio_21_dlc_xm17', 'hiphop', 'Blonded Los Santos 97.8 FM'),
('radio_22_dlc_battle_mix1_radio', 'gta_5_radio', 'textures/radio_22_dlc_battle_mix1_radio', 'house, techno', 'Los Santos Underground Radio'),
('radio_23_dlc_xm19_radio', 'gta_5_radio', 'textures/radio_23_dlc_xm19_radio', 'modern hiphop, uk rap, afrofusion', 'iFruit Radio'),
('radio_27_dlc_prhei4', 'gta_5_radio', 'textures/radio_27_dlc_prhei4', 'electronic, house, techno', 'Still Slipping Los Santos'),
('radio_34_dlc_hei4_kult', 'gta_5_radio', 'textures/radio_34_dlc_hei4_kult', 'alternative rock', 'Kult FM'),
('radio_35_dlc_hei4_mlr', 'gta_5_radio', 'textures/radio_35_dlc_hei4_mlr', 'house, disco, techno', 'The Music Locker'),
('radio_37_motomami', 'gta_5_radio', 'textures/radio_37_motomami', 'latin', 'MOTOMAMI Los Santos');