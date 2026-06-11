CREATE DATABASE IF NOT EXISTS course_registration; 

USE course_registration;

CREATE TABLE students (
    mssv VARCHAR(10) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) UNIQUE NOT NULL,
    address TEXT NOT NULL
);

CREATE TABLE courses (
    course_id VARCHAR(10) PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    description TEXT,
    credits INT CHECK(credits > 0)
);

CREATE TABLE enrollment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mssv VARCHAR(10) NOT NULL,
    course_id VARCHAR(10) NOT NULL,
    registration_date DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (mssv) REFERENCES students(mssv) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
);
