-- Unified initialization script: creates database, schema, and seeds data
-- - 3 users
-- - 10 buildings
-- - 2..8 rooms per building
-- - 100 reservations with dates relative to NOW()

-- Drop and recreate database for a clean import
DROP DATABASE IF EXISTS `meetrooms`;
CREATE DATABASE IF NOT EXISTS `meetrooms` CHARACTER SET utf8;
USE `meetrooms`;

SET FOREIGN_KEY_CHECKS=0;

-- Users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `salt` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Buildings
DROP TABLE IF EXISTS `buildings`;
CREATE TABLE `buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(200) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_buildings_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Rooms
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `building_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `capacity` int(11) DEFAULT NULL,
  `features` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rooms_building` (`building_id`),
  UNIQUE KEY `uniq_rooms_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Reservations
DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room` varchar(200) NOT NULL,
  `room_id` int(11) NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reservations_room` (`room_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Foreign keys
ALTER TABLE `rooms` ADD CONSTRAINT `fk_rooms_building` FOREIGN KEY (`building_id`) REFERENCES `buildings`(`id`) ON DELETE CASCADE;
ALTER TABLE `reservations` ADD CONSTRAINT `fk_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;
ALTER TABLE `reservations` ADD CONSTRAINT `fk_reservations_room` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS=1;

-- Seed users
INSERT INTO users (username, email, password, salt) VALUES
('Admin', 'admin@admin.fr', SHA2(CONCAT('admin','dev-admin-salt-2025'), 256), 'dev-admin-salt-2025'),
('Alice', 'alice@example.com', SHA2(CONCAT('password','dev-salt-1'), 256), 'dev-salt-1'),
('Bob', 'bob@example.com', SHA2(CONCAT('password','dev-salt-2'), 256), 'dev-salt-2');

-- Seed buildings (10)
INSERT INTO buildings (id, code, name) VALUES
 (1,'B01','Siège Paris 1'),
 (2,'B02','Siège Paris 2'),
 (3,'B03','Lyon Part-Dieu'),
 (4,'B04','Lyon Confluence'),
 (5,'B05','Marseille Prado'),
 (6,'B06','Bordeaux Quinconces'),
 (7,'B07','Lille Euratech'),
 (8,'B08','Nantes Île de Nantes'),
 (9,'B09','Toulouse Blagnac'),
 (10,'B10','Rennes Gare');

-- Seed rooms per building (2..8 each)
INSERT INTO rooms (building_id, code, name, capacity) VALUES
 -- B01: 5
 (1,'B01-R01','Salle A101',6), (1,'B01-R02','Salle A102',8), (1,'B01-R03','Salle A201',10), (1,'B01-R04','Salle A202',4), (1,'B01-R05','Salle A301',12),
 -- B02: 3
 (2,'B02-R01','Salle B101',6), (2,'B02-R02','Salle B102',8), (2,'B02-R03','Salle B201',10),
 -- B03: 8
 (3,'B03-R01','Salle C101',6), (3,'B03-R02','Salle C102',8), (3,'B03-R03','Salle C201',10), (3,'B03-R04','Salle C202',4), (3,'B03-R05','Salle C301',12), (3,'B03-R06','Salle C302',6), (3,'B03-R07','Salle C401',14), (3,'B03-R08','Salle C402',8),
 -- B04: 2
 (4,'B04-R01','Salle D101',6), (4,'B04-R02','Salle D102',8),
 -- B05: 6
 (5,'B05-R01','Salle E101',6), (5,'B05-R02','Salle E102',8), (5,'B05-R03','Salle E201',10), (5,'B05-R04','Salle E202',4), (5,'B05-R05','Salle E301',12), (5,'B05-R06','Salle E302',6),
 -- B06: 4
 (6,'B06-R01','Salle F101',6), (6,'B06-R02','Salle F102',8), (6,'B06-R03','Salle F201',10), (6,'B06-R04','Salle F202',4),
 -- B07: 7
 (7,'B07-R01','Salle G101',6), (7,'B07-R02','Salle G102',8), (7,'B07-R03','Salle G201',10), (7,'B07-R04','Salle G202',4), (7,'B07-R05','Salle G301',12), (7,'B07-R06','Salle G302',6), (7,'B07-R07','Salle G401',14),
 -- B08: 2
 (8,'B08-R01','Salle H101',6), (8,'B08-R02','Salle H102',8),
 -- B09: 8
 (9,'B09-R01','Salle I101',6), (9,'B09-R02','Salle I102',8), (9,'B09-R03','Salle I201',10), (9,'B09-R04','Salle I202',4), (9,'B09-R05','Salle I301',12), (9,'B09-R06','Salle I302',6), (9,'B09-R07','Salle I401',14), (9,'B09-R08','Salle I402',8),
 -- B10: 3
 (10,'B10-R01','Salle J101',6), (10,'B10-R02','Salle J102',8), (10,'B10-R03','Salle J201',10);

-- Seed features for rooms using deterministic mapping based on code hash
UPDATE rooms r
JOIN (SELECT id, code,
  CONV(SUBSTRING(MD5(code),1,2),16,10) AS hv
  FROM rooms) h ON h.id = r.id
SET r.features = TRIM(BOTH ', ' FROM CONCAT_WS(', ',
    CASE WHEN (h.hv % 2) = 0 THEN 'Tableau blanc' ELSE NULL END,
    CASE WHEN (h.hv % 3) = 0 THEN 'Visio' ELSE NULL END,
    CASE WHEN (h.hv % 4) = 0 THEN 'Éclairage naturel' ELSE NULL END,
    CASE WHEN (h.hv % 5) = 0 THEN 'Écran' ELSE NULL END,
    CASE WHEN (h.hv % 6) = 0 THEN 'Climatisation' ELSE NULL END,
    CASE WHEN (h.hv % 7) = 0 THEN 'Paperboard' ELSE NULL END
));

-- Seed 100 reservations relative to NOW()
-- Generate 100 reservations via CTEs (numbers 0..99)
INSERT INTO reservations (room, room_id, start_datetime, end_datetime, user_id, comment, created_at)
SELECT rm_named.name AS room, rm_named.id AS room_id,
       STR_TO_DATE(CONCAT(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL ((nums.n % 20) - 10) DAY), '%Y-%m-%d'), ' ', LPAD(9 + (nums.n % 8),2,'0'), ':00:00'), '%Y-%m-%d %H:%i:%s') AS start_dt,
       DATE_ADD(STR_TO_DATE(CONCAT(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL ((nums.n % 20) - 10) DAY), '%Y-%m-%d'), ' ', LPAD(9 + (nums.n % 8),2,'0'), ':00:00'), '%Y-%m-%d %H:%i:%s'), INTERVAL 60 MINUTE) AS end_dt,
       u.id AS user_id,
       CONCAT('Seed ', nums.n) AS comment,
       NOW()
FROM (
  SELECT a.n*10 + b.n AS n
  FROM (
    SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
  ) a
  CROSS JOIN (
    SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
  ) b
) nums
JOIN (
  SELECT r.id, r.name, (@rownum:=@rownum+1) AS rn
  FROM (SELECT @rownum:= -1) init, rooms r
  ORDER BY r.id
) rm_named ON 1=1
JOIN (SELECT COUNT(*) AS c FROM rooms) rc ON 1=1
JOIN (
  SELECT 'alice@example.com' AS email, 0 AS modv
  UNION ALL SELECT 'bob@example.com', 1
  UNION ALL SELECT 'admin@admin.fr', 2
) u_map ON (nums.n % 3) = u_map.modv
JOIN users u ON u.email = u_map.email
WHERE rm_named.rn = (nums.n % rc.c)
LIMIT 100;

-- Ensure reservation.room is filled (redundant safety)
UPDATE reservations r
JOIN rooms rm ON r.room_id = rm.id
SET r.room = rm.name
WHERE r.room IS NULL OR r.room = '';

-- Ensure a busy demo day: exactly 8 reservations on J+3 to showcase calendar density
-- Times: 09:00,10:00,11:00,13:00,14:00,15:00,16:00,17:00 with 30-60 min durations
DELETE FROM reservations WHERE DATE(start_datetime) = DATE_ADD(CURDATE(), INTERVAL 3 DAY);
INSERT INTO reservations (room, room_id, start_datetime, end_datetime, user_id, comment, created_at)
SELECT rsel.name AS room, rsel.id AS room_id,
       STR_TO_DATE(CONCAT(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY),'%Y-%m-%d'), ' ', t.hh, ':', t.mm, ':00'), '%Y-%m-%d %H:%i:%s') AS start_dt,
       DATE_ADD(STR_TO_DATE(CONCAT(DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 3 DAY),'%Y-%m-%d'), ' ', t.hh, ':', t.mm, ':00'), '%Y-%m-%d %H:%i:%s'), INTERVAL t.dur MINUTE) AS end_dt,
       u.id AS user_id,
       CONCAT('Démo J+3 • Slot ', t.ord+1) AS comment,
       NOW()
FROM (
  SELECT 0 AS ord, '09' AS hh, '00' AS mm, 60 AS dur UNION ALL
  SELECT 1,'10','00',60 UNION ALL
  SELECT 2,'11','00',60 UNION ALL
  SELECT 3,'13','00',60 UNION ALL
  SELECT 4,'14','00',60 UNION ALL
  SELECT 5,'15','00',60 UNION ALL
  SELECT 6,'16','00',30 UNION ALL
  SELECT 7,'17','00',30
) t
JOIN (
  SELECT rr.id, rr.name, (@rn:=@rn+1) AS rn
  FROM (SELECT @rn:= -1) init, (SELECT id, name FROM rooms ORDER BY id LIMIT 8) rr
) rsel ON rsel.rn = t.ord
JOIN users u ON u.email = 'admin@admin.fr';
