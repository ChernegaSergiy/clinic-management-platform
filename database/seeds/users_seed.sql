-- Пароль для всіх користувачів: 'password'
-- Хеш для 'password': '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy'

INSERT INTO users (username, password_hash, email, first_name, last_name, role_id) VALUES
('admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'admin@clinic.ua', 'Адмін', 'Адміненко', 1),
('doctor', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'doctor@clinic.ua', 'Лікар', 'Лікаренко', 2),
('registrar', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'registrar@clinic.ua', 'Реєстратор', 'Реєстраторенко', 3);
