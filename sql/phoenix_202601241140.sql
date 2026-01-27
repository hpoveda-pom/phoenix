/*
 Navicat Premium Dump SQL

 Source Server         : LOCAL
 Source Server Type    : MySQL
 Source Server Version : 100432 (10.4.32-MariaDB)
 Source Host           : localhost:3306
 Source Schema         : phoenix

 Target Server Type    : MySQL
 Target Server Version : 100432 (10.4.32-MariaDB)
 File Encoding         : 65001

 Date: 24/01/2026 11:40:24
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for access
-- ----------------------------
DROP TABLE IF EXISTS `access`;
CREATE TABLE `access`  (
  `AccessId` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `UsersId` int NULL DEFAULT NULL,
  `ReportsId` int NULL DEFAULT NULL,
  `Level` int NULL DEFAULT NULL,
  `LastModify` datetime NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`AccessId`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of access
-- ----------------------------

-- ----------------------------
-- Table structure for access_log
-- ----------------------------
DROP TABLE IF EXISTS `access_log`;
CREATE TABLE `access_log`  (
  `LogId` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ReportsId` int NULL DEFAULT NULL,
  `UsersId` int NOT NULL,
  `QueryDate` datetime NOT NULL,
  `ExecTime` decimal(10, 2) NULL DEFAULT NULL,
  `Type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Request` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Response` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Browser` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`LogId`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of access_log
-- ----------------------------

-- ----------------------------
-- Table structure for category
-- ----------------------------
DROP TABLE IF EXISTS `category`;
CREATE TABLE `category`  (
  `CategoryId` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ParentId` int NULL DEFAULT NULL,
  `Title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Order` int NULL DEFAULT NULL,
  `IdType` int NULL DEFAULT NULL,
  `Status` int NULL DEFAULT 1,
  PRIMARY KEY (`CategoryId`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of category
-- ----------------------------
INSERT INTO `category` VALUES (1, NULL, 'REPORTES', '', 1, 1, 1);
INSERT INTO `category` VALUES (2, 1, 'Prueba 1', '', 1, 1, 1);
INSERT INTO `category` VALUES (3, 1, 'POM Reportes', '', 2, 1, 1);
INSERT INTO `category` VALUES (4, NULL, 'DATA LAKE', '', 0, 1, 1);
INSERT INTO `category` VALUES (5, 4, 'POM_Reportes', '', 1, 1, 1);
INSERT INTO `category` VALUES (6, 1, 'CLICKHOUSE-DW', '', 0, 1, 1);

-- ----------------------------
-- Table structure for connections
-- ----------------------------
DROP TABLE IF EXISTS `connections`;
CREATE TABLE `connections`  (
  `ConnectionId` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `Title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Connector` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Hostname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Port` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Username` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Password` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `ServiceName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Schema` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Status` int NULL DEFAULT 1,
  PRIMARY KEY (`ConnectionId`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of connections
-- ----------------------------
INSERT INTO `connections` VALUES (1, 'phoenix', NULL, 'mysqli', '', '', '', '', '', 'phoenix', 1);
INSERT INTO `connections` VALUES (2, 'data werehouse', NULL, 'mysqli', '', '', '', '', '', 'phoenixdw', 1);
INSERT INTO `connections` VALUES (3, 'POM_Aplicaciones', '', 'mysqli', 'localhost', '3306', 'root', '', '', 'pom_aplicaciones', 1);
INSERT INTO `connections` VALUES (4, 'POM_Reportes', '', 'mysqli', 'localhost', '3306', 'root', '', '', 'pom_reportes', 1);
INSERT INTO `connections` VALUES (5, 'clickhouse-cloud', '', 'clickhouse', 'f4rf85ygzj.eastus2.azure.clickhouse.cloud', '8443', 'default', 'Tsm1e.3Wgbw5P', '', 'POM_Aplicaciones', 1);
INSERT INTO `connections` VALUES (6, 'clickhouse-mysql', '', 'mysqlissl', 'f4rf85ygzj.eastus2.azure.clickhouse.cloud', '3306', 'mysql4f4rf85ygzj', 'Solid256!', '', 'POM_Aplicaciones', 1);

-- ----------------------------
-- Table structure for conventions
-- ----------------------------
DROP TABLE IF EXISTS `conventions`;
CREATE TABLE `conventions`  (
  `IdConventions` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ReportsId` int NULL DEFAULT NULL,
  `FieldName` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `FieldAlias` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `DataType` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Comments` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `MaskingLevel` int NULL DEFAULT NULL,
  `Status` int NOT NULL DEFAULT 1,
  PRIMARY KEY (`IdConventions`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of conventions
-- ----------------------------

-- ----------------------------
-- Table structure for login_errors
-- ----------------------------
DROP TABLE IF EXISTS `login_errors`;
CREATE TABLE `login_errors`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `UsersId` int NOT NULL,
  `error_message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of login_errors
-- ----------------------------

-- ----------------------------
-- Table structure for masking
-- ----------------------------
DROP TABLE IF EXISTS `masking`;
CREATE TABLE `masking`  (
  `MaskingId` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ReportsId` int NOT NULL,
  `UsersId` int NOT NULL,
  `OwnerId` int NOT NULL COMMENT 'Reports Owner users',
  `Level` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1' COMMENT '1=Public, 2=Personal, 3=Sensitive, 4=Confidential according Ley N.° 8968 & PRODHAB',
  `DateCreated` datetime NOT NULL DEFAULT current_timestamp(),
  `DateModified` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `ExpirationDate` datetime NULL DEFAULT NULL,
  `Status` int NOT NULL DEFAULT 1,
  PRIMARY KEY (`MaskingId`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of masking
-- ----------------------------

-- ----------------------------
-- Table structure for pipelines
-- ----------------------------
DROP TABLE IF EXISTS `pipelines`;
CREATE TABLE `pipelines`  (
  `PipelinesId` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `Description` varbinary(255) NULL DEFAULT NULL,
  `ReportsId` int NOT NULL,
  `ConnSource` int NOT NULL,
  `SchemaSource` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `TableSource` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `SchemaCreate` int NOT NULL DEFAULT 0,
  `TableCreate` int NOT NULL DEFAULT 1,
  `TableTruncate` int NOT NULL DEFAULT 1,
  `RecordsAlert` int NULL DEFAULT NULL,
  `TimeStamp` int NOT NULL DEFAULT 0,
  `CreateDate` datetime NOT NULL DEFAULT current_timestamp(),
  `LastUpdate` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `LastExecution` datetime NULL DEFAULT NULL,
  `Progress` int NOT NULL DEFAULT 0,
  `ExecTime` int NULL DEFAULT NULL,
  `Records` int NULL DEFAULT NULL,
  `SyncStatus` int NULL DEFAULT NULL,
  `Status` int NOT NULL DEFAULT 1,
  PRIMARY KEY (`PipelinesId`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of pipelines
-- ----------------------------

-- ----------------------------
-- Table structure for query
-- ----------------------------
DROP TABLE IF EXISTS `query`;
CREATE TABLE `query`  (
  `QueryId` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `UsersId` int NULL DEFAULT NULL,
  `ReportsId` int NULL DEFAULT NULL,
  `Query` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `Version` int NULL DEFAULT NULL,
  `CreateDate` datetime NULL DEFAULT current_timestamp(),
  `LastUpdated` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `Status` int NULL DEFAULT 1,
  PRIMARY KEY (`QueryId`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of query
-- ----------------------------

-- ----------------------------
-- Table structure for reports
-- ----------------------------
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports`  (
  `ReportsId` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ParentId` int NULL DEFAULT NULL,
  `UsersId` int NULL DEFAULT NULL,
  `TypeId` int NOT NULL DEFAULT 1,
  `CategoryId` int NOT NULL,
  `Title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `QueryId` int NULL DEFAULT NULL,
  `Query` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `ConnectionId` int NULL DEFAULT NULL,
  `Order` int NULL DEFAULT NULL,
  `Version` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `LayoutGridClass` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `ConventionStatus` int NULL DEFAULT 1,
  `CreatedDate` datetime NULL DEFAULT current_timestamp(),
  `Periodic` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `PipelinesId` int NULL DEFAULT NULL,
  `UserUpdated` int NULL DEFAULT NULL,
  `LastUpdated` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `MaskingStatus` int NULL DEFAULT 1,
  `TotalAxisX` int NULL DEFAULT NULL,
  `TotalAxisY` int NULL DEFAULT NULL,
  `Status` int NOT NULL DEFAULT 1,
  PRIMARY KEY (`ReportsId`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 69 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of reports
-- ----------------------------
INSERT INTO `reports` VALUES (1, 0, 1, 1, 2, 'test', '', NULL, 'SELECT * FROM ventas', 2, 1, '', '', 1, '2026-01-21 10:30:53', '', NULL, 1, '2026-01-21 10:42:24', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (2, 0, 1, 1, 3, 'Estados Actual', '', NULL, 'SELECT * FROM ch_estados_actual', 4, 0, '', '', 1, '2026-01-21 11:09:03', '0', NULL, NULL, NULL, 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (3, 0, 1, 1, 5, 'analisis_pago', NULL, NULL, 'SELECT * FROM analisis_pago', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:38', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (4, 0, 1, 1, 5, 'analisis_pago_legal', NULL, NULL, 'SELECT * FROM analisis_pago_legal', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:38', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (5, 0, 1, 1, 5, 'avancelegalanualcartera', NULL, NULL, 'SELECT * FROM avancelegalanualcartera', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:38', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (6, 0, 1, 1, 5, 'bg_riesgopreescribirdav7', NULL, NULL, 'SELECT * FROM bg_riesgopreescribirdav7', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:38', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (7, 0, 1, 1, 5, 'bi_estrategia_operaciones', NULL, NULL, 'SELECT * FROM bi_estrategia_operaciones', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:38', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (8, 0, 1, 1, 5, 'bi_estrategias', NULL, NULL, 'SELECT * FROM bi_estrategias', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (9, 0, 1, 1, 5, 'buropriorizado_actual', NULL, NULL, 'SELECT * FROM buropriorizado_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (10, 0, 1, 1, 5, 'buropriorizado_historico', NULL, NULL, 'SELECT * FROM buropriorizado_historico', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (11, 0, 1, 1, 5, 'cdr', NULL, NULL, 'SELECT * FROM cdr', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (12, 0, 1, 1, 5, 'central', NULL, NULL, 'SELECT * FROM central', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (13, 0, 1, 1, 5, 'ch_desgloceadmin', NULL, NULL, 'SELECT * FROM ch_desgloceadmin', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (14, 0, 1, 1, 5, 'ch_desgloceadmin_actual', NULL, NULL, 'SELECT * FROM ch_desgloceadmin_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (15, 0, 1, 1, 5, 'ch_desglocecierre', NULL, NULL, 'SELECT * FROM ch_desglocecierre', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (16, 0, 1, 1, 5, 'ch_desglocecierre_actual', NULL, NULL, 'SELECT * FROM ch_desglocecierre_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (17, 0, 1, 1, 5, 'ch_escalones_proyecto', NULL, NULL, 'SELECT * FROM ch_escalones_proyecto', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (18, 0, 1, 1, 5, 'ch_escalones_proyecto_actual', NULL, NULL, 'SELECT * FROM ch_escalones_proyecto_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (19, 0, 1, 1, 5, 'ch_escalonesporcentuales_proyecto', NULL, NULL, 'SELECT * FROM ch_escalonesporcentuales_proyecto', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (20, 0, 1, 1, 5, 'ch_escalonesporcentuales_proyecto_actual', NULL, NULL, 'SELECT * FROM ch_escalonesporcentuales_proyecto_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (21, 0, 1, 1, 5, 'ch_estados', NULL, NULL, 'SELECT * FROM ch_estados', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (22, 0, 1, 1, 5, 'ch_estados_actual', NULL, NULL, 'SELECT * FROM ch_estados_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (23, 0, 1, 1, 5, 'ch_gestores', NULL, NULL, 'SELECT * FROM ch_gestores', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (24, 0, 1, 1, 5, 'ch_gestores_20250101', NULL, NULL, 'SELECT * FROM ch_gestores_20250101', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (25, 0, 1, 1, 5, 'ch_gestores_actual', NULL, NULL, 'SELECT * FROM ch_gestores_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (26, 0, 1, 1, 5, 'ch_girosconproblemas', NULL, NULL, 'SELECT * FROM ch_girosconproblemas', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (27, 0, 1, 1, 5, 'ch_girosconproblemas_actual', NULL, NULL, 'SELECT * FROM ch_girosconproblemas_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (28, 0, 1, 1, 5, 'ch_metas_proyecto', NULL, NULL, 'SELECT * FROM ch_metas_proyecto', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (29, 0, 1, 1, 5, 'ch_metas_proyecto_actual', NULL, NULL, 'SELECT * FROM ch_metas_proyecto_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (30, 0, 1, 1, 5, 'ch_pagosconproblemas', NULL, NULL, 'SELECT * FROM ch_pagosconproblemas', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (31, 0, 1, 1, 5, 'ch_pagosconproblemas_actual', NULL, NULL, 'SELECT * FROM ch_pagosconproblemas_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (32, 0, 1, 1, 5, 'ch_pagosnoconciliados', NULL, NULL, 'SELECT * FROM ch_pagosnoconciliados', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (33, 0, 1, 1, 5, 'ch_pagosnoconciliados_actual', NULL, NULL, 'SELECT * FROM ch_pagosnoconciliados_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (34, 0, 1, 1, 5, 'ch_ptoequilibrio_proyecto', NULL, NULL, 'SELECT * FROM ch_ptoequilibrio_proyecto', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (35, 0, 1, 1, 5, 'ch_ptoequilibrio_proyecto_actual', NULL, NULL, 'SELECT * FROM ch_ptoequilibrio_proyecto_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (36, 0, 1, 1, 5, 'ch_servimas', NULL, NULL, 'SELECT * FROM ch_servimas', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (37, 0, 1, 1, 5, 'ch_servimas_actual', NULL, NULL, 'SELECT * FROM ch_servimas_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (38, 0, 1, 1, 5, 'ch_supervisores', NULL, NULL, 'SELECT * FROM ch_supervisores', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (39, 0, 1, 1, 5, 'ch_supervisores_20250101', NULL, NULL, 'SELECT * FROM ch_supervisores_20250101', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (40, 0, 1, 1, 5, 'ch_supervisores_actual', NULL, NULL, 'SELECT * FROM ch_supervisores_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (41, 0, 1, 1, 5, 'ch_totalesbanco', NULL, NULL, 'SELECT * FROM ch_totalesbanco', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (42, 0, 1, 1, 5, 'ch_totalesbanco_actual', NULL, NULL, 'SELECT * FROM ch_totalesbanco_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (43, 0, 1, 1, 5, 'ch_totalescierre', NULL, NULL, 'SELECT * FROM ch_totalescierre', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (44, 0, 1, 1, 5, 'ch_totalescierre_actual', NULL, NULL, 'SELECT * FROM ch_totalescierre_actual', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (45, 0, 1, 1, 5, 'cierre_estadocuentames', NULL, NULL, 'SELECT * FROM cierre_estadocuentames', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (46, 0, 1, 1, 5, 'control_pagos', NULL, NULL, 'SELECT * FROM control_pagos', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (47, 0, 1, 1, 5, 'control_retenciones', NULL, NULL, 'SELECT * FROM control_retenciones', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (48, 0, 1, 1, 5, 'coopeande_rptdiario', NULL, NULL, 'SELECT * FROM coopeande_rptdiario', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (49, 0, 1, 1, 5, 'dav2abril', NULL, NULL, 'SELECT * FROM dav2abril', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (50, 0, 1, 1, 5, 'est_cartera_aguinaldos20241004', NULL, NULL, 'SELECT * FROM est_cartera_aguinaldos20241004', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (51, 0, 1, 1, 5, 'est_cartera_aguinaldos20241021', NULL, NULL, 'SELECT * FROM est_cartera_aguinaldos20241021', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (52, 0, 1, 1, 5, 'fact_cargaestadoscuenta', NULL, NULL, 'SELECT * FROM fact_cargaestadoscuenta', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (53, 0, 1, 1, 5, 'filemaster_dav_historico', NULL, NULL, 'SELECT * FROM filemaster_dav_historico', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (54, 0, 1, 1, 5, 'ge_entregas', NULL, NULL, 'SELECT * FROM ge_entregas', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (55, 0, 1, 1, 5, 'ge_entregas_contact', NULL, NULL, 'SELECT * FROM ge_entregas_contact', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (56, 0, 1, 1, 5, 'indicadores_gestoresdiario', NULL, NULL, 'SELECT * FROM indicadores_gestoresdiario', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (57, 0, 1, 1, 5, 'lic_censa_cartera_20240823', NULL, NULL, 'SELECT * FROM lic_censa_cartera_20240823', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (58, 0, 1, 1, 5, 'lic_coopecaja_cartera_20250619', NULL, NULL, 'SELECT * FROM lic_coopecaja_cartera_20250619', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (59, 0, 1, 1, 5, 'lic_coopecaja_cartera_20250708', NULL, NULL, 'SELECT * FROM lic_coopecaja_cartera_20250708', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (60, 0, 1, 1, 5, 'lic_dav_cartera_20240425', NULL, NULL, 'SELECT * FROM lic_dav_cartera_20240425', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (61, 0, 1, 1, 5, 'lic_dav_cartera_20240425_detalle', NULL, NULL, 'SELECT * FROM lic_dav_cartera_20240425_detalle', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (62, 0, 1, 1, 5, 'lic_dav_cartera_20240425_proyeccion', NULL, NULL, 'SELECT * FROM lic_dav_cartera_20240425_proyeccion', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (63, 0, 1, 1, 5, 'lic_davivienda_cartera_20250704', NULL, NULL, 'SELECT * FROM lic_davivienda_cartera_20250704', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (64, 0, 1, 1, 5, 'lic_hawlet_cartera_20240918', NULL, NULL, 'SELECT * FROM lic_hawlet_cartera_20240918', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (65, 0, 1, 1, 5, 'resumen_central', NULL, NULL, 'SELECT * FROM resumen_central', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (66, 0, 1, 1, 5, 'rev_unicomer_recuperacion', NULL, NULL, 'SELECT * FROM rev_unicomer_recuperacion', 4, NULL, NULL, NULL, 1, '2026-01-21 17:52:56', NULL, NULL, NULL, '2026-01-21 17:54:39', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (67, 0, 1, 1, 6, 'PC_Metas', '', NULL, 'SELECT * FROM PC_Metas', 5, 0, '', '', 1, '2026-01-23 09:38:30', '0', NULL, 1, '2026-01-23 09:39:49', 1, NULL, NULL, 1);
INSERT INTO `reports` VALUES (68, 0, 1, 1, 6, 'PC_Metas V2', '', NULL, 'SELECT * FROM PC_Metas', 6, 0, '', '', 1, '2026-01-23 10:07:46', '0', NULL, NULL, NULL, 1, NULL, NULL, 1);

-- ----------------------------
-- Table structure for tasks
-- ----------------------------
DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks`  (
  `TasksId` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `UsersId` int NULL DEFAULT NULL,
  `ReportsId` int NULL DEFAULT NULL,
  `Mon` time NULL DEFAULT NULL,
  `Tue` time NULL DEFAULT NULL,
  `Wed` time NULL DEFAULT NULL,
  `Thu` time NULL DEFAULT NULL,
  `Fri` time NULL DEFAULT NULL,
  `Sat` time NULL DEFAULT NULL,
  `Sun` time NULL DEFAULT NULL,
  `LastSend` datetime NULL DEFAULT NULL,
  `Status` int NULL DEFAULT 1,
  PRIMARY KEY (`TasksId`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of tasks
-- ----------------------------
INSERT INTO `tasks` VALUES (1, 1, 263, '15:31:27', NULL, '09:00:00', '17:23:00', '09:30:00', NULL, NULL, '2025-03-14 09:30:01', 1);

-- ----------------------------
-- Table structure for tools
-- ----------------------------
DROP TABLE IF EXISTS `tools`;
CREATE TABLE `tools`  (
  `IdTools` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `CategoryId` int NULL DEFAULT NULL,
  `Title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `URL` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Order` int NULL DEFAULT NULL,
  `Status` int NULL DEFAULT 1,
  PRIMARY KEY (`IdTools`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of tools
-- ----------------------------
INSERT INTO `tools` VALUES (1, 22, 'Administrar Usuarios', NULL, 'sise_users.php', 1, 1);

-- ----------------------------
-- Table structure for types
-- ----------------------------
DROP TABLE IF EXISTS `types`;
CREATE TABLE `types`  (
  `TypesId` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `Title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `Status` int NULL DEFAULT 1,
  PRIMARY KEY (`TypesId`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of types
-- ----------------------------
INSERT INTO `types` VALUES (1, 'REPORTES', NULL, 1);
INSERT INTO `types` VALUES (2, 'DASHBOARD', NULL, 1);
INSERT INTO `types` VALUES (3, 'TOOLS', NULL, 1);

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `UsersId` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `UsersType` int NOT NULL,
  `Username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `FullName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `CreateDate` datetime NULL DEFAULT current_timestamp(),
  `LasModify` datetime NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
  `LastPasswordChanged` datetime NULL DEFAULT NULL,
  `CreatedBy` int NULL DEFAULT NULL,
  `ModifiedBy` int NULL DEFAULT NULL,
  `LastLogin` datetime NULL DEFAULT NULL,
  `Status` int NOT NULL DEFAULT 1,
  PRIMARY KEY (`UsersId`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 1, 'hpoveda', '$2y$10$oHrN8EipbP4b3ttKbHikZ.llgn14H1inbRNhcBfLjJa9vsNWQnZgq', 'Herbert Poveda', 'hpoveda@dominio.com', '2024-07-30 13:53:10', '2026-01-21 10:18:43', '2026-01-21 10:18:43', 1, NULL, NULL, 1);

SET FOREIGN_KEY_CHECKS = 1;
