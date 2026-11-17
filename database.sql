CREATE DATABASE IF NOT EXISTS sweet_dreams
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sweet_dreams;

CREATE TABLE categories (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(30) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE features (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_features_name (name)
) ENGINE=InnoDB;

CREATE TABLE category_features (
    category_id TINYINT UNSIGNED NOT NULL,
    feature_id TINYINT UNSIGNED NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (category_id, feature_id),
    CONSTRAINT fk_cf_category FOREIGN KEY (category_id) REFERENCES categories (id),
    CONSTRAINT fk_cf_feature FOREIGN KEY (feature_id) REFERENCES features (id)
) ENGINE=InnoDB;

CREATE TABLE rooms (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    number VARCHAR(10) NOT NULL,
    category_id TINYINT UNSIGNED NOT NULL,
    price INT UNSIGNED NOT NULL,
    image VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rooms_number (number),
    CONSTRAINT fk_rooms_category FOREIGN KEY (category_id) REFERENCES categories (id)
) ENGINE=InnoDB;

CREATE TABLE statuses (
    id TINYINT UNSIGNED NOT NULL,
    name VARCHAR(20) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_statuses_name (name)
) ENGINE=InnoDB;

CREATE TABLE bookings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_id SMALLINT UNSIGNED NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(16) NOT NULL,
    email VARCHAR(100) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status_id TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bookings_room_status (room_id, status_id),
    CONSTRAINT fk_bookings_room FOREIGN KEY (room_id) REFERENCES rooms (id),
    CONSTRAINT fk_bookings_status FOREIGN KEY (status_id) REFERENCES statuses (id),
    CONSTRAINT chk_bookings_dates CHECK (check_out > check_in)
) ENGINE=InnoDB;

CREATE TABLE admins (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    login VARCHAR(30) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_login (login)
) ENGINE=InnoDB;

INSERT INTO categories (id, name) VALUES (1, 'Стандартный'), (2, 'Студия'), (3, 'Люкс');

INSERT INTO features (id, name) VALUES
    (1, 'Полный санузел'), (2, 'Двуспальная кровать'), (3, 'Телевизор'),
    (4, 'Кухонный уголок'), (5, 'Холодильник'), (6, 'Двухкомнатный номер'), (7, 'Кондиционер');

INSERT INTO category_features (category_id, feature_id, sort_order) VALUES
    (1, 1, 1), (1, 2, 2), (1, 3, 3),
    (2, 4, 1), (2, 1, 2), (2, 2, 3), (2, 3, 4), (2, 5, 5),
    (3, 6, 1), (3, 1, 2), (3, 2, 3), (3, 3, 4), (3, 5, 5), (3, 7, 6);

INSERT INTO rooms (id, number, category_id, price, image) VALUES
    (1, '101', 1, 3000, 'standart.jpg'),
    (2, '102', 1, 3400, 'Станд_Кинг_1-1620x1080.jpg'),
    (3, '103', 1, 3800, 'garden-ring-hotel-moscow-suit-room-01.jpg'),
    (4, '201', 2, 4600, 'ee3d8d71bcbd5f9531616f975035f0e9.jpg'),
    (5, '202', 2, 5400, 'Vert-16.jpg'),
    (6, '203', 2, 6200, '10.jpg'),
    (7, '301', 3, 8500, 'Люкс 1.jpg'),
    (8, '302', 3, 11500, '487d7588b2bc7b4066334ea328e7a7bb.jpg'),
    (9, '303', 3, 15000, 'ren_037_ren03663_hd.jpg');

INSERT INTO statuses (id, name) VALUES (1, 'Новая'), (2, 'Одобрена'), (3, 'Отклонена');

INSERT INTO admins (login, password_hash) VALUES
    ('hotel123', '$2y$10$OY.DzuV23iPOB11BiWQheu9CeRaPPMc.FG86n2ZYTCKkZzEuWSCkq');
