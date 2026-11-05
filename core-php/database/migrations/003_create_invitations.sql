CREATE TABLE invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    role ENUM('admin','member') NOT NULL DEFAULT 'member',
    token CHAR(64) NOT NULL UNIQUE,
    invited_by BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id),
    INDEX idx_email_status (email, status)
);