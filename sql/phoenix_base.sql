-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 15-06-2025 a las 00:50:21
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `phoenix`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `access`
--

CREATE TABLE `access` (
  `AccessId` int(10) UNSIGNED NOT NULL,
  `UsersId` int(11) DEFAULT NULL,
  `ReportsId` int(11) DEFAULT NULL,
  `Level` int(11) DEFAULT NULL,
  `LastModify` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `access_log`
--

CREATE TABLE `access_log` (
  `LogId` int(10) UNSIGNED NOT NULL,
  `ReportsId` int(11) DEFAULT NULL,
  `UsersId` int(11) NOT NULL,
  `QueryDate` datetime NOT NULL,
  `ExecTime` decimal(10,2) DEFAULT NULL,
  `Type` varchar(50) DEFAULT NULL,
  `Request` varchar(255) NOT NULL,
  `Response` varchar(50) DEFAULT NULL,
  `Browser` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `category`
--

CREATE TABLE `category` (
  `CategoryId` int(10) UNSIGNED NOT NULL,
  `ParentId` int(11) DEFAULT NULL,
  `Title` varchar(100) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `Order` int(11) DEFAULT NULL,
  `IdType` int(11) DEFAULT NULL,
  `Status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `connections`
--

CREATE TABLE `connections` (
  `ConnectionId` int(10) UNSIGNED NOT NULL,
  `Title` varchar(100) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `Connector` varchar(50) DEFAULT NULL,
  `Hostname` varchar(255) DEFAULT NULL,
  `Port` varchar(25) DEFAULT NULL,
  `Username` varchar(25) DEFAULT NULL,
  `Password` varchar(25) DEFAULT NULL,
  `ServiceName` varchar(100) DEFAULT NULL,
  `Schema` varchar(100) DEFAULT NULL,
  `Status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `connections`
--

INSERT INTO `connections` (`ConnectionId`, `Title`, `Description`, `Connector`, `Hostname`, `Port`, `Username`, `Password`, `ServiceName`, `Schema`, `Status`) VALUES
(1, 'phoenix', NULL, 'mysqli', '', '', '', '', '', 'phoenix', 1),
(2, 'data werehouse', NULL, 'mysqli', '', '', '', '', '', 'datawerehouse', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conventions`
--

CREATE TABLE `conventions` (
  `IdConventions` int(10) UNSIGNED NOT NULL,
  `ReportsId` int(11) DEFAULT NULL,
  `FieldName` varchar(25) NOT NULL,
  `FieldAlias` varchar(150) DEFAULT NULL,
  `DataType` varchar(25) DEFAULT NULL,
  `Comments` varchar(255) DEFAULT NULL,
  `MaskingLevel` int(11) DEFAULT NULL,
  `Status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_errors`
--

CREATE TABLE `login_errors` (
  `id` int(11) NOT NULL,
  `UsersId` int(11) NOT NULL,
  `error_message` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `masking`
--

CREATE TABLE `masking` (
  `MaskingId` int(10) UNSIGNED NOT NULL,
  `ReportsId` int(11) NOT NULL,
  `UsersId` int(11) NOT NULL,
  `OwnerId` int(11) NOT NULL COMMENT 'Reports Owner users',
  `Level` varchar(5) NOT NULL DEFAULT '1' COMMENT '1=Public, 2=Personal, 3=Sensitive, 4=Confidential according Ley N.° 8968 & PRODHAB',
  `DateCreated` datetime NOT NULL DEFAULT current_timestamp(),
  `DateModified` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `ExpirationDate` datetime DEFAULT NULL,
  `Status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pipelines`
--

CREATE TABLE `pipelines` (
  `PipelinesId` int(10) UNSIGNED NOT NULL,
  `Description` varbinary(255) DEFAULT NULL,
  `ReportsId` int(11) NOT NULL,
  `ConnSource` int(11) NOT NULL,
  `SchemaSource` varchar(255) DEFAULT NULL,
  `TableSource` varchar(255) DEFAULT NULL,
  `SchemaCreate` int(11) NOT NULL DEFAULT 0,
  `TableCreate` int(11) NOT NULL DEFAULT 1,
  `TableTruncate` int(11) NOT NULL DEFAULT 1,
  `RecordsAlert` int(11) DEFAULT NULL,
  `TimeStamp` int(11) NOT NULL DEFAULT 0,
  `CreateDate` datetime NOT NULL DEFAULT current_timestamp(),
  `LastUpdate` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `LastExecution` datetime DEFAULT NULL,
  `Progress` int(11) NOT NULL DEFAULT 0,
  `ExecTime` int(11) DEFAULT NULL,
  `Records` int(11) DEFAULT NULL,
  `SyncStatus` int(11) DEFAULT NULL,
  `Status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `query`
--

CREATE TABLE `query` (
  `QueryId` int(10) UNSIGNED NOT NULL,
  `UsersId` int(11) DEFAULT NULL,
  `ReportsId` int(11) DEFAULT NULL,
  `Query` text DEFAULT NULL,
  `Version` int(11) DEFAULT NULL,
  `CreateDate` datetime DEFAULT current_timestamp(),
  `LastUpdated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `Status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reports`
--

CREATE TABLE `reports` (
  `ReportsId` int(10) UNSIGNED NOT NULL,
  `ParentId` int(11) DEFAULT NULL,
  `UsersId` int(11) DEFAULT NULL,
  `TypeId` int(11) NOT NULL DEFAULT 1,
  `CategoryId` int(11) NOT NULL,
  `Title` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `QueryId` int(11) DEFAULT NULL,
  `Query` text DEFAULT NULL,
  `ConnectionId` int(11) DEFAULT NULL,
  `Order` int(11) DEFAULT NULL,
  `Version` varchar(5) DEFAULT NULL,
  `LayoutGridClass` varchar(100) DEFAULT NULL,
  `ConventionStatus` int(11) DEFAULT 1,
  `CreatedDate` datetime DEFAULT current_timestamp(),
  `Periodic` varchar(150) DEFAULT NULL,
  `PipelinesId` int(11) DEFAULT NULL,
  `UserUpdated` int(11) DEFAULT NULL,
  `LastUpdated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `MaskingStatus` int(11) DEFAULT 1,
  `TotalAxisX` int(11) DEFAULT NULL,
  `TotalAxisY` int(11) DEFAULT NULL,
  `Status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasks`
--

CREATE TABLE `tasks` (
  `TasksId` int(10) UNSIGNED NOT NULL,
  `UsersId` int(11) DEFAULT NULL,
  `ReportsId` int(11) DEFAULT NULL,
  `Mon` time DEFAULT NULL,
  `Tue` time DEFAULT NULL,
  `Wed` time DEFAULT NULL,
  `Thu` time DEFAULT NULL,
  `Fri` time DEFAULT NULL,
  `Sat` time DEFAULT NULL,
  `Sun` time DEFAULT NULL,
  `LastSend` datetime DEFAULT NULL,
  `Status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `tasks`
--

INSERT INTO `tasks` (`TasksId`, `UsersId`, `ReportsId`, `Mon`, `Tue`, `Wed`, `Thu`, `Fri`, `Sat`, `Sun`, `LastSend`, `Status`) VALUES
(1, 1, 263, '15:31:27', NULL, '09:00:00', '17:23:00', '09:30:00', NULL, NULL, '2025-03-14 09:30:01', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tools`
--

CREATE TABLE `tools` (
  `IdTools` int(10) UNSIGNED NOT NULL,
  `CategoryId` int(11) DEFAULT NULL,
  `Title` varchar(100) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `Order` int(11) DEFAULT NULL,
  `Status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `tools`
--

INSERT INTO `tools` (`IdTools`, `CategoryId`, `Title`, `Description`, `URL`, `Order`, `Status`) VALUES
(1, 22, 'Administrar Usuarios', NULL, 'sise_users.php', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `types`
--

CREATE TABLE `types` (
  `TypesId` int(10) UNSIGNED NOT NULL,
  `Title` varchar(100) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `Status` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `types`
--

INSERT INTO `types` (`TypesId`, `Title`, `Description`, `Status`) VALUES
(1, 'REPORTES', NULL, 1),
(2, 'DASHBOARD', NULL, 1),
(3, 'TOOLS', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `UsersId` int(10) UNSIGNED NOT NULL,
  `UsersType` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `FullName` varchar(255) NOT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `CreateDate` datetime DEFAULT current_timestamp(),
  `LasModify` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `LastPasswordChanged` datetime DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `ModifiedBy` int(11) DEFAULT NULL,
  `LastLogin` datetime DEFAULT NULL,
  `Status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`UsersId`, `UsersType`, `Username`, `Password`, `FullName`, `Email`, `CreateDate`, `LasModify`, `LastPasswordChanged`, `CreatedBy`, `ModifiedBy`, `LastLogin`, `Status`) VALUES
(1, 1, 'hpoveda', '$2y$10$fIjSiVu3fbGszoW.7rasu.Th8N9fnC4CvJl7xZq5qea37BaXC1YIu', 'Herbert Poveda', 'hpoveda@dominio.com', '2024-07-30 13:53:10', '2025-02-27 11:32:06', '2024-12-10 15:04:36', 1, NULL, NULL, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `access`
--
ALTER TABLE `access`
  ADD PRIMARY KEY (`AccessId`) USING BTREE;

--
-- Indices de la tabla `access_log`
--
ALTER TABLE `access_log`
  ADD PRIMARY KEY (`LogId`) USING BTREE;

--
-- Indices de la tabla `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`CategoryId`) USING BTREE;

--
-- Indices de la tabla `connections`
--
ALTER TABLE `connections`
  ADD PRIMARY KEY (`ConnectionId`) USING BTREE;

--
-- Indices de la tabla `conventions`
--
ALTER TABLE `conventions`
  ADD PRIMARY KEY (`IdConventions`) USING BTREE;

--
-- Indices de la tabla `login_errors`
--
ALTER TABLE `login_errors`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indices de la tabla `masking`
--
ALTER TABLE `masking`
  ADD PRIMARY KEY (`MaskingId`) USING BTREE;

--
-- Indices de la tabla `pipelines`
--
ALTER TABLE `pipelines`
  ADD PRIMARY KEY (`PipelinesId`) USING BTREE;

--
-- Indices de la tabla `query`
--
ALTER TABLE `query`
  ADD PRIMARY KEY (`QueryId`) USING BTREE;

--
-- Indices de la tabla `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`ReportsId`) USING BTREE;

--
-- Indices de la tabla `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`TasksId`) USING BTREE;

--
-- Indices de la tabla `tools`
--
ALTER TABLE `tools`
  ADD PRIMARY KEY (`IdTools`) USING BTREE;

--
-- Indices de la tabla `types`
--
ALTER TABLE `types`
  ADD PRIMARY KEY (`TypesId`) USING BTREE;

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UsersId`) USING BTREE;

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `access`
--
ALTER TABLE `access`
  MODIFY `AccessId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `access_log`
--
ALTER TABLE `access_log`
  MODIFY `LogId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `category`
--
ALTER TABLE `category`
  MODIFY `CategoryId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `connections`
--
ALTER TABLE `connections`
  MODIFY `ConnectionId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `conventions`
--
ALTER TABLE `conventions`
  MODIFY `IdConventions` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `login_errors`
--
ALTER TABLE `login_errors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `masking`
--
ALTER TABLE `masking`
  MODIFY `MaskingId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pipelines`
--
ALTER TABLE `pipelines`
  MODIFY `PipelinesId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `query`
--
ALTER TABLE `query`
  MODIFY `QueryId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reports`
--
ALTER TABLE `reports`
  MODIFY `ReportsId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tasks`
--
ALTER TABLE `tasks`
  MODIFY `TasksId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tools`
--
ALTER TABLE `tools`
  MODIFY `IdTools` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `types`
--
ALTER TABLE `types`
  MODIFY `TypesId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `UsersId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
