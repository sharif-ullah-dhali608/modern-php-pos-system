<?php
$host = "localhost";
$username = "root";
$password = "root";
$database = "pos_system";

$conn = mysqli_connect($host, $username, $password, $database);

if(!$conn)
{
    die("Connection Failed: ". mysqli_connect_error());
}

/**
 * Ensure required tables exist.
 * Runs lightweight CREATE TABLE IF NOT EXISTS statements so it is safe to call on every request.
 */
function ensure_core_tables(mysqli $conn) {
    // --- 1. Core Tables ---

    // Stores table
    $storesSql = "CREATE TABLE IF NOT EXISTS stores (
        id INT(11) NOT NULL AUTO_INCREMENT,
        store_name VARCHAR(255) NOT NULL,
        store_code VARCHAR(50) NOT NULL UNIQUE,
        business_type VARCHAR(100) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        city_zip VARCHAR(255) DEFAULT NULL,
        vat_number VARCHAR(100) DEFAULT NULL,
        timezone VARCHAR(50) DEFAULT 'Asia/Dhaka',
        max_line_disc DECIMAL(10,2) DEFAULT 0,
        max_inv_disc DECIMAL(10,2) DEFAULT 0,
        approval_disc DECIMAL(10,2) DEFAULT 0,
        overselling VARCHAR(20) DEFAULT 'deny',
        low_stock INT(11) DEFAULT 5,
        status TINYINT(1) DEFAULT 1,
        daily_target DECIMAL(18,2) DEFAULT 0,
        open_time VARCHAR(10) DEFAULT '09:00',
        close_time VARCHAR(10) DEFAULT '21:00',
        allow_manual_price TINYINT(1) DEFAULT 0,
        allow_backdate TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Currencies table
    $currencySql = "CREATE TABLE IF NOT EXISTS currencies (
        id INT(11) NOT NULL AUTO_INCREMENT,
        currency_name VARCHAR(255) NOT NUll,
        code VARCHAR(3) NOT NULL UNIQUE,
        symbol_left VARCHAR(10) DEFAULT NULL,
        symbol_right VARCHAR(10) DEFAULT NULL,
        decimal_place INT(1) DEFAULT 2,
        status TINYINT(1) DEFAULT 1,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    // Payment Methods
    $paymentSql = "CREATE TABLE IF NOT EXISTS payment_methods (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        code VARCHAR(100) NOT NULL UNIQUE,
        details TEXT DEFAULT NULL,
        status TINYINT(1) DEFAULT 1,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    // Units
    $unitsSql = "CREATE TABLE IF NOT EXISTS units (
        id INT(11) NOT NULL AUTO_INCREMENT,
        unit_name VARCHAR(255) NOT NULL,
        code VARCHAR(100) NOT NULL UNIQUE,
        details TEXT DEFAULT NULL,
        status TINYINT(1) DEFAULT 1,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Brands
    $brandsSql = "CREATE TABLE IF NOT EXISTS brands (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        code VARCHAR(100) NOT NULL UNIQUE,
        thumbnail VARCHAR(500) DEFAULT NULL,
        details TEXT DEFAULT NULL,
        status TINYINT(1) DEFAULT 1,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Tax rates
    $taxSql = "CREATE TABLE IF NOT EXISTS taxrates (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        code VARCHAR(100) NOT NULL UNIQUE,
        taxrate DECIMAL(10,2) NOT NULL DEFAULT 0,
        status TINYINT(1) DEFAULT 1,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    // --- 2. Pivot Tables (Junction Tables) ---

    // Store-currency pivot (already present)
    $storeCurrencySql = "CREATE TABLE IF NOT EXISTS store_currency (
        id INT(11) NOT NULL AUTO_INCREMENT,
        store_id INT(11) NOT NULL,
        currency_id INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY store_currency_unique (store_id, currency_id),
        KEY store_id (store_id),
        KEY currency_id (currency_id),
        CONSTRAINT store_currency_store_fk FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
        CONSTRAINT store_currency_currency_fk FOREIGN KEY (currency_id) REFERENCES currencies (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Payment-Store pivot (MISSING TABLE - Fixes the previous fatal error)
    $paymentStoreMapSql = "CREATE TABLE IF NOT EXISTS payment_store_map (
        id INT(11) NOT NULL AUTO_INCREMENT,
        payment_method_id INT(11) NOT NULL,
        store_id INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY payment_store_unique (payment_method_id, store_id),
        KEY payment_method_id (payment_method_id),
        KEY store_id (store_id),
        CONSTRAINT payment_store_payment_fk FOREIGN KEY (payment_method_id) REFERENCES payment_methods (id) ON DELETE CASCADE,
        CONSTRAINT payment_store_store_fk FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";



// Brand-Store Pivot
    $brandStoreSql = "CREATE TABLE IF NOT EXISTS brand_store (
        id INT(11) NOT NULL AUTO_INCREMENT,
        brand_id INT(11) NOT NULL,
        store_id INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY brand_store_unique (brand_id, store_id),
        KEY brand_id (brand_id),
        KEY store_id (store_id),
        CONSTRAINT brand_store_brand_fk FOREIGN KEY (brand_id) REFERENCES brands (id) ON DELETE CASCADE,
        CONSTRAINT brand_store_store_fk FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    // --- 3. Execute Queries ---
    

   // -- Taxrates and Stores Mapping (Pivot Table)
    $taxStoreSql ="CREATE TABLE IF NOT EXISTS taxrate_store_map (
    id INT(11) NOT NULL AUTO_INCREMENT,
    taxrate_id INT(11) NOT NULL,
    store_id INT(11) NOT NULL,
    PRIMARY KEY (id),
    -- Foreign keys for data integrity
    CONSTRAINT fk_taxrate FOREIGN KEY (taxrate_id) 
        REFERENCES taxrates(id) ON DELETE CASCADE,
    CONSTRAINT fk_store FOREIGN KEY (store_id) 
        REFERENCES stores(id) ON DELETE CASCADE,
    -- Unique constraint taaki ek hi store mein ek hi taxrate do baar map na ho
    UNIQUE KEY unique_map (taxrate_id, store_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


    // Core tables
    mysqli_query($conn, $storesSql);
    mysqli_query($conn, $currencySql);
    mysqli_query($conn, $paymentSql);
    mysqli_query($conn, $unitsSql);
    mysqli_query($conn, $brandsSql);
    mysqli_query($conn, $taxSql);
    
    // Pivot tables (must run after core tables are created)
    mysqli_query($conn, $storeCurrencySql);
    mysqli_query($conn, $paymentStoreMapSql); // NEW: Execution of the missing table
    mysqli_query($conn, $brandStoreSql);
    mysqli_query($conn, $taxStoreSql);


}

ensure_core_tables($conn);
?>