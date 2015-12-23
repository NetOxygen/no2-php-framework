DROP TABLE IF EXISTS users_roles;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id              INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fullname        VARCHAR(255)   NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE roles (
    id              INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255)   NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE users_roles (
    user_id      INTEGER DEFAULT NULL,
    INDEX user_index (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id),

    role_id      INTEGER DEFAULT NULL,
    INDEX role_index (role_id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
