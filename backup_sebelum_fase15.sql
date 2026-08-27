-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: sistem_surat
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `barang_alias`
--

DROP TABLE IF EXISTS `barang_alias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barang_alias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sekolah_id` bigint unsigned NOT NULL,
  `master_barang_id` bigint unsigned NOT NULL,
  `nama_alias` varchar(255) NOT NULL,
  `spesifikasi_alias` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `barang_alias_sekolah_id_foreign` (`sekolah_id`),
  KEY `barang_alias_master_barang_id_foreign` (`master_barang_id`),
  CONSTRAINT `barang_alias_master_barang_id_foreign` FOREIGN KEY (`master_barang_id`) REFERENCES `master_barang` (`id`) ON DELETE CASCADE,
  CONSTRAINT `barang_alias_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barang_alias`
--

LOCK TABLES `barang_alias` WRITE;
/*!40000 ALTER TABLE `barang_alias` DISABLE KEYS */;
/*!40000 ALTER TABLE `barang_alias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `barang_masuk`
--

DROP TABLE IF EXISTS `barang_masuk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barang_masuk` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sekolah_id` bigint unsigned NOT NULL,
  `nomor_bpu` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barang_masuk_sekolah_id_nomor_bpu_unique` (`sekolah_id`,`nomor_bpu`),
  CONSTRAINT `barang_masuk_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barang_masuk`
--

LOCK TABLES `barang_masuk` WRITE;
/*!40000 ALTER TABLE `barang_masuk` DISABLE KEYS */;
/*!40000 ALTER TABLE `barang_masuk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `barang_masuk_item`
--

DROP TABLE IF EXISTS `barang_masuk_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barang_masuk_item` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `barang_masuk_id` bigint unsigned NOT NULL,
  `master_barang_id` bigint unsigned NOT NULL,
  `spesifikasi` varchar(255) NOT NULL,
  `satuan` varchar(255) NOT NULL,
  `jumlah` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `barang_masuk_item_barang_masuk_id_foreign` (`barang_masuk_id`),
  KEY `barang_masuk_item_master_barang_id_foreign` (`master_barang_id`),
  CONSTRAINT `barang_masuk_item_barang_masuk_id_foreign` FOREIGN KEY (`barang_masuk_id`) REFERENCES `barang_masuk` (`id`) ON DELETE CASCADE,
  CONSTRAINT `barang_masuk_item_master_barang_id_foreign` FOREIGN KEY (`master_barang_id`) REFERENCES `master_barang` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barang_masuk_item`
--

LOCK TABLES `barang_masuk_item` WRITE;
/*!40000 ALTER TABLE `barang_masuk_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `barang_masuk_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `koreksi_stok`
--

DROP TABLE IF EXISTS `koreksi_stok`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `koreksi_stok` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sekolah_id` bigint unsigned NOT NULL,
  `master_barang_id` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `jumlah` int NOT NULL,
  `alasan` text NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `koreksi_stok_sekolah_id_foreign` (`sekolah_id`),
  KEY `koreksi_stok_master_barang_id_foreign` (`master_barang_id`),
  KEY `koreksi_stok_user_id_foreign` (`user_id`),
  CONSTRAINT `koreksi_stok_master_barang_id_foreign` FOREIGN KEY (`master_barang_id`) REFERENCES `master_barang` (`id`),
  CONSTRAINT `koreksi_stok_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE,
  CONSTRAINT `koreksi_stok_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `koreksi_stok`
--

LOCK TABLES `koreksi_stok` WRITE;
/*!40000 ALTER TABLE `koreksi_stok` DISABLE KEYS */;
/*!40000 ALTER TABLE `koreksi_stok` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_barang`
--

DROP TABLE IF EXISTS `master_barang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_barang` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sekolah_id` bigint unsigned NOT NULL,
  `kode_barang` varchar(255) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `kategori` varchar(255) DEFAULT NULL,
  `satuan_default` varchar(255) NOT NULL,
  `keperluan_default` text,
  `spesifikasi_default` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_barang_sekolah_id_kode_barang_unique` (`sekolah_id`,`kode_barang`),
  CONSTRAINT `master_barang_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_barang`
--

LOCK TABLES `master_barang` WRITE;
/*!40000 ALTER TABLE `master_barang` DISABLE KEYS */;
INSERT INTO `master_barang` VALUES (2,5,'1.1.7.01.03.0001','Tinta Epson Hitam','Bahan Komputer','Botol',NULL,NULL,NULL,'2026-08-25 10:41:05','2026-08-25 10:41:05'),(3,5,'1.1.7.01.01.0002','Tipe Ex','Alat Tulis Kantor','Buah',NULL,NULL,NULL,'2026-08-25 10:41:05','2026-08-25 10:41:05'),(4,5,'1.1.7.01.01.0003','Map Kertas','Alat Tulis Kantor','Buah',NULL,NULL,NULL,'2026-08-25 10:41:05','2026-08-25 10:41:05'),(5,5,'1.1.7.01.01.0004','Kertas F4','Alat Tulis Kantor','Rim',NULL,NULL,NULL,'2026-08-25 10:41:05','2026-08-25 10:41:05');
/*!40000 ALTER TABLE `master_barang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'2026_08_21_141007_create_sekolah_table',1),(4,'2026_08_21_141008_create_users_table',1),(5,'2026_08_22_033432_create_master_barang_table',1),(6,'2026_08_22_033654_create_barang_alias_table',1),(7,'2026_08_22_033832_create_barang_masuk_table',1),(8,'2026_08_22_034043_create_barang_masuk_item_table',1),(9,'2026_08_22_034216_create_koreksi_stok_table',1),(10,'2026_08_22_034352_create_pegawai_table',1),(11,'2026_08_22_034525_create_transaksi_table',1),(12,'2026_08_22_034639_create_transaksi_item_table',1),(13,'2026_08_23_002911_add_nomor_urut_terakhir_to_sekolah_table',1),(14,'2026_08_23_011306_create_paket_subscription_table',1),(15,'2026_08_23_011400_create_subscriptions_table',1),(16,'2026_08_23_161732_rename_midtrans_order_id_to_fersaku_payment_id_in_subscriptions_table',1),(17,'2026_08_24_164658_add_spesifikasi_default_to_master_barang_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paket_subscription`
--

DROP TABLE IF EXISTS `paket_subscription`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paket_subscription` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_paket` varchar(255) NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `durasi_hari` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paket_subscription`
--

LOCK TABLES `paket_subscription` WRITE;
/*!40000 ALTER TABLE `paket_subscription` DISABLE KEYS */;
/*!40000 ALTER TABLE `paket_subscription` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pegawai`
--

DROP TABLE IF EXISTS `pegawai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pegawai` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sekolah_id` bigint unsigned NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nip` varchar(255) DEFAULT NULL,
  `jabatan` varchar(255) NOT NULL,
  `kategori` enum('kepala_sekolah','pengurus_barang_pembantu','guru','tendik') NOT NULL,
  `ttd_path` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pegawai_sekolah_id_foreign` (`sekolah_id`),
  CONSTRAINT `pegawai_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pegawai`
--

LOCK TABLES `pegawai` WRITE;
/*!40000 ALTER TABLE `pegawai` DISABLE KEYS */;
INSERT INTO `pegawai` VALUES (2,5,'Tulus Wahyudi, S.Pd','196604031993071001','Kepala Sekolah','kepala_sekolah',NULL,NULL,'2026-08-25 10:41:05','2026-08-25 10:41:05'),(3,5,'Inayati, S.Pd',NULL,'Pengurus Barang Pembantu','pengurus_barang_pembantu',NULL,NULL,'2026-08-25 10:41:05','2026-08-25 10:41:05'),(4,5,'Abdul Aziz',NULL,'Operator Layanan Operasional','tendik',NULL,NULL,'2026-08-25 10:41:05','2026-08-25 10:41:05'),(5,5,'Siti Rahma, S.Pd',NULL,'Guru Kelas','guru',NULL,NULL,'2026-08-25 10:41:05','2026-08-25 10:41:05');
/*!40000 ALTER TABLE `pegawai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sekolah`
--

DROP TABLE IF EXISTS `sekolah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sekolah` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_sekolah` varchar(255) NOT NULL,
  `kode_sekolah` varchar(255) NOT NULL,
  `logo_sekolah` varchar(255) DEFAULT NULL,
  `logo_kabupaten` varchar(255) DEFAULT NULL,
  `kontak_wa` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `nama_pemerintah` varchar(255) NOT NULL,
  `nama_dinas` varchar(255) NOT NULL,
  `nama_korwil` varchar(255) DEFAULT NULL,
  `alamat` text NOT NULL,
  `tempat` varchar(255) NOT NULL,
  `kode_klasifikasi_surat` varchar(255) NOT NULL DEFAULT '000.2.3.1',
  `jabatan_resmi_sppb` varchar(255) NOT NULL DEFAULT 'Kuasa Pengguna Barang',
  `format_kode_npb` varchar(255) DEFAULT NULL,
  `format_kode_spb` varchar(255) DEFAULT NULL,
  `format_kode_sppb` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `nomor_urut_terakhir` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sekolah`
--

LOCK TABLES `sekolah` WRITE;
/*!40000 ALTER TABLE `sekolah` DISABLE KEYS */;
INSERT INTO `sekolah` VALUES (5,'SEKOLAH DASAR NEGERI 3 RANGKASBITUNG TIMUR','SDN3RKST',NULL,NULL,'081234567890','sdn3rangkasbitungtimur@example.com','PEMERINTAH KABUPATEN LEBAK','DINAS PENDIDIKAN','KORWIL SATUAN PENDIDIKAN','Kp. Catihan Desa Rangkasbitung Timur Kec. Rangkasbitung, Kab. Lebak, Banten 42313','Rangkasbitung','000.2.3.1','Kuasa Pengguna Barang','FORMAT II.I.6','FORMAT II.I.7','FORMAT II.I.8','2026-08-25 10:41:04','2026-08-25 10:41:38',44);
/*!40000 ALTER TABLE `sekolah` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('QP5fgFK5yp6cOSYIkB8UYKIA4WYqVkbIG6d5iHMC',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRlBvMGdIVVZ4bFhQZFNHbVp3bGNDMXhjM1NjVmQzbThEeFBaR0ZqVCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjQ6Imh0dHA6Ly9zaXN0ZW0tc3VyYXQudGVzdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1787793771),('tG9kpb0KpvpBzMF8NhLetMD7q6jeoUELb5U6JH4I',6,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiTHRQRHQ1TklRdExBTzJhcEY0WDJFMDQwVGQ4UDBneGJDZXFSeGJxSCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzQ6Imh0dHA6Ly9zaXN0ZW0tc3VyYXQudGVzdC9kYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo2O30=',1787679941);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sekolah_id` bigint unsigned NOT NULL,
  `paket_id` bigint unsigned NOT NULL,
  `status` enum('aktif','hold','expired') NOT NULL DEFAULT 'aktif',
  `tanggal_mulai` date NOT NULL,
  `tanggal_berakhir` date NOT NULL,
  `fersaku_payment_id` varchar(255) DEFAULT NULL,
  `dibuat_manual_oleh` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_sekolah_id_foreign` (`sekolah_id`),
  KEY `subscriptions_paket_id_foreign` (`paket_id`),
  KEY `subscriptions_dibuat_manual_oleh_foreign` (`dibuat_manual_oleh`),
  CONSTRAINT `subscriptions_dibuat_manual_oleh_foreign` FOREIGN KEY (`dibuat_manual_oleh`) REFERENCES `users` (`id`),
  CONSTRAINT `subscriptions_paket_id_foreign` FOREIGN KEY (`paket_id`) REFERENCES `paket_subscription` (`id`),
  CONSTRAINT `subscriptions_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaksi`
--

DROP TABLE IF EXISTS `transaksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sekolah_id` bigint unsigned NOT NULL,
  `nomor_referensi_asal` varchar(255) NOT NULL,
  `nomor_npb` varchar(255) DEFAULT NULL,
  `nomor_spb` varchar(255) DEFAULT NULL,
  `nomor_sppb` varchar(255) DEFAULT NULL,
  `tanggal_npb` date NOT NULL,
  `tanggal_spb` date DEFAULT NULL,
  `tanggal_sppb` date DEFAULT NULL,
  `pihak_peminta_id` bigint unsigned DEFAULT NULL,
  `status` enum('draft','siap_generate','selesai') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaksi_sekolah_id_foreign` (`sekolah_id`),
  KEY `transaksi_pihak_peminta_id_foreign` (`pihak_peminta_id`),
  CONSTRAINT `transaksi_pihak_peminta_id_foreign` FOREIGN KEY (`pihak_peminta_id`) REFERENCES `pegawai` (`id`),
  CONSTRAINT `transaksi_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksi`
--

LOCK TABLES `transaksi` WRITE;
/*!40000 ALTER TABLE `transaksi` DISABLE KEYS */;
/*!40000 ALTER TABLE `transaksi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaksi_item`
--

DROP TABLE IF EXISTS `transaksi_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksi_item` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaksi_id` bigint unsigned NOT NULL,
  `master_barang_id` bigint unsigned NOT NULL,
  `spesifikasi` varchar(255) NOT NULL,
  `jumlah` int NOT NULL,
  `satuan` varchar(255) NOT NULL,
  `keperluan` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaksi_item_transaksi_id_foreign` (`transaksi_id`),
  KEY `transaksi_item_master_barang_id_foreign` (`master_barang_id`),
  CONSTRAINT `transaksi_item_master_barang_id_foreign` FOREIGN KEY (`master_barang_id`) REFERENCES `master_barang` (`id`),
  CONSTRAINT `transaksi_item_transaksi_id_foreign` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksi_item`
--

LOCK TABLES `transaksi_item` WRITE;
/*!40000 ALTER TABLE `transaksi_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `transaksi_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `sekolah_id` bigint unsigned DEFAULT NULL,
  `role` enum('admin','sekolah') NOT NULL DEFAULT 'sekolah',
  `google_id` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_sekolah_id_foreign` (`sekolah_id`),
  CONSTRAINT `users_sekolah_id_foreign` FOREIGN KEY (`sekolah_id`) REFERENCES `sekolah` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (5,'Admin Delix Studio','admin@delixstudio.com',NULL,'admin',NULL,NULL,'$2y$12$JYuuYdw.HFQTHiQY.nDyGeAchf.lQE22YcE37YHt70XFTzQZ3cR56',NULL,'2026-08-25 10:41:05','2026-08-25 10:41:05'),(6,'Operator SDN 3 Rangkasbitung Timur','sekolah@example.com',5,'sekolah',NULL,NULL,'$2y$12$AoX3FE8trVsrVcLcRPVituJ27vIMFHlytlBbMRISBFDh/SZhtlIq.',NULL,'2026-08-25 10:41:05','2026-08-25 10:41:05');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-27  8:24:25
