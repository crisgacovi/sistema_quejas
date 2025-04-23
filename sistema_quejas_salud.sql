-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-04-2025 a las 21:12:32
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
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ciudades`
--

INSERT INTO `ciudades` (`id`, `nombre`) VALUES
(1, 'Bogotá'),
(2, 'Medellín'),
(3, 'Cali'),
(4, 'Barranquilla'),
(5, 'Cartagena'),
(6, 'Bucaramanga'),
(7, 'Pereira'),
(8, 'Santa Marta'),
(9, 'Manizales'),
(10, 'Pasto');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eps`
--

CREATE TABLE `eps` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eps`
--

INSERT INTO `eps` (`id`, `nombre`) VALUES
(1, 'Nueva EPS'),
(2, 'Sura EPS'),
(3, 'Sanitas'),
(4, 'Compensar'),
(5, 'Salud Total'),
(6, 'Famisanar'),
(7, 'Coosalud'),
(8, 'Mutual SER'),
(9, 'Comfenalco Valle'),
(10, 'Emssanar');

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
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('Pendiente','En revisión','Resuelta','Rechazada') DEFAULT 'Pendiente',
  `archivo_adjunto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `quejas`
--

INSERT INTO `quejas` (`id`, `nombre_paciente`, `documento_identidad`, `email`, `telefono`, `ciudad_id`, `eps_id`, `tipo_queja_id`, `descripcion`, `fecha_creacion`, `estado`, `archivo_adjunto`) VALUES
(1, 'Carlos Castro', '4325789', 'carlosc@gmail.com', '3202491572', 1, 1, 1, 'Espere dos horas y no me atendieron', '2025-04-21 23:29:36', 'Pendiente', NULL),
(2, 'Cristian Coronado', '74374584', 'crisgacovi@hotmail.com', '3133832499', 8, 8, 3, 'La enfermera solo hablaba por celular', '2025-04-22 04:04:44', 'Pendiente', NULL),
(3, 'Paula Baron', '1049606165', 'paucar20230@hotmail.com', '3213896756', 10, 6, 5, 'Me recetaron medicamentos que me afectaron', '2025-04-22 04:09:45', 'Pendiente', NULL),
(4, 'Mariana Higuera', '1053256123', 'marianah@gmail.com', '3105359552', 5, 2, 2, 'Había fallo en el sistema y no me atendieron', '2025-04-22 04:18:58', 'Pendiente', NULL),
(5, 'Maria Viancha', '24201398', 'mariaviancha@yahoo.com', '3142880713', 7, 10, 9, 'Me solicitaron muchos documentos', '2025-04-22 04:58:29', 'Pendiente', NULL),
(7, 'Natalia Mejia', '105533562', 'natalia@outlook.com', '3214212022', 2, 3, 6, 'Me exigieron dinero adicional', '2025-04-23 01:51:05', 'Pendiente', 'uploads/6808478978220.pdf');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguimientos`
--

CREATE TABLE `seguimientos` (
  `queja_id` int(10) NOT NULL,
  `estado` varchar(50) NOT NULL,
  `comentario` varchar(100) NOT NULL,
  `usuario_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_queja`
--

CREATE TABLE `tipos_queja` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_queja`
--

INSERT INTO `tipos_queja` (`id`, `nombre`, `descripcion`) VALUES
(1, 'Demora en la atención', 'Tiempo excesivo para recibir atención médica'),
(2, 'Negación de servicio', 'Rechazo a prestar un servicio de salud solicitado'),
(3, 'Mala atención del personal', 'Trato inadecuado por parte del personal médico o administrativo'),
(4, 'Medicamentos no entregados', 'No entrega de medicamentos formulados'),
(5, 'Error en diagnóstico', 'Diagnóstico médico equivocado'),
(6, 'Cobros indebidos', 'Cobros no justificados o errores en la facturación'),
(7, 'Problemas con citas', 'No disponibilidad o cancelación de citas médicas'),
(8, 'Infraestructura deficiente', 'Problemas con instalaciones, equipos o condiciones sanitarias'),
(9, 'Trámites excesivos', 'Procedimientos administrativos complicados o innecesarios'),
(10, 'Otro', 'Otros motivos no listados anteriormente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` text NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `email`, `role`) VALUES
(1, 'administrador', '$2y$10$kLcZ42pInHtwUz074FOIK.s.dEmPWCAjsHNC2r/o9V4ic.cr8Rvgi', 'Administrador', 'admin@example.com', 'admin'),
(2, 'auditor', '$2y$10$qLrlPdNBcm64Lrx3xwHU6.uQ0/GsjrAzoZbRBRWth80YruLHH1o.i', 'Eduard Escamilla', 'eduard@hotmail.com', 'auditor');

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
-- Indices de la tabla `seguimientos`
--
ALTER TABLE `seguimientos`
  ADD PRIMARY KEY (`queja_id`);

--
-- Indices de la tabla `tipos_queja`
--
ALTER TABLE `tipos_queja`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `ciudades`
--
ALTER TABLE `ciudades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `eps`
--
ALTER TABLE `eps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `quejas`
--
ALTER TABLE `quejas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `tipos_queja`
--
ALTER TABLE `tipos_queja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
