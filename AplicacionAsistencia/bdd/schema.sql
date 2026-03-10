-- =============================================
-- Base de Datos: Aplicación de Asistencia
-- Cultura Tretze
-- =============================================

CREATE DATABASE IF NOT EXISTS assistencia_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_spanish_ci;

USE assistencia_db;

-- =============================================
-- Tabla: users (Administradores, Profesores y Padres)
-- =============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'teacher', 'parent') NOT NULL DEFAULT 'parent',
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- Tabla: groups (Grupos de alumnos)
-- Cada grupo tiene un profesor asignado (1:1)
-- =============================================
CREATE TABLE IF NOT EXISTS `groups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `teacher_id` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- Tabla: students (Alumnos/Niños)
-- =============================================
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `surname` VARCHAR(100) NOT NULL,
    `birthdate` DATE DEFAULT NULL,
    `parent_id` INT DEFAULT NULL,
    `group_id` INT DEFAULT NULL,
    `photo` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- Tabla: attendance (Registros de asistencia)
-- =============================================
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `status` ENUM('present', 'absent', 'late', 'justified') NOT NULL DEFAULT 'present',
    `notes` VARCHAR(500) DEFAULT NULL,
    `recorded_by` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_attendance` (`student_id`, `date`),
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- Tabla: daily_notes (Notas diarias del admin)
-- =============================================
CREATE TABLE IF NOT EXISTS `daily_notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `content` TEXT NOT NULL,
    `author_id` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- Tabla: notifications (Notificaciones a padres)
-- =============================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =============================================
-- DATOS INICIALES
-- Todas las contraseñas son: password (hash bcrypt)
-- =============================================

-- =============================================
-- 1. Usuario Admin
-- =============================================
INSERT INTO `users` (`username`, `password`, `role`, `name`, `email`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrador', 'admin@culturatretze.org');

-- =============================================
-- 2. Profesores (9 profesores) - IDs 2 a 10
-- =============================================
INSERT INTO `users` (`username`, `password`, `role`, `name`, `email`, `phone`) VALUES
('profe.ana',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Ana Martínez López',       'ana.martinez@culturatretze.org',    '611000001'),
('profe.carlos',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Carlos Ruiz Fernández',    'carlos.ruiz@culturatretze.org',     '611000002'),
('profe.laura',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Laura Sánchez García',     'laura.sanchez@culturatretze.org',   '611000003'),
('profe.miguel',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Miguel Torres Díaz',       'miguel.torres@culturatretze.org',   '611000004'),
('profe.elena',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Elena Romero Navarro',     'elena.romero@culturatretze.org',    '611000005'),
('profe.david',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'David Herrero Moreno',     'david.herrero@culturatretze.org',   '611000006'),
('profe.sofia',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Sofía Jiménez Ruiz',       'sofia.jimenez@culturatretze.org',   '611000007'),
('profe.jorge',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Jorge Castillo Vega',      'jorge.castillo@culturatretze.org',  '611000008'),
('profe.marta',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Marta Delgado Ortiz',      'marta.delgado@culturatretze.org',   '611000009');

-- =============================================
-- 3. Grupos (9 grupos, cada uno con su profesor)
-- =============================================
INSERT INTO `groups` (`name`, `description`, `teacher_id`) VALUES
('Infantil A',  'Grupo de 3-4 años - Lunes y Miércoles',       2),
('Infantil B',  'Grupo de 3-4 años - Martes y Jueves',         3),
('Infantil C',  'Grupo de 5-6 años - Lunes y Miércoles',       4),
('Primaria A',  'Grupo de 6-7 años - Martes y Jueves',         5),
('Primaria B',  'Grupo de 7-8 años - Lunes y Miércoles',       6),
('Primaria C',  'Grupo de 8-9 años - Martes y Jueves',         7),
('Juvenil A',   'Grupo de 10-11 años - Viernes',               8),
('Juvenil B',   'Grupo de 12-13 años - Lunes y Miércoles',     9),
('Juvenil C',   'Grupo de 14-15 años - Martes y Jueves',       10);

-- =============================================
-- 4. Padres (45 padres) - IDs 11 a 55
-- =============================================
INSERT INTO `users` (`username`, `password`, `role`, `name`, `email`, `phone`) VALUES
-- Padres para Infantil A (Grupo 1)
('maria.garcia',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'María García López',       'maria.garcia@email.com',       '612000001'),
('pedro.lopez',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Pedro López Martín',       'pedro.lopez@email.com',        '612000002'),
('carmen.fernandez',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Carmen Fernández Ruiz',    'carmen.fernandez@email.com',   '612000003'),
('antonio.martinez',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Antonio Martínez Sánchez', 'antonio.martinez@email.com',   '612000004'),
('lucia.diaz',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Lucía Díaz Torres',        'lucia.diaz@email.com',         '612000005'),
-- Padres para Infantil B (Grupo 2)
('jose.hernandez',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'José Hernández Gómez',     'jose.hernandez@email.com',     '612000006'),
('rosa.moreno',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Rosa Moreno Álvarez',      'rosa.moreno@email.com',        '612000007'),
('francisco.alvarez',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Francisco Álvarez Muñoz',  'francisco.alvarez@email.com',  '612000008'),
('isabel.munoz',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Isabel Muñoz Romero',      'isabel.munoz@email.com',       '612000009'),
('rafael.romero',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Rafael Romero Alonso',     'rafael.romero@email.com',      '612000010'),
-- Padres para Infantil C (Grupo 3)
('pilar.alonso',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Pilar Alonso Navarro',     'pilar.alonso@email.com',       '612000011'),
('fernando.navarro',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Fernando Navarro Gil',     'fernando.navarro@email.com',   '612000012'),
('dolores.gil',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Dolores Gil Blanco',       'dolores.gil@email.com',        '612000013'),
('manuel.blanco',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Manuel Blanco Serrano',    'manuel.blanco@email.com',      '612000014'),
('teresa.serrano',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Teresa Serrano Castro',    'teresa.serrano@email.com',     '612000015'),
-- Padres para Primaria A (Grupo 4)
('alberto.castro',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Alberto Castro Ramos',     'alberto.castro@email.com',     '612000016'),
('elena.ramos',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Elena Ramos Suárez',       'elena.ramos@email.com',        '612000017'),
('andres.suarez',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Andrés Suárez Ortega',     'andres.suarez@email.com',      '612000018'),
('beatriz.ortega',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Beatriz Ortega Marín',     'beatriz.ortega@email.com',     '612000019'),
('daniel.marin',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Daniel Marín Iglesias',    'daniel.marin@email.com',       '612000020'),
-- Padres para Primaria B (Grupo 5)
('cristina.iglesias',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Cristina Iglesias Peña',   'cristina.iglesias@email.com',  '612000021'),
('pablo.pena',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Pablo Peña Cortés',        'pablo.pena@email.com',         '612000022'),
('raquel.cortes',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Raquel Cortés Montero',    'raquel.cortes@email.com',      '612000023'),
('luis.montero',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Luis Montero Vidal',       'luis.montero@email.com',       '612000024'),
('eva.vidal',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Eva Vidal Rojas',          'eva.vidal@email.com',          '612000025'),
-- Padres para Primaria C (Grupo 6)
('roberto.rojas',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Roberto Rojas Cano',       'roberto.rojas@email.com',      '612000026'),
('silvia.cano',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Silvia Cano Prieto',       'silvia.cano@email.com',        '612000027'),
('victor.prieto',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Víctor Prieto Mora',       'victor.prieto@email.com',      '612000028'),
('ana.mora',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Ana Mora Pascual',         'ana.mora@email.com',           '612000029'),
('sergio.pascual',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Sergio Pascual León',      'sergio.pascual@email.com',     '612000030'),
-- Padres para Juvenil A (Grupo 7)
('patricia.leon',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Patricia León Guerrero',   'patricia.leon@email.com',      '612000031'),
('marcos.guerrero',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Marcos Guerrero Santos',   'marcos.guerrero@email.com',    '612000032'),
('aurora.santos',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Aurora Santos Medina',     'aurora.santos@email.com',      '612000033'),
('diego.medina',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Diego Medina Fuentes',     'diego.medina@email.com',       '612000034'),
('nuria.fuentes',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Nuria Fuentes Reyes',      'nuria.fuentes@email.com',      '612000035'),
-- Padres para Juvenil B (Grupo 8)
('oscar.reyes',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Óscar Reyes Cabrera',      'oscar.reyes@email.com',        '612000036'),
('carolina.cabrera',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Carolina Cabrera Herrera', 'carolina.cabrera@email.com',   '612000037'),
('ivan.herrera',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Iván Herrera Peña',        'ivan.herrera@email.com',       '612000038'),
('alicia.campos',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Alicia Campos Vargas',     'alicia.campos@email.com',      '612000039'),
('javier.vargas',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Javier Vargas Molina',     'javier.vargas@email.com',      '612000040'),
-- Padres para Juvenil C (Grupo 9)
('sonia.molina',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Sonia Molina Cruz',        'sonia.molina@email.com',       '612000041'),
('ricardo.cruz',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Ricardo Cruz Ortiz',       'ricardo.cruz@email.com',       '612000042'),
('lorena.ortiz',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Lorena Ortiz Delgado',     'lorena.ortiz@email.com',       '612000043'),
('gabriel.delgado',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Gabriel Delgado Rubio',    'gabriel.delgado@email.com',    '612000044'),
('marta.rubio',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Marta Rubio Sanz',         'marta.rubio@email.com',        '612000045');

-- =============================================
-- 5. Alumnos (45 alumnos, 5 por grupo)
--    parent_id: 11-55, group_id: 1-9
-- =============================================
INSERT INTO `students` (`name`, `surname`, `birthdate`, `parent_id`, `group_id`) VALUES
-- Infantil A (Grupo 1) - Niños de 3-4 años - Padres 11-15
('Lucas',     'García López',       '2022-03-10', 11, 1),
('Valeria',   'López Martín',       '2022-07-22', 12, 1),
('Hugo',      'Fernández Ruiz',     '2021-11-05', 13, 1),
('Martina',   'Martínez Sánchez',   '2022-01-18', 14, 1),
('Leo',       'Díaz Torres',        '2021-09-30', 15, 1),
-- Infantil B (Grupo 2) - Niños de 3-4 años - Padres 16-20
('Olivia',    'Hernández Gómez',    '2022-05-14', 16, 2),
('Mateo',     'Moreno Álvarez',     '2021-12-28', 17, 2),
('Emma',      'Álvarez Muñoz',      '2022-08-03', 18, 2),
('Daniel',    'Muñoz Romero',       '2022-02-17', 19, 2),
('Sofía',     'Romero Alonso',      '2021-10-11', 20, 2),
-- Infantil C (Grupo 3) - Niños de 5-6 años - Padres 21-25
('Pablo',     'Alonso Navarro',     '2020-04-25', 21, 3),
('Julia',     'Navarro Gil',        '2020-09-08', 22, 3),
('Álvaro',    'Gil Blanco',         '2019-12-12', 23, 3),
('Lucía',     'Blanco Serrano',     '2020-06-30', 24, 3),
('Adrián',    'Serrano Castro',     '2019-08-20', 25, 3),
-- Primaria A (Grupo 4) - Niños de 6-7 años - Padres 26-30
('Carmen',    'Castro Ramos',       '2019-01-15', 26, 4),
('Nicolás',   'Ramos Suárez',       '2018-07-22', 27, 4),
('Claudia',   'Suárez Ortega',      '2019-03-11', 28, 4),
('Diego',     'Ortega Marín',       '2018-11-05', 29, 4),
('Irene',     'Marín Iglesias',     '2019-05-28', 30, 4),
-- Primaria B (Grupo 5) - Niños de 7-8 años - Padres 31-35
('Alejandro', 'Iglesias Peña',      '2018-02-14', 31, 5),
('Noa',       'Peña Cortés',        '2017-08-19', 32, 5),
('Marco',     'Cortés Montero',     '2018-04-03', 33, 5),
('Alma',      'Montero Vidal',      '2017-12-25', 34, 5),
('Iker',      'Vidal Rojas',        '2018-06-17', 35, 5),
-- Primaria C (Grupo 6) - Niños de 8-9 años - Padres 36-40
('Laia',      'Rojas Cano',         '2017-01-09', 36, 6),
('Bruno',     'Cano Prieto',        '2016-07-14', 37, 6),
('Vega',      'Prieto Mora',        '2017-03-22', 38, 6),
('Enzo',      'Mora Pascual',       '2016-11-30', 39, 6),
('Candela',   'Pascual León',       '2017-05-06', 40, 6),
-- Juvenil A (Grupo 7) - Niños de 10-11 años - Padres 41-45
('Rodrigo',   'León Guerrero',      '2015-09-13', 41, 7),
('Daniela',   'Guerrero Santos',    '2014-12-01', 42, 7),
('Izan',      'Santos Medina',      '2015-04-18', 43, 7),
('Jimena',    'Medina Fuentes',     '2014-08-27', 44, 7),
('Gael',      'Fuentes Reyes',      '2015-02-05', 45, 7),
-- Juvenil B (Grupo 8) - Niños de 12-13 años - Padres 46-50
('Sara',      'Reyes Cabrera',      '2013-06-20', 46, 8),
('Marcos',    'Cabrera Herrera',    '2012-10-15', 47, 8),
('Chloe',     'Herrera Peña',       '2013-03-08', 48, 8),
('Eric',      'Campos Vargas',      '2012-07-25', 49, 8),
('Luna',      'Vargas Molina',      '2013-01-12', 50, 8),
-- Juvenil C (Grupo 9) - Niños de 14-15 años - Padres 51-55
('Aitor',     'Molina Cruz',        '2011-11-03', 51, 9),
('Ainara',    'Cruz Ortiz',         '2010-05-19', 52, 9),
('Unai',      'Ortiz Delgado',      '2011-08-07', 53, 9),
('Ariadna',   'Delgado Rubio',      '2010-12-14', 54, 9),
('Jan',       'Rubio Sanz',         '2011-03-29', 55, 9);
