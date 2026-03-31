CREATE TABLE IF NOT EXISTS makaew_store_material (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    category_id INT UNSIGNED DEFAULT NULL,
    unit VARCHAR(50) NOT NULL DEFAULT 'шт',
    consumption_rate DECIMAL(10, 4) DEFAULT NULL COMMENT 'Расход на единицу площади',
    consumption_unit VARCHAR(50) DEFAULT NULL COMMENT 'Единица измерения расхода (кг/м2, л/м2)',
    description TEXT DEFAULT NULL,
    sort INT NOT NULL DEFAULT 500,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_code (code),
    KEY idx_category (category_id),
    KEY idx_active_sort (active, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS makaew_store_material_category (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    depth_level INT UNSIGNED NOT NULL DEFAULT 0,
    sort INT NOT NULL DEFAULT 500,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uk_code (code),
    KEY idx_parent (parent_id),
    KEY idx_active_sort (active, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS makaew_store_calculation_log (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED DEFAULT NULL,
    material_id INT UNSIGNED NOT NULL,
    area DECIMAL(10, 2) NOT NULL,
    calculated_amount DECIMAL(10, 2) NOT NULL,
    params JSON DEFAULT NULL COMMENT 'Доп. параметры расчёта',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user (user_id),
    KEY idx_material (material_id),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
