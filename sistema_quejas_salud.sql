-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-05-2025 a las 05:32:14
-- Versión del servidor: 10.4.27-MariaDB
-- Versión de PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_quejas_salud`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciudades`
--

CREATE TABLE `ciudades` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `estado` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ciudades`
--

INSERT INTO `ciudades` (`id`, `nombre`, `departamento`, `estado`) VALUES
(1, 'Bogotá', 'Cundinamarca', 1),
(2, 'Medellín', 'Antioquia', 1),
(3, 'Cali', 'Valle del Cauca', 1),
(4, 'Barranquilla', 'Atlántico', 1),
(5, 'Cartagena', 'Bolívar', 1),
(6, 'Bucaramanga', 'Santander', 1),
(7, 'Pereira', 'Risaralda', 1),
(8, 'Santa Marta', 'Magdalena', 1),
(9, 'Manizales', 'Caldas', 1),
(10, 'Pasto', 'Nariño', 1),
(11, 'Tunja', 'Boyacá', 1),
(12, 'Tuta', 'Boyacá', 1),
(13, 'Moniquirá', 'Boyacá', 1),
(14, 'Barbosa', 'Boyacá', 1),
(15, 'Soatá', 'Boyacá', 1),
(16, 'Boavita', 'Boyacá', 1),
(17, 'San Mateo', 'Boyacá', 1),
(18, 'Guacamayas', 'Boyacá', 1),
(19, 'Panqueba', 'Boyacá', 1),
(20, 'El Cocuy', 'Boyacá', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eps`
--

CREATE TABLE `eps` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `estado` int(10) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eps`
--

INSERT INTO `eps` (`id`, `nombre`, `email`, `estado`) VALUES
(1, 'Nueva EPS', NULL, 1),
(2, 'Sura EPS', NULL, 1),
(3, 'Sanitas', NULL, 1),
(4, 'Compensar', NULL, 1),
(5, 'Salud Total', NULL, 1),
(6, 'Famisanar', NULL, 1),
(7, 'Coosalud', NULL, 1),
(8, 'Mutual SER', NULL, 1),
(9, 'Comfenalco Valle', NULL, 1),
(10, 'Emssanar', NULL, 1),
(11, 'Caprecom', NULL, 1),
(12, 'Comfamiliar Huila', 'comfamiliarhuila@gmail.com', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `quejas`
--

CREATE TABLE `quejas` (
  `id` int(11) NOT NULL,
  `nombre_paciente` varchar(100) NOT NULL,
  `documento_identidad` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `ciudad_id` int(11) NOT NULL,
  `eps_id` int(11) NOT NULL,
  `tipo_queja_id` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `respuesta` text DEFAULT NULL,
  `archivo_respuesta` varchar(255) DEFAULT NULL,
  `fecha_respuesta` date DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','En Proceso','Resuelto') DEFAULT 'Pendiente',
  `archivo_adjunto` varchar(255) DEFAULT NULL,
  `email_enviado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `quejas`
--

INSERT INTO `quejas` (`id`, `nombre_paciente`, `documento_identidad`, `email`, `telefono`, `ciudad_id`, `eps_id`, `tipo_queja_id`, `descripcion`, `respuesta`, `archivo_respuesta`, `fecha_respuesta`, `fecha_creacion`, `estado`, `archivo_adjunto`, `email_enviado`) VALUES
(4, 'Mariana Higuera', '1053256123', 'marianah@gmail.com', '3105359552', 20, 2, 2, 'Había fallo en el sistema y no me atendieron', 'se informo a rocky', NULL, '2025-05-15', '2025-04-22 04:18:58', 'En Proceso', NULL, 0),
(13, 'Jose Coronado', '4288526', 'josegabriel@hotmail.com', '3105359552', 16, 3, 7, 'Sistema de citas fuera de funcionamiento', 'se informo a rocky', NULL, '2025-05-15', '2025-04-25 20:36:53', 'En Proceso', 'uploads/680bf26557418.jpeg', 0),
(14, 'Cristian Coronado', '74374584', 'crisgacovi@hotmail.com', '3133832499', 20, 6, 9, 'Mucha documentación', 'Se esta implementando racionalización de trámites', 'uploads/respuestas/respuesta_queja_14_6819727c69cf8.pdf', '2025-05-05', '2025-04-26 03:50:33', 'En Proceso', 'uploads/680c58092d008.jpg', 1),
(15, 'Julio Mahecha', '4288355', 'mahecha@gmail.com', '3147892541', 18, 9, 3, 'Personal no sabe atender público', 'se informo a gerente', NULL, '2025-05-15', '2025-04-26 04:01:13', 'En Proceso', 'uploads/680c5a89767fc.pdf', 0),
(16, 'Fernando Espitia', '10491593578', 'fernanda@yahoo.es', '3214567896', 19, 2, 5, 'Medicamentos en caducidad', 'Se hizo revisión de inventario de farmacia', 'uploads/respuestas/respuesta_queja_16_68142cc4b4551.jpg', '2025-05-01', '2025-04-26 04:26:24', 'En Proceso', 'uploads/680c6070be38f.pdf', 0),
(18, 'Eduard escamilla', '42885620', 'eduard@hotmail.com', '3123890970', 19, 2, 1, 'La enfermera no me atendió', 'Se informo al jefe', 'uploads/respuestas/respuesta_queja_18_20250502_043742_68142ff61e469.pdf', '2025-05-01', '2025-04-29 00:26:48', 'En Proceso', 'uploads/68101cc881444.jpg', 0),
(19, 'Paula Baron', '1049606165', 'paucar20230@hotmail.com', '3213896756', 18, 1, 4, 'No había el medicamento recetado', 'Ya se verificó inventario actual', 'uploads/respuestas/respuesta_queja_19_6818275ba09dc.pdf', '2025-05-04', '2025-05-04 05:08:03', 'En Proceso', 'uploads/adjuntos6816f633c7982.pdf', 0),
(22, 'Graciela Viancha', '24201598', 'gracielav@gmail.com', '3142880713', 12, 5, 5, 'Doctor recetó mal', 'Se cambio a doctor', 'uploads/respuestas/respuesta_queja_22_681829824416b.png', '2025-05-04', '2025-05-04 05:30:04', 'En Proceso', 'uploads/adjuntos/6816fb5c0f5d8.pdf', 0),
(23, 'Jose Coronado', '7895725', 'josegabriel@hotmail.com', '3135268475', 13, 10, 7, 'No hay disponibilidad de citas', 'Contratación más médicos', 'uploads/respuestas/respuesta_queja_23_681962eaabc25.pdf', '2025-05-05', '2025-05-04 05:38:44', 'En Proceso', 'uploads/adjuntos/adjunto_queja__6816fd64204a4.jpeg', 0),
(24, 'Leonardo lopez', '1055355485', 'leonardol@yahoo.com', '3235698745', 20, 4, 8, 'Baños fuera de servicio', 'Ya se constató la habilitación del sitio', 'uploads/respuestas/respuesta_queja_24_681959d09bd56.jpeg', '2025-05-05', '2025-05-04 05:57:46', 'En Proceso', 'uploads/adjuntos/adjunto_queja_1055355485_681701dadf5c8.pdf', 0),
(25, 'Lucinio Figueredo', '45628159', 'luciniof@gmail.com', '3115502615', 16, 8, 2, 'No me atendieron injustificadamente', 'Se solicitaron razones de la no atención', 'uploads/respuestas/respuesta_queja_25_681976c778706.pdf', '2025-05-05', '2025-05-06 01:22:18', 'En Proceso', 'uploads/adjuntos/adjunto_queja_45628159_6819644a134a7.pdf', 0),
(26, 'Luis Coronado', '24202569', 'crisgacovi@gmail.com', '3112589647', 16, 2, 4, 'No se entregaron los medicamentos recetados', 'Se reporto a farmacia para que revise inventario', 'uploads/respuestas/respuesta_queja_26_681aed8698173.pdf', '2025-05-07', '2025-05-07 05:19:00', 'En Proceso', 'uploads/adjuntos/adjunto_queja_24202569_681aed43eefa2.pdf', 0),
(27, 'Alexander Rojas', '105578925', 'crisgacovi@yahoo.com', '3115987538', 19, 8, 5, 'Medicamentos me causaron otros problemas', 'Se notificó a médico ante jefe', 'uploads/respuestas/respuesta_queja_27_681aefe92e310.pdf', '2025-05-07', '2025-05-07 05:29:04', 'En Proceso', 'uploads/adjuntos/adjunto_queja_105578925_681aefa02f8b5.jpg', 1),
(28, 'Carolina Sierra', '123456789', 'crisgacovi@hotmail.com', '7351085', 11, 7, 3, 'Médicos no atienden amablemente al usuario', 'Se anotó en hoja de vida', 'uploads/respuestas/respuesta_queja_28_681c228a50e24.jpg', '2025-05-07', '2025-05-08 03:17:27', 'En Proceso', 'uploads/adjuntos/adjunto_queja_123456789_681c2247a69bb.pdf', 1),
(29, 'Elber Salas', '42586413', 'crisgacovi@gmail.com', '3115329552', 14, 4, 9, 'Mucho papeleo', 'Se están racionalizando trámites', 'uploads/respuestas/respuesta_queja_29_681c28c2743cd.jpeg', '2025-05-07', '2025-05-08 03:44:02', 'En Proceso', 'uploads/adjuntos/adjunto_queja_42586413_681c28823da03.pdf', 1),
(30, 'Eduard Escamilla', '4288599', 'eduescami@hotmail.com', '3103279906', 19, 3, 3, 'Los médicos dan el diagnóstico de mala manera', 'Se notificó a gerente para que tome la medidas correspondientes', 'uploads/respuestas/respuesta_queja_30_6822637d6a585.pdf', '2025-05-12', '2025-05-12 21:08:07', 'En Proceso', 'uploads/adjuntos/adjunto_queja_4288599_68226337199ee.jpg', 1),
(31, 'Astrid Parra', '23984011', 'auditariagaudi2025@gmail.com', '', 12, 5, 12, 'Solicitud generación cita medicina nutrición', 'Se asigno cita el dia tal', 'uploads/respuestas/respuesta_queja_31_682293a4d9d09.pdf', '2025-05-12', '2025-05-13 00:28:03', 'En Proceso', 'uploads/adjuntos/adjunto_queja_23984011_682292133e6b1.jpg', 1),
(32, 'Astrid Parra', '23984011', 'auditoriagaudi@gmail.com', '3168454345', 13, 6, 2, 'No me atendieron', 'Queja remitida a eps', 'uploads/respuestas/respuesta_queja_32_682295ef75187.png', '2025-05-12', '2025-05-13 00:42:16', 'En Proceso', 'uploads/adjuntos/adjunto_queja_23984011_6822956866801.pdf', 1),
(33, 'as', '23984011', 'auditoriagaudi2025@gmail.com', '3105359552', 20, 10, 5, 'Medicamentos incorrectos', 'Listo', 'uploads/respuestas/respuesta_queja_33_682296c24a846.png', '2025-05-12', '2025-05-13 00:47:26', 'En Proceso', 'uploads/adjuntos/adjunto_queja_23984011_6822969e64f0f.pdf', 1),
(34, 'Jose Coronado', '23984011', 'crisgacovi@hotmail.com', '3142880713', 3, 6, 9, 'exceso tramites', 'racionalización trámites', NULL, '2025-05-15', '2025-05-14 04:15:00', 'En Proceso', NULL, 1),
(35, 'Paula Baron', '24201598', 'crisgacovi@gmail.com', '3142880713', 12, 4, 3, 'mala atencion', 'se notifico a gerente', NULL, '2025-05-13', '2025-05-14 04:15:41', 'En Proceso', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_queja`
--

CREATE TABLE `tipos_queja` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` int(10) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_queja`
--

INSERT INTO `tipos_queja` (`id`, `nombre`, `descripcion`, `estado`) VALUES
(1, 'Demora en la atención', 'Tiempo excesivo para recibir atención médica', 1),
(2, 'Negación de servicio', 'Rechazo a prestar un servicio de salud solicitado', 1),
(3, 'Mala atención del personal', 'Trato inadecuado por parte del personal médico o administrativo', 1),
(4, 'Medicamentos no entregados', 'No entrega de medicamentos formulados', 1),
(5, 'Error en diagnóstico', 'Diagnóstico médico equivocado', 1),
(6, 'Cobros indebidos', 'Cobros no justificados o errores en la facturación', 1),
(7, 'Problemas con citas', 'No disponibilidad o cancelación de citas médicas', 1),
(8, 'Infraestructura deficiente', 'Problemas con instalaciones, equipos o condiciones sanitarias', 1),
(9, 'Trámites excesivos', 'Procedimientos administrativos complicados o innecesarios', 1),
(10, 'Otro', 'Otros motivos no listados anteriormente', 1),
(11, 'Personal no disponible', 'No se cuenta con médico especialista', 1),
(12, 'Autorización', 'Autorización citas', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','editor','consultor_ciudad') NOT NULL DEFAULT 'editor',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre_completo`, `email`, `role`, `estado`, `ultimo_login`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'admin', '$2y$10$ul0SaUFvkl3W8X/Ftn1yBe9ygpOGKE1ZEc/cdwDUfh8aVi6BvC0Uq', 'Administrador del Sistema', 'admin@sistema.com', 'admin', 1, '2025-05-19 21:50:36', '2025-04-23 21:10:28', '2025-05-20 02:50:36'),
(2, 'editor', '$2y$10$H2HBFAnfN2.56/08Ad6L3uA1cX.PlSfSbukYusfgLg4qRROybHc9y', 'Editor del Sistema', 'editor@sistema.com', 'editor', 1, '2025-05-12 19:53:47', '2025-04-23 21:10:28', '2025-05-13 00:53:47'),
(3, 'sisbentuta', '$2y$10$xxzXi351OatnUuBLeZYxdOtYP6u01y2zQbUP2V6N0kab5C1txEU9u', 'Consultor Tuta', 'sisben@tuta-boyaca.gov.co', 'consultor_ciudad', 1, '2025-05-15 23:23:23', '2025-05-14 03:54:58', '2025-05-16 04:23:23'),
(4, 'sisbenpanqueba', '$2y$10$0j4gVNLUudNnZ9buwkqc..JkQxgQCZuz.lwbdnRoaQ3g8HHYYbOdO', 'Consultor Panqueba', 'sisben@panqueba-boyaca.gov.co', 'consultor_ciudad', 1, '2025-05-15 20:26:41', '2025-05-16 01:26:23', '2025-05-16 01:26:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_ciudad`
--

CREATE TABLE `usuario_ciudad` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ciudad_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario_ciudad`
--

INSERT INTO `usuario_ciudad` (`id`, `usuario_id`, `ciudad_id`, `created_at`) VALUES
(3, 3, 12, '2025-05-14 03:55:52'),
(4, 4, 19, '2025-05-16 01:26:23');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `ciudades`
--
ALTER TABLE `ciudades`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `eps`
--
ALTER TABLE `eps`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `quejas`
--
ALTER TABLE `quejas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ciudad_id` (`ciudad_id`),
  ADD KEY `eps_id` (`eps_id`),
  ADD KEY `tipo_queja_id` (`tipo_queja_id`);

--
-- Indices de la tabla `tipos_queja`
--
ALTER TABLE `tipos_queja`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `usuario_ciudad`
--
ALTER TABLE `usuario_ciudad`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usuario_ciudad` (`usuario_id`,`ciudad_id`),
  ADD KEY `ciudad_id` (`ciudad_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ciudades`
--
ALTER TABLE `ciudades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `eps`
--
ALTER TABLE `eps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `quejas`
--
ALTER TABLE `quejas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `tipos_queja`
--
ALTER TABLE `tipos_queja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuario_ciudad`
--
ALTER TABLE `usuario_ciudad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `quejas`
--
ALTER TABLE `quejas`
  ADD CONSTRAINT `quejas_ibfk_1` FOREIGN KEY (`ciudad_id`) REFERENCES `ciudades` (`id`),
  ADD CONSTRAINT `quejas_ibfk_2` FOREIGN KEY (`eps_id`) REFERENCES `eps` (`id`),
  ADD CONSTRAINT `quejas_ibfk_3` FOREIGN KEY (`tipo_queja_id`) REFERENCES `tipos_queja` (`id`);

--
-- Filtros para la tabla `usuario_ciudad`
--
ALTER TABLE `usuario_ciudad`
  ADD CONSTRAINT `usuario_ciudad_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuario_ciudad_ibfk_2` FOREIGN KEY (`ciudad_id`) REFERENCES `ciudades` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
