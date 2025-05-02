-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-05-2025 a las 05:00:01
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
  `estado` int(10) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eps`
--

INSERT INTO `eps` (`id`, `nombre`, `estado`) VALUES
(1, 'Nueva EPS', 1),
(2, 'Sura EPS', 1),
(3, 'Sanitas', 1),
(4, 'Compensar', 1),
(5, 'Salud Total', 1),
(6, 'Famisanar', 1),
(7, 'Coosalud', 1),
(8, 'Mutual SER', 1),
(9, 'Comfenalco Valle', 1),
(10, 'Emssanar', 1),
(11, 'Caprecom', 1);

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
  `estado` enum('Pendiente','En Proceso','Resuelto','Cerrado') DEFAULT 'Pendiente',
  `archivo_adjunto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `quejas`
--

INSERT INTO `quejas` (`id`, `nombre_paciente`, `documento_identidad`, `email`, `telefono`, `ciudad_id`, `eps_id`, `tipo_queja_id`, `descripcion`, `respuesta`, `archivo_respuesta`, `fecha_respuesta`, `fecha_creacion`, `estado`, `archivo_adjunto`) VALUES
(4, 'Mariana Higuera', '1053256123', 'marianah@gmail.com', '3105359552', 20, 2, 2, 'Había fallo en el sistema y no me atendieron', NULL, NULL, NULL, '2025-04-22 04:18:58', 'Pendiente', NULL),
(12, 'Fabian Valencia', '74568258', 'valencia@yahoo.es', '3147892541', 14, 3, 10, 'Error en asignación de cita', NULL, NULL, NULL, '2025-04-24 15:18:30', 'Pendiente', 'uploads/680a5646bec98.jpg'),
(13, 'Jose Coronado', '4288526', 'josegabriel@hotmail.com', '3105359552', 16, 3, 7, 'Sistema de citas fuera de funcionamiento', NULL, NULL, NULL, '2025-04-25 20:36:53', 'Pendiente', 'uploads/680bf26557418.jpeg'),
(14, 'Cristian Coronado', '74374584', 'crisgacovi@hotmail.com', '3133832499', 20, 6, 9, 'Mucha documentación', NULL, NULL, NULL, '2025-04-26 03:50:33', 'Pendiente', 'uploads/680c58092d008.jpg'),
(15, 'Julio Mahecha', '4288355', 'mahecha@gmail.com', '3147892541', 18, 9, 3, 'Personal no sabe atender público', '', NULL, NULL, '2025-04-26 04:01:13', 'Pendiente', 'uploads/680c5a89767fc.pdf'),
(16, 'Fernando Espitia', '10491593578', 'fernanda@yahoo.es', '3214567896', 19, 2, 5, 'Medicamentos en caducidad', 'Se hizo revisión de inventario de farmacia', 'uploads/respuestas/respuesta_queja_16_68142cc4b4551.jpg', '2025-05-01', '2025-04-26 04:26:24', 'Resuelto', 'uploads/680c6070be38f.pdf'),
(18, 'Eduard escamilla', '42885620', 'eduard@hotmail.com', '3123890970', 19, 2, 1, 'La enfermera no me atendió', 'Se informo al jefe', 'uploads/respuestas/respuesta_queja_18_20250502_043742_68142ff61e469.pdf', '2025-05-01', '2025-04-29 00:26:48', 'Resuelto', 'uploads/68101cc881444.jpg');

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
(11, 'Personal no disponible', 'No se cuenta con médico especialista', 1);

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
  `role` enum('admin','editor') NOT NULL DEFAULT 'editor',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre_completo`, `email`, `role`, `estado`, `ultimo_login`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'admin', '$2y$10$ul0SaUFvkl3W8X/Ftn1yBe9ygpOGKE1ZEc/cdwDUfh8aVi6BvC0Uq', 'Administrador del Sistema', 'admin@sistema.com', 'admin', 1, '2025-05-01 21:10:57', '2025-04-23 21:10:28', '2025-05-02 02:10:57'),
(2, 'editor', '$2y$10$H2HBFAnfN2.56/08Ad6L3uA1cX.PlSfSbukYusfgLg4qRROybHc9y', 'Editor del Sistema', 'editor@sistema.com', 'editor', 1, '2025-04-28 19:41:39', '2025-04-23 21:10:28', '2025-04-29 00:41:39');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `quejas`
--
ALTER TABLE `quejas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `tipos_queja`
--
ALTER TABLE `tipos_queja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
