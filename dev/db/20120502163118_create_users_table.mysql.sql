CREATE TABLE users (
    id              INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,

    updated_at      DATETIME NOT NULL,
    created_at      DATETIME NOT NULL,

    updated_by      INTEGER DEFAULT NULL,
    INDEX updated_by_index (updated_by),
    FOREIGN KEY (updated_by) REFERENCES users(id),
    created_by      INTEGER DEFAULT NULL,
    INDEX created_by_index (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id),

    fullname        VARCHAR(255)   NOT NULL,
    gender          ENUM('M', 'F', '?') NOT NULL DEFAULT '?',
    role            VARCHAR(255)   NOT NULL,
    email           VARCHAR(255)   NOT NULL UNIQUE,
    passwd          VARCHAR(255)   NOT NULL,
    is_active       BOOLEAN        NOT NULL DEFAULT TRUE,
    description     TEXT           NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
