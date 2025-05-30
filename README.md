## DATABASE SHOULD BE NAMED: foundit
- XAMPP
- Project must be under XAMPP folder.

## SETUP
- XAMPP should be in LOCAL C incase it doesn't work.
- FOUNDIT should be under XAMPP/htdocs/FoundIT

```
-- Roles
CREATE TABLE roles (
    id INT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE
);

INSERT INTO roles (id, name) VALUES
    (1, 'REGULAR'),
    (2, 'STAFF'),
    (3, 'ADMIN');

```
```
-- Accounts
CREATE TABLE accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    student_number VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role INT NOT NULL DEFAULT 1 COMMENT '1 = REGULAR, 2 = STAFF, 3 = ADMIN',
    status TINYINT DEFAULT 1 COMMENT '1 = Active, 2 = Archived',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_student_number (student_number),
    INDEX idx_role (role),
    CONSTRAINT fk_accounts_role FOREIGN KEY (role) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```


```
-- Items
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                -- References accounts(id)
    name VARCHAR(255) NOT NULL,          -- Item Name
    category VARCHAR(100) NOT NULL,      -- Category
    description TEXT,                    -- Description
    date_time DATETIME NOT NULL,         -- Date & Time of submission or event
    location VARCHAR(255),               -- General Location
    specific_location VARCHAR(255),      -- Specific Location
    left_with VARCHAR(255),              -- For Found Item
    image VARCHAR(255),                  -- Image file path or URL
    contact_method VARCHAR(255),         -- Contact Method (email, phone, etc.)
    status TINYINT(1) DEFAULT 1,         -- 1 = active, 0 = archived
    type ENUM('lost', 'found') NOT NULL, -- 'lost' or 'found'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, -- When the record was created
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Last update
    FOREIGN KEY (user_id) REFERENCES accounts(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```
-- Reports
CREATE TABLE item_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    user_id INT NOT NULL,         -- The user who reported
    author_id INT NOT NULL,       -- The owner of the reported item
    type VARCHAR(64) NOT NULL,
    details TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES accounts(id) ON DELETE CASCADE
);
```

```
-- Activity Log Table
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    item_id INT DEFAULT NULL,
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

```
```
CREATE TABLE activity_log_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,                -- references accounts(id) of the admin/staff
    action VARCHAR(64) NOT NULL,          -- e.g. 'archive_item', 'delete_user', 'update_report'
    target_type VARCHAR(32),              -- e.g. 'item', 'user', 'report'
    target_id INT,                        -- id of the affected entity (item/user/report)
    details TEXT,                         -- optional: more info about the action
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES accounts(id)
);
```
```
-- Inbox Table
CREATE TABLE inbox (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_user_id INT DEFAULT NULL,
    item_id INT DEFAULT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```



### CHANGES
```
Cram Day 1:
- Moved to XAMPP
- Connected database
- User Dashboard : Semi Done (profile, dashboard, inbox, my-items) : Not Done: submit item & activity log
- Login & Signup : Full Done all connected to database.
- Admin Dashboard : Static
- Lost & Found Page : Static
- Database Table : accounts, items, activity_log and inbox.
- PHP Apis
```
