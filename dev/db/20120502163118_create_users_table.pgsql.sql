CREATE TYPE users_gender AS ENUM('?', 'M', 'F');
CREATE TYPE users_role AS ENUM('admin', 'user');
CREATE TABLE users (
    id              UUID PRIMARY KEY DEFAULT uuid_generate_v4(),

    created_at      TIMESTAMP WITH TIME ZONE DEFAULT current_timestamp,
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT current_timestamp,

    created_by      UUID DEFAULT NULL REFERENCES users(id),
    updated_by      UUID DEFAULT NULL REFERENCES users(id),

    fullname        VARCHAR(255)    NOT NULL,
    gender          users_gender    NOT NULL DEFAULT '?',
    role            users_role      NOT NULL,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    passwd          VARCHAR(255)    NOT NULL,
    is_active       BOOLEAN         NOT NULL DEFAULT TRUE,
    description     TEXT            NOT NULL DEFAULT ''
);
