-- Sazug Timetable System - PostgreSQL Schema optimized for Neon DB

-- 1. Users (For Admin Authentication)
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. System Settings (Key-Value pair for flexibility)
CREATE TABLE settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    description TEXT
);

-- 3. Custom Time Slots
CREATE TABLE time_slots (
    id SERIAL PRIMARY KEY,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_break BOOLEAN DEFAULT FALSE,
    order_index INTEGER NOT NULL UNIQUE,
    UNIQUE(start_time, end_time)
);

-- 4. Programs & Levels
CREATE TABLE programs (
    id SERIAL PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE levels (
    id SERIAL PRIMARY KEY,
    name VARCHAR(20) UNIQUE NOT NULL -- e.g., '100 Level', '200 Level'
);

-- 5. Rooms (Respects capacity and type)
CREATE TABLE rooms (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    capacity INTEGER NOT NULL CHECK (capacity > 0),
    room_type VARCHAR(50) DEFAULT 'Lecture Hall' CHECK (room_type IN ('Lecture Hall', 'Laboratory', 'Computer Lab'))
);

-- 6. Lecturers
CREATE TABLE lecturers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE
);

-- 7. Lecturer Unavailability (Soft/Hard constraints)
CREATE TABLE lecturer_unavailability (
    id SERIAL PRIMARY KEY,
    lecturer_id INTEGER REFERENCES lecturers(id) ON DELETE CASCADE,
    day_of_week VARCHAR(15) CHECK (day_of_week IN ('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')),
    time_slot_id INTEGER REFERENCES time_slots(id) ON DELETE CASCADE,
    UNIQUE(lecturer_id, day_of_week, time_slot_id)
);

-- 8. Courses (Supports multi-hour logic and links to lecturer/program/level)
CREATE TABLE courses (
    id SERIAL PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(150) NOT NULL,
    duration_hours INTEGER NOT NULL CHECK (duration_hours BETWEEN 1 AND 3),
    is_practical BOOLEAN DEFAULT FALSE,
    students_count INTEGER NOT NULL CHECK (students_count > 0),
    program_id INTEGER REFERENCES programs(id) ON DELETE RESTRICT,
    level_id INTEGER REFERENCES levels(id) ON DELETE RESTRICT,
    lecturer_id INTEGER REFERENCES lecturers(id) ON DELETE SET NULL
);

-- 9. Timetable Matrix (The generated schedule)
CREATE TABLE timetable (
    id SERIAL PRIMARY KEY,
    course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
    room_id INTEGER REFERENCES rooms(id) ON DELETE CASCADE,
    day_of_week VARCHAR(15) NOT NULL CHECK (day_of_week IN ('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')),
    time_slot_id INTEGER REFERENCES time_slots(id) ON DELETE CASCADE,
    is_locked BOOLEAN DEFAULT FALSE, -- Allows admin to manually pin a scheduled slot
    UNIQUE(room_id, day_of_week, time_slot_id), -- Prevents room double-booking
    UNIQUE(course_id, day_of_week, time_slot_id) -- Prevents course double-booking
);

-- ==========================================================
-- SEED DATA
-- ==========================================================

-- Default Admin (Password is 'admin123' - PLEASE CHANGE IN PRODUCTION)
INSERT INTO users (username, password_hash) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Core Settings
INSERT INTO settings (setting_key, setting_value, description) VALUES 
('allow_saturdays', 'false', 'Enable or disable Saturday lectures'),
('academic_semester', 'First Semester 2026/2027', 'Current academic semester string');

-- Default Time Slots (7am to 6pm with 1pm Break)
INSERT INTO time_slots (start_time, end_time, is_break, order_index) VALUES 
('07:00', '08:00', FALSE, 1),
('08:00', '09:00', FALSE, 2),
('09:00', '10:00', FALSE, 3),
('10:00', '11:00', FALSE, 4),
('11:00', '12:00', FALSE, 5),
('12:00', '13:00', FALSE, 6),
('13:00', '14:00', TRUE, 7), -- 1pm to 2pm Break
('14:00', '15:00', FALSE, 8),
('15:00', '16:00', FALSE, 9),
('16:00', '17:00', FALSE, 10),
('17:00', '18:00', FALSE, 11);

-- Default Programs & Levels
INSERT INTO programs (code, name) VALUES 
('MTH', 'Mathematics'),
('CSC', 'Computer Science'),
('STA', 'Statistics');

INSERT INTO levels (name) VALUES 
('100 Level'), ('200 Level'), ('300 Level'), ('400 Level');

-- Sample Rooms
INSERT INTO rooms (name, capacity, room_type) VALUES 
('Math Lecture Hall A', 150, 'Lecture Hall'),
('Math Lecture Hall B', 100, 'Lecture Hall'),
('Computer Lab 1', 50, 'Computer Lab');
