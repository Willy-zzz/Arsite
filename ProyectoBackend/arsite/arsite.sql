/*
Navicat MySQL Data Transfer

Source Server         : Ar_Site
Source Server Version : 50505
Source Host           : localhost:3306
Source Database       : arsite

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2025-11-08 07:38:14
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for banners
-- ----------------------------
DROP TABLE IF EXISTS `banners`;
CREATE TABLE `banners` (
  `ban_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ban_titulo` varchar(100) NOT NULL,
  `ban_subtitulo` varchar(200) NOT NULL,
  `ban_texto_boton` varchar(50) NOT NULL DEFAULT 'Saber más',
  `ban_enlace_boton` varchar(255) NOT NULL,
  `ban_imagen` varchar(255) NOT NULL,
  `ban_fecha_publicacion` date DEFAULT NULL,
  `ban_fecha_terminacion` date DEFAULT NULL,
  `ban_orden` int(10) unsigned DEFAULT NULL,
  `ban_estatus` enum('Publicado','Guardado') NOT NULL DEFAULT 'Guardado',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ban_id`),
  KEY `banners_user_id_foreign` (`user_id`),
  CONSTRAINT `banners_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of banners
-- ----------------------------
INSERT INTO `banners` VALUES ('5', 'Prueba tres', 'Subtitulo prueba uno', 'Click prueba', 'https://gemini.google.com', 'banners/20251027102106_1761560466_68ff479268436.jpg', null, null, null, 'Guardado', '2', '2025-10-27 10:21:06', '2025-10-27 10:21:06');
INSERT INTO `banners` VALUES ('6', 'Prueba cuatro', 'Subtitulo prueba uno', 'Click prueba', 'https://gemini.google.com', 'banners/20251027102112_1761560472_68ff4798399de.jpg', null, null, null, 'Guardado', '2', '2025-10-27 10:21:12', '2025-10-27 10:21:12');

-- ----------------------------
-- Table structure for cache
-- ----------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of cache
-- ----------------------------

-- ----------------------------
-- Table structure for cache_locks
-- ----------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of cache_locks
-- ----------------------------

-- ----------------------------
-- Table structure for clientes
-- ----------------------------
DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `cli_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cli_nombre` varchar(60) NOT NULL,
  `cli_logo` varchar(255) NOT NULL,
  `cli_fecha_publicacion` date DEFAULT NULL,
  `cli_fecha_terminacion` date DEFAULT NULL,
  `cli_estatus` enum('Publicado','Guardado') NOT NULL DEFAULT 'Guardado',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`cli_id`),
  KEY `clientes_user_id_foreign` (`user_id`),
  CONSTRAINT `clientes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of clientes
-- ----------------------------

-- ----------------------------
-- Table structure for contactos
-- ----------------------------
DROP TABLE IF EXISTS `contactos`;
CREATE TABLE `contactos` (
  `con_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `con_nombre` varchar(100) NOT NULL,
  `con_email` varchar(150) NOT NULL,
  `con_telefono` varchar(20) DEFAULT NULL,
  `con_asunto` varchar(200) DEFAULT NULL,
  `con_mensaje` text NOT NULL,
  `con_empresa` varchar(100) DEFAULT NULL,
  `con_estado` enum('Nuevo','Leido','Respondido','Archivado') NOT NULL DEFAULT 'Nuevo',
  `con_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`con_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of contactos
-- ----------------------------
INSERT INTO `contactos` VALUES ('2', 'Elias Roman García', 'elias.perez@hotmail.com', '99356234567', 'Solicitud de respuesta', 'Estoy interesado en conocer más sobre sus servicios de integración tecnológica. ¿Podrían enviarme información y precios?', 'Tech Solutions SA', 'Leido', '127.0.0.1', '2025-10-19 07:38:46', '2025-10-19 07:45:50');
INSERT INTO `contactos` VALUES ('3', 'Ana Roman García', 'ana.perez@hotmail.com', '99356234567', 'Solicitud de respuesta', 'Estoy interesado en conocer más sobre sus servicios de integración tecnológica. ¿Podrían enviarme información y precios?', 'Tech Solutions SA', 'Leido', '127.0.0.1', '2025-10-19 07:38:54', '2025-10-19 07:45:50');

-- ----------------------------
-- Table structure for destacados
-- ----------------------------
DROP TABLE IF EXISTS `destacados`;
CREATE TABLE `destacados` (
  `des_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `des_titulo` varchar(50) NOT NULL,
  `des_subtitulo` varchar(80) NOT NULL,
  `des_texto_boton` varchar(40) NOT NULL,
  `des_enlace_boton` varchar(255) NOT NULL,
  `des_imagen` varchar(255) NOT NULL,
  `des_fecha_publicacion` date DEFAULT NULL,
  `des_fecha_terminacion` date DEFAULT NULL,
  `des_orden` int(10) unsigned DEFAULT NULL,
  `des_estatus` enum('Publicado','Guardado') NOT NULL DEFAULT 'Guardado',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`des_id`),
  KEY `destacados_user_id_foreign` (`user_id`),
  CONSTRAINT `destacados_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of destacados
-- ----------------------------

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of failed_jobs
-- ----------------------------

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of jobs
-- ----------------------------

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of job_batches
-- ----------------------------

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO `migrations` VALUES ('97', '0001_01_01_000000_create_users_table', '1');
INSERT INTO `migrations` VALUES ('98', '0001_01_01_000001_create_cache_table', '1');
INSERT INTO `migrations` VALUES ('99', '0001_01_01_000002_create_jobs_table', '1');
INSERT INTO `migrations` VALUES ('100', '2025_08_07_191311_create_banners_table', '1');
INSERT INTO `migrations` VALUES ('101', '2025_08_09_001503_create_destacados_table', '1');
INSERT INTO `migrations` VALUES ('102', '2025_08_09_001723_create_productos_table', '1');
INSERT INTO `migrations` VALUES ('103', '2025_08_09_002036_create_servicios_table', '1');
INSERT INTO `migrations` VALUES ('104', '2025_08_09_002142_create_partners_table', '1');
INSERT INTO `migrations` VALUES ('105', '2025_08_09_002347_create_clientes_table', '1');
INSERT INTO `migrations` VALUES ('106', '2025_08_09_002802_create_noticias_table', '1');
INSERT INTO `migrations` VALUES ('107', '2025_08_17_222703_create_contactos_table', '1');
INSERT INTO `migrations` VALUES ('108', '2025_08_18_213707_create_personal_access_tokens_table', '1');

-- ----------------------------
-- Table structure for noticias
-- ----------------------------
DROP TABLE IF EXISTS `noticias`;
CREATE TABLE `noticias` (
  `not_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `not_titulo` varchar(100) NOT NULL,
  `not_subtitulo` varchar(300) DEFAULT NULL,
  `not_descripcion` text NOT NULL,
  `not_portada` varchar(255) NOT NULL,
  `not_imagen` varchar(255) DEFAULT NULL,
  `not_video` varchar(255) DEFAULT NULL,
  `not_publicacion` datetime DEFAULT NULL,
  `not_estatus` enum('Publicado','Guardado') NOT NULL DEFAULT 'Guardado',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`not_id`),
  KEY `noticias_user_id_foreign` (`user_id`),
  CONSTRAINT `noticias_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of noticias
-- ----------------------------
INSERT INTO `noticias` VALUES ('2', 'Prueba_1', 'Prueba_sub_1', 'Prueba_sub', 'noticias/portadas/1760659186_portada_68f186f2b7ae9.png', 'noticias/imagenes/1760659186_imagen_68f186f2bba19.png', 'https://www.youtube.com/watch?v=p4yw8K--Qpo', '2025-10-16 23:59:46', 'Publicado', '2', '2025-10-16 23:59:46', '2025-10-16 23:59:46');

-- ----------------------------
-- Table structure for partners
-- ----------------------------
DROP TABLE IF EXISTS `partners`;
CREATE TABLE `partners` (
  `par_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `par_nombre` varchar(50) NOT NULL,
  `par_logo` varchar(255) NOT NULL,
  `par_fecha_publicacion` date DEFAULT NULL,
  `par_fecha_terminacion` date DEFAULT NULL,
  `par_estatus` enum('Publicado','Guardado') NOT NULL DEFAULT 'Guardado',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`par_id`),
  KEY `partners_user_id_foreign` (`user_id`),
  CONSTRAINT `partners_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of partners
-- ----------------------------

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of password_reset_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of personal_access_tokens
-- ----------------------------
INSERT INTO `personal_access_tokens` VALUES ('1', 'App\\Models\\User', '2', 'auth-token', '550752a7b27bce988ad745aaf22cc48a1f2915952e2bdfcc070ba0149f04bd45', '[\"*\"]', '2025-10-17 14:36:26', null, '2025-10-16 23:01:28', '2025-10-17 14:36:26');
INSERT INTO `personal_access_tokens` VALUES ('2', 'App\\Models\\User', '2', 'auth-token', 'ccd0c2521c55859a562f45184da0964c4d674722f5eedc02c44c8bb9ab64585e', '[\"*\"]', null, null, '2025-10-17 12:29:09', '2025-10-17 12:29:09');
INSERT INTO `personal_access_tokens` VALUES ('3', 'App\\Models\\User', '2', 'auth-token', '4a4ad4683d3c70e49ecef9de8c3a4bcdc3f7387cb9fed0a88784fecd09eb7915', '[\"*\"]', '2025-10-17 14:37:31', null, '2025-10-17 14:36:52', '2025-10-17 14:37:31');
INSERT INTO `personal_access_tokens` VALUES ('4', 'App\\Models\\User', '2', 'auth-token', '4942fb0afe32c9555ee74706f833933f18dc379a0086e2a232a73d7f8fe78f68', '[\"*\"]', '2025-10-18 23:45:16', null, '2025-10-18 21:47:13', '2025-10-18 23:45:16');
INSERT INTO `personal_access_tokens` VALUES ('5', 'App\\Models\\User', '2', 'auth-token', '8fb5cb104a6c482f63fc7952fb5e354263976ce7d5963f034aef64240ae632fe', '[\"*\"]', null, null, '2025-10-18 23:38:39', '2025-10-18 23:38:39');
INSERT INTO `personal_access_tokens` VALUES ('6', 'App\\Models\\User', '2', 'auth-token', 'c46f27d609d29f3e5e0baff4e6da7ba50900eebb4cca401c7ba1e0ed25df8edc', '[\"*\"]', '2025-10-19 00:45:08', null, '2025-10-19 00:31:35', '2025-10-19 00:45:08');
INSERT INTO `personal_access_tokens` VALUES ('7', 'App\\Models\\User', '2', 'auth-token', 'fd2b8893910a24d6563a731a94f33c71c1fc2299c94aa8facb34fdd66b950e0d', '[\"*\"]', '2025-10-19 07:33:15', null, '2025-10-19 07:31:16', '2025-10-19 07:33:15');
INSERT INTO `personal_access_tokens` VALUES ('8', 'App\\Models\\User', '2', 'auth-token', '53c055da0dc3197cb61a2af4c81f038e35e08442347a318c13eaf9c7a47cd3b7', '[\"*\"]', '2025-10-19 08:14:58', null, '2025-10-19 07:40:28', '2025-10-19 08:14:58');
INSERT INTO `personal_access_tokens` VALUES ('9', 'App\\Models\\User', '2', 'auth-token', '03532dd68ea52bb77d373056bf9c46aed0b880b489cf03a4110c2fa353efe233', '[\"*\"]', null, null, '2025-10-20 18:41:02', '2025-10-20 18:41:02');
INSERT INTO `personal_access_tokens` VALUES ('10', 'App\\Models\\User', '2', 'web_token', '497c8c3ee5c1cb126536fb77567a853472265efcc90ebe78d0e663e0117b4ea4', '[\"create\",\"read\",\"update\",\"delete\",\"manage-users\",\"view-statistics\",\"export-data\",\"bulk-operations\"]', '2025-10-27 01:40:44', null, '2025-10-27 00:25:55', '2025-10-27 01:40:44');
INSERT INTO `personal_access_tokens` VALUES ('12', 'App\\Models\\User', '2', 'web_token', '77c5dda23dda15f162c430f965464ba06684f0afd0472087172aa647be4f279d', '[\"create\",\"read\",\"update\",\"delete\",\"manage-users\",\"view-statistics\",\"export-data\",\"bulk-operations\"]', null, null, '2025-10-27 03:21:54', '2025-10-27 03:21:54');
INSERT INTO `personal_access_tokens` VALUES ('13', 'App\\Models\\User', '2', 'web_token', '507d518eb32f5abd58f331ed72a440f8d7fcee4f7d42c64d768e8122684c30e6', '[\"create\",\"read\",\"update\",\"delete\",\"manage-users\",\"view-statistics\",\"export-data\",\"bulk-operations\"]', '2025-10-27 03:38:49', null, '2025-10-27 03:22:33', '2025-10-27 03:38:49');
INSERT INTO `personal_access_tokens` VALUES ('19', 'App\\Models\\User', '4', 'auth-token', '7fdcd145836bde58d01ff9a17bcd48eee5e3e1873cd36ad1a0d90990cb3a176d', '[\"create\",\"read\",\"update\",\"view-statistics\"]', null, null, '2025-10-27 04:12:54', '2025-10-27 04:12:54');
INSERT INTO `personal_access_tokens` VALUES ('20', 'App\\Models\\User', '3', 'web_token', '72a3f25108810cd05cc39cf3b392b6789795a003a919d2fbd5a2397baf5da1c9', '[\"create\",\"read\",\"update\",\"view-statistics\"]', '2025-10-27 04:19:57', null, '2025-10-27 04:18:24', '2025-10-27 04:19:57');
INSERT INTO `personal_access_tokens` VALUES ('21', 'App\\Models\\User', '3', 'web_token', 'b1c9e0003ca9c2ffb3b5331afc133d8efff9f9d6cb4cc3f599950892b069b19b', '[\"create\",\"read\",\"update\",\"view-statistics\"]', null, null, '2025-10-27 06:28:44', '2025-10-27 06:28:44');
INSERT INTO `personal_access_tokens` VALUES ('22', 'App\\Models\\User', '2', 'web_token', '114097241d92f0fe188370f0c1b0ee6eaa470e62b50af779b4c76d998cf5a60a', '[\"create\",\"read\",\"update\",\"delete\",\"manage-users\",\"view-statistics\",\"export-data\",\"bulk-operations\"]', '2025-10-27 10:21:12', null, '2025-10-27 06:29:23', '2025-10-27 10:21:12');
INSERT INTO `personal_access_tokens` VALUES ('23', 'App\\Models\\User', '3', 'web_token', 'b6191be6fbbbb42f78f9cedf6c857282632ab3f9cc92755fe72b3659db824dee', '[\"create\",\"read\",\"update\",\"view-statistics\"]', '2025-10-27 06:31:04', null, '2025-10-27 06:30:30', '2025-10-27 06:31:04');
INSERT INTO `personal_access_tokens` VALUES ('24', 'App\\Models\\User', '2', 'web_token', 'f45f441b1f2fcd543281b2bbc0c4c8d8eca725b139d47ead156c887d402f486f', '[\"create\",\"read\",\"update\",\"delete\",\"manage-users\",\"view-statistics\",\"export-data\",\"bulk-operations\"]', '2025-10-27 10:01:10', null, '2025-10-27 10:00:00', '2025-10-27 10:01:10');
INSERT INTO `personal_access_tokens` VALUES ('25', 'App\\Models\\User', '3', 'web_token', 'c09ed3d338a6802a6c723cca89883e8a629d9183a040b4021563aac7be095a63', '[\"create\",\"read\",\"update\",\"view-statistics\"]', '2025-10-27 10:03:33', null, '2025-10-27 10:01:43', '2025-10-27 10:03:33');
INSERT INTO `personal_access_tokens` VALUES ('26', 'App\\Models\\User', '3', 'web_token', '0549698970522fcbc9cb444c335cc9d7b5c30b4e2c8c765063c1fc4a45659020', '[\"create\",\"read\",\"update\",\"view-statistics\"]', null, null, '2025-10-27 10:03:59', '2025-10-27 10:03:59');
INSERT INTO `personal_access_tokens` VALUES ('27', 'App\\Models\\User', '2', 'web_token', 'de78839e202bd39a380f4d598c6f94547dace90ec0de2914a71ec48b5ba64f16', '[\"create\",\"read\",\"update\",\"delete\",\"manage-users\",\"view-statistics\",\"export-data\",\"bulk-operations\"]', null, null, '2025-10-27 10:20:44', '2025-10-27 10:20:44');
INSERT INTO `personal_access_tokens` VALUES ('28', 'App\\Models\\User', '3', 'web_token', '768f2397765e452ac1c3bd87155127da3dea9a819fa282cb2f49502aead8b7cd', '[\"create\",\"read\",\"update\",\"view-statistics\"]', '2025-10-27 10:22:18', null, '2025-10-27 10:21:31', '2025-10-27 10:22:18');
INSERT INTO `personal_access_tokens` VALUES ('29', 'App\\Models\\User', '2', 'web_token', 'ee6a0d1a126e79ce622d81ebf95de8476214d778c9aecc2ed6d0514fa57df849', '[\"create\",\"read\",\"update\",\"delete\",\"manage-users\",\"view-statistics\",\"export-data\",\"bulk-operations\"]', '2025-10-30 00:08:55', null, '2025-10-30 00:05:42', '2025-10-30 00:08:55');

-- ----------------------------
-- Table structure for productos
-- ----------------------------
DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `pro_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pro_nombre` varchar(25) NOT NULL,
  `pro_imagen` varchar(255) NOT NULL,
  `pro_estatus` enum('Publicado','Guardado') NOT NULL DEFAULT 'Guardado',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`pro_id`),
  KEY `productos_user_id_foreign` (`user_id`),
  CONSTRAINT `productos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of productos
-- ----------------------------

-- ----------------------------
-- Table structure for servicios
-- ----------------------------
DROP TABLE IF EXISTS `servicios`;
CREATE TABLE `servicios` (
  `ser_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ser_titulo` varchar(100) NOT NULL,
  `ser_descripcion` text NOT NULL,
  `ser_imagen` varchar(255) DEFAULT NULL,
  `ser_orden` int(10) unsigned NOT NULL DEFAULT 0,
  `ser_estatus` enum('Publicado','Guardado') NOT NULL DEFAULT 'Guardado',
  `ser_fecha_publicacion` date DEFAULT NULL,
  `ser_fecha_terminacion` date DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ser_id`),
  KEY `servicios_user_id_foreign` (`user_id`),
  CONSTRAINT `servicios_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of servicios
-- ----------------------------

-- ----------------------------
-- Table structure for sessions
-- ----------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of sessions
-- ----------------------------
INSERT INTO `sessions` VALUES ('bkYT3mfBswoN6cZ0ir1F4g7n8TxspbgOtKL0MTAQ', null, '127.0.0.1', 'PostmanRuntime/7.48.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiOERhWllMbXhzdUtoS2dBT3RiMDZpajJZZG5rbU5RV2Z6RVlGVkF4UyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', '1760654066');
INSERT INTO `sessions` VALUES ('fdeB8VV1S7Tj8OEnBHd3XZtII17uvVQhyREWBqwJ', null, '127.0.0.1', 'PostmanRuntime/7.49.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZDBBVDFCRkpjM2pnQ240M0ZHd0pNM011RlBvOUQ3Y21Ha0tDMXpVUCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NDE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9zYW5jdHVtL2NzcmYtY29va2llIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', '1760704138');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usu_nombre` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `usu_rol` enum('Administrador','Editor') NOT NULL DEFAULT 'Editor',
  `usu_estado` enum('Activo','Inactivo','Pendiente') NOT NULL DEFAULT 'Pendiente',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES ('1', 'Test User', 'test@example.com', '$2y$12$p/TJKeHfQ3Ek.wPUazFKw./NBpYzuKvAa97w.BNs1OmmcTdRuDzhO', 'Editor', 'Pendiente', 'rovdBJ69O4', '2025-10-16 22:34:05', '2025-10-16 22:34:05');
INSERT INTO `users` VALUES ('2', 'Cinthia', 'cdelacruz@arsite.com', '$2y$12$4800dHsBxSJRvCvvXki0hO3SI3siIO24LHKu51yN4wsOHlTvdqu6u', 'Administrador', 'Activo', null, '2025-10-16 23:01:08', '2025-10-16 23:01:08');
INSERT INTO `users` VALUES ('3', 'Editor Test Actualizado', 'editor_nuevo@test.com', '$2y$12$keGx9Lh5.ZTnSmbzefem/u0vhodlN4GQaXpzYvjPWxh/3.VDKodMK', 'Editor', 'Activo', null, '2025-10-27 03:10:18', '2025-10-27 03:43:10');
INSERT INTO `users` VALUES ('4', 'Otro Usuario', 'editor@test.com', '$2y$12$QfFxGDkk.l8Wy1Lgai0MiuhnBkt8sIW/1LFcNJCsUE6Xp/hQPpg8q', 'Editor', 'Pendiente', null, '2025-10-27 04:12:54', '2025-10-27 04:12:54');
