CREATE TABLE IF NOT EXISTS urls
(
    id         BIGSERIAL PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS url_checks
(
    id          BIGSERIAL PRIMARY KEY,
    url_id      INTEGER      NOT NULL,
    status_code INTEGER      NULL,
    h1          VARCHAR(255) NULL,
    title       TEXT         NULL,
    description TEXT         NULL,
    created_at  TIMESTAMP NOT NULL,
    FOREIGN KEY (url_id) REFERENCES urls (Id) ON DELETE CASCADE
);