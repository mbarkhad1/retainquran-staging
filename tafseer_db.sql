/*
 Navicat Premium Data Transfer

 Source Server         : localhost_3306
 Source Server Type    : MySQL
 Source Server Version : 100428 (10.4.28-MariaDB)
 Source Host           : localhost:3306
 Source Schema         : retain_quran

 Target Server Type    : MySQL
 Target Server Version : 100428 (10.4.28-MariaDB)
 File Encoding         : 65001

 Date: 29/07/2025 15:20:24
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for tafseers
-- ----------------------------
DROP TABLE IF EXISTS `tafseers`;
CREATE TABLE `tafseers`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `name_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `language` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `language_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tafseers
-- ----------------------------
INSERT INTO `tafseers` VALUES (1, 'Tafsir Ibn Kathir', 'تفسير ابن كثير', 'English', 'إنجليزي', 'en-tafisr-ibn-kathir', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:16');
INSERT INTO `tafseers` VALUES (2, 'Tafsir Ibn Kathir', 'تفسير ابن كثير', 'Arabic', 'عربي', 'ar-tafsir-ibn-kathir', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (3, 'Tafsir Ibn Kathir', 'تفسير ابن كثير', 'Urdu', 'الأردية', 'tafseer-ibn-e-kaseer-urdu', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (4, 'Tafsir Ibn Kathir', 'تفسير ابن كثير', 'Bengali', 'بنجالية', 'bn-tafseer-ibn-e-kaseer', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (5, 'Tafsir Jalalayn', 'تفسير الجلالين', 'Indonesian', 'الإندونيسية', 'in-tafsir-jalalayn', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (6, 'Rebar Kurdish Tafsir', 'تفسير الريبار الكردي', 'Kurdish', 'كردي', 'kurd-tafsir-rebar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (7, 'Tafseer Al Saddi', 'تفسير السعدي', 'Russian', 'روسي', 'ru-tafseer-al-saddi', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (8, 'Bosnian Abridged Explanation of the Quran', 'تفسير مختصر للقرآن باللغة البوسنية', 'Bosnian', 'البوسنية', 'bosnian-mokhtasar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (9, 'Italian Al-Mukhtasar in interpreting the Noble Quran', 'الإيطالي المختصر في تفسير القرآن الكريم', 'Italian', 'إيطالي', 'italian-mokhtasar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (10, 'Malayalam Abridged Explanation of the Quran', 'تفسير مختصر للقرآن باللغة المالايالامية', 'Malayalam', 'مالايالام', 'malayalam-mokhtasar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (11, 'Turkish Al-Mukhtasar in Interpreting the Noble Quran', 'المختصر التركي في تفسير القرآن الكريم', 'Turkish', 'تركي', 'turkish-mokhtasar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (12, 'French Abridged Explanation of the Quran', 'تفسير القرآن المختصر باللغة الفرنسية', 'French', 'فرنسية', 'french-mokhtasar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (13, 'Chinese Abridged Explanation of the Quran', 'شرح مختصر للقرآن باللغة الصينية', 'Chinese', 'صيني', 'chinese-mokhtasar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (14, 'Japanese Abridged Explanation of the Quran', 'شرح مختصر للقرآن باللغة اليابانية', 'Japanese', 'ياباني', 'japanese-mokhtasar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (15, 'Spanish Abridged Explanation of the Quran', 'شرح مختصر للقرآن باللغة الإسبانية', 'Spanish', 'إسباني', 'spanish-mokhtasar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (16, 'Persian Al-Mukhtasar in interpreting the Noble Quran', 'المختصر الفارسي في تفسير القرآن الكريم', 'Persian', 'فارسي', 'persian-mokhtasar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (17, 'Vietnamese Al-Mukhtasar in interpreting the Noble Quran', 'الفقه الفيتنامي المختصر في تفسير القرآن الكريم', 'Vietnamese', 'فيتنامي', 'vietnamese-mokhtasar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');
INSERT INTO `tafseers` VALUES (18, 'Filipino (Tagalog) Al-Mukhtasar in interpreting the Noble Quran', 'الفلبينية (التاغالوغ) المختصر في تفسير القرآن الكريم', 'Tagalog ( Phillipines)', 'التاغالوغ (الفلبين)', 'tagalog-mokhtasar', 'https://storage.googleapis.com/retain-quran/tafseers', '2025-07-29 12:04:57', '2025-07-29 12:09:21');

SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE tbl_user_settings 
ADD COLUMN `tafseer_id` int NULL AFTER `mushaf_id`;