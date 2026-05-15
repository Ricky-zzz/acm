USE db_countera;

START TRANSACTION;

-- Resolve IDs from existing records
SET @city_id := (
  SELECT id FROM cities WHERE name = 'Lipa City' LIMIT 1
);

SET @p1_id := (
  SELECT id FROM parties WHERE alias = 'P1' LIMIT 1
);

SET @p2_id := (
  SELECT id FROM parties WHERE alias = 'P2' LIMIT 1
);

SET @mayor_id := (
  SELECT id FROM positions WHERE city_id = @city_id AND title = 'Mayor' LIMIT 1
);

SET @vice_mayor_id := (
  SELECT id FROM positions WHERE city_id = @city_id AND title = 'Vice Mayor' LIMIT 1
);

SET @councilor_id := (
  SELECT id FROM positions WHERE city_id = @city_id AND title = 'City Councilor' LIMIT 1
);

-- Optional cleanup: clear existing candidates for these 3 positions
DELETE FROM candidates
WHERE position_id IN (@mayor_id, @vice_mayor_id, @councilor_id);

-- Mayor (2)
INSERT INTO candidates (position_id, name, party_id) VALUES
(@mayor_id, 'Mayor Candidate P1', @p1_id),
(@mayor_id, 'Mayor Candidate P2', @p2_id);

-- Vice Mayor (2)
INSERT INTO candidates (position_id, name, party_id) VALUES
(@vice_mayor_id, 'Vice Mayor Candidate P1', @p1_id),
(@vice_mayor_id, 'Vice Mayor Candidate P2', @p2_id);

-- City Councilor (24 total: 12 P1 + 12 P2)
INSERT INTO candidates (position_id, name, party_id) VALUES
(@councilor_id, 'Councilor 01 - P1', @p1_id),
(@councilor_id, 'Councilor 02 - P1', @p1_id),
(@councilor_id, 'Councilor 03 - P1', @p1_id),
(@councilor_id, 'Councilor 04 - P1', @p1_id),
(@councilor_id, 'Councilor 05 - P1', @p1_id),
(@councilor_id, 'Councilor 06 - P1', @p1_id),
(@councilor_id, 'Councilor 07 - P1', @p1_id),
(@councilor_id, 'Councilor 08 - P1', @p1_id),
(@councilor_id, 'Councilor 09 - P1', @p1_id),
(@councilor_id, 'Councilor 10 - P1', @p1_id),
(@councilor_id, 'Councilor 11 - P1', @p1_id),
(@councilor_id, 'Councilor 12 - P1', @p1_id),

(@councilor_id, 'Councilor 13 - P2', @p2_id),
(@councilor_id, 'Councilor 14 - P2', @p2_id),
(@councilor_id, 'Councilor 15 - P2', @p2_id),
(@councilor_id, 'Councilor 16 - P2', @p2_id),
(@councilor_id, 'Councilor 17 - P2', @p2_id),
(@councilor_id, 'Councilor 18 - P2', @p2_id),
(@councilor_id, 'Councilor 19 - P2', @p2_id),
(@councilor_id, 'Councilor 20 - P2', @p2_id),
(@councilor_id, 'Councilor 21 - P2', @p2_id),
(@councilor_id, 'Councilor 22 - P2', @p2_id),
(@councilor_id, 'Councilor 23 - P2', @p2_id),
(@councilor_id, 'Councilor 24 - P2', @p2_id);

COMMIT;



USE db_countera;

START TRANSACTION;

-- Resolve IDs from existing records
SET @city_id := (
  SELECT id FROM cities WHERE name = 'Lipa City' LIMIT 1
);

SET @p1_id := (
  SELECT id FROM parties WHERE alias = 'P1' LIMIT 1
);

SET @p2_id := (
  SELECT id FROM parties WHERE alias = 'P2' LIMIT 1
);

SET @mayor_id := (
  SELECT id FROM positions WHERE city_id = @city_id AND title = 'Mayor' LIMIT 1
);

SET @vice_mayor_id := (
  SELECT id FROM positions WHERE city_id = @city_id AND title = 'Vice Mayor' LIMIT 1
);

SET @councilor_id := (
  SELECT id FROM positions WHERE city_id = @city_id AND title = 'City Councilor' LIMIT 1
);

-- Clear existing candidates for these positions (optional but recommended)
DELETE FROM candidates
WHERE position_id IN (@mayor_id, @vice_mayor_id, @councilor_id);

-- Mayor (2)
INSERT INTO candidates (position_id, name, party_id) VALUES
(@mayor_id, 'Ramon Dela Cruz', @p1_id),
(@mayor_id, 'Eduardo Villanueva', @p2_id);

-- Vice Mayor (2)
INSERT INTO candidates (position_id, name, party_id) VALUES
(@vice_mayor_id, 'Lorenzo Santos', @p1_id),
(@vice_mayor_id, 'Miguel Bautista', @p2_id);

-- City Councilor (24 total: 12 P1 + 12 P2)
INSERT INTO candidates (position_id, name, party_id) VALUES
(@councilor_id, 'Antonio Reyes', @p1_id),
(@councilor_id, 'Jose Mercado', @p1_id),
(@councilor_id, 'Paolo Fernandez', @p1_id),
(@councilor_id, 'Mark Anthony Lim', @p1_id),
(@councilor_id, 'Carlo Mendoza', @p1_id),
(@councilor_id, 'Ryan Torres', @p1_id),
(@councilor_id, 'Nathaniel Cruz', @p1_id),
(@councilor_id, 'Patrick Valdez', @p1_id),
(@councilor_id, 'Vincent Navarro', @p1_id),
(@councilor_id, 'Kenneth Garcia', @p1_id),
(@councilor_id, 'Jerome Castillo', @p1_id),
(@councilor_id, 'Francis Alcantara', @p1_id),

(@councilor_id, 'Daniel Robles', @p2_id),
(@councilor_id, 'Leo Marquez', @p2_id),
(@councilor_id, 'Adrian Pineda', @p2_id),
(@councilor_id, 'Brian Solis', @p2_id),
(@councilor_id, 'Harold Aquino', @p2_id),
(@councilor_id, 'Julius Navarro', @p2_id),
(@councilor_id, 'Emmanuel Rojas', @p2_id),
(@councilor_id, 'Kevin Bautista', @p2_id),
(@councilor_id, 'Rafael Dominguez', @p2_id),
(@councilor_id, 'Gilbert Ramos', @p2_id),
(@councilor_id, 'Noel Serrano', @p2_id),
(@councilor_id, 'Victor Salazar', @p2_id);

COMMIT;