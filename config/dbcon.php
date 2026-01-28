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
        currency_id INT(11) DEFAULT NULL,
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
        PRIMARY KEY (id),
        FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE SET NULL
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

    // --- NEW: Quotations Table ---
    $quotationsSql = "CREATE TABLE IF NOT EXISTS quotations (
        id INT(11) NOT NULL AUTO_INCREMENT,
        ref_no VARCHAR(50) NOT NULL,
        customer_id INT(11) DEFAULT NULL,
        supplier_id INT(11) DEFAULT NULL,
        date DATE DEFAULT NULL,
        subtotal DECIMAL(15,2) DEFAULT 0.00,
        discount DECIMAL(15,2) DEFAULT 0.00,
        order_tax_rate DECIMAL(10,2) DEFAULT 0.00,
        shipping_cost DECIMAL(15,2) DEFAULT 0.00,
        others_charge DECIMAL(15,2) DEFAULT 0.00,
        grand_total DECIMAL(15,2) DEFAULT 0.00,
        terms TEXT DEFAULT NULL,
        status TINYINT(1) DEFAULT 1,
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

    // --- NEW: Boxes Table ---
    $boxesSql = "CREATE TABLE IF NOT EXISTS boxes (
        id INT(11) NOT NULL AUTO_INCREMENT,
        box_name VARCHAR(255) NOT NULL,
        code_name VARCHAR(100) NOT NULL UNIQUE,
        barcode_id VARCHAR(100) NOT NULL UNIQUE,
        box_details TEXT DEFAULT NULL,
        shelf_number VARCHAR(100) DEFAULT NULL,
        storage_type VARCHAR(100) DEFAULT NULL,
        max_capacity INT(11) DEFAULT 0,
        status TINYINT(1) DEFAULT 1,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    

// --- Categories Table (Fix: Added IF NOT EXISTS) ---
    $categorySql = "CREATE TABLE IF NOT EXISTS categories (
        id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        category_code varchar(100) NOT NULL,
        slug varchar(255) NOT NULL,
        parent_id int(11) DEFAULT 0,
        thumbnail varchar(255) DEFAULT NULL,
        details text DEFAULT NULL,
        status tinyint(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
        sort_order int(11) DEFAULT 0,
        visibility_pos tinyint(1) DEFAULT 1 COMMENT '1=Visible, 0=Hidden',
        created_at timestamp NOT NULL DEFAULT current_timestamp(),
        updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (id),
        UNIQUE KEY category_code (category_code),
        UNIQUE KEY slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Suppliers Table ---
// --- Suppliers Table (Updated with Trade License & Bank Account) ---
    $suppliersSql = "CREATE TABLE IF NOT EXISTS suppliers (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        code_name VARCHAR(100) NOT NULL UNIQUE,
        trade_license_num VARCHAR(100) DEFAULT NULL,
        bank_account_num VARCHAR(100) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        mobile VARCHAR(50) NOT NULL,
        address TEXT DEFAULT NULL,
        city VARCHAR(100) DEFAULT NULL,
        state VARCHAR(100) DEFAULT NULL,
        country VARCHAR(100) DEFAULT NULL,
        details TEXT DEFAULT NULL,
        status TINYINT(1) DEFAULT 1,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Printers Table ---
    $printersSql = "CREATE TABLE IF NOT EXISTS printers (
        printer_id INT(11) NOT NULL AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        type VARCHAR(50) DEFAULT 'network',
        profile VARCHAR(50) DEFAULT 'thermal',
        char_per_line INT(11) DEFAULT 200,
        ip_address VARCHAR(50) DEFAULT NULL,
        port VARCHAR(10) DEFAULT '9100',
        path VARCHAR(255) DEFAULT NULL,
        status TINYINT(1) DEFAULT 1,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (printer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Printer-Store Map Table ---
    $printerStoreMapSql = "CREATE TABLE IF NOT EXISTS printer_store_map (
        id INT(11) NOT NULL AUTO_INCREMENT,
        printer_id INT(11) NOT NULL,
        store_id INT(11) NOT NULL,
        PRIMARY KEY (id),
        KEY printer_id (printer_id),
        KEY store_id (store_id),
        FOREIGN KEY (printer_id) REFERENCES printers(printer_id) ON DELETE CASCADE,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: CUSTOMERS Table (STREAMLINED) ---
    $customersSql = "CREATE TABLE IF NOT EXISTS customers (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        company_name VARCHAR(255) DEFAULT NULL,
        code_name VARCHAR(100) DEFAULT NULL,
        customer_group VARCHAR(100) DEFAULT '',
        membership_level VARCHAR(50) DEFAULT '',
        email VARCHAR(255) DEFAULT NULL,
        fax_number VARCHAR(100) DEFAULT NULL,
        mobile VARCHAR(50) NOT NULL,
        alt_mobile VARCHAR(50) DEFAULT NULL,
        address TEXT DEFAULT NULL,
        city VARCHAR(100) DEFAULT NULL,
        state VARCHAR(100) DEFAULT NULL,
        country VARCHAR(100) DEFAULT 'Bangladesh',
        details TEXT DEFAULT NULL,
        image VARCHAR(255) DEFAULT NULL,
        status TINYINT(1) DEFAULT 1,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        sex VARCHAR(20) DEFAULT 'Male',
        age INT(11) DEFAULT 0,
        dob DATE DEFAULT NULL,
        credit_balance DECIMAL(15,2) DEFAULT 0.00,
        opening_balance DECIMAL(15,2) DEFAULT 0.00,
        current_due DECIMAL(15,2) DEFAULT 0.00,
        credit_limit DECIMAL(15,2) DEFAULT 0.00,
        reward_points INT(11) DEFAULT 0,
        fixed_discount DECIMAL(15,2) DEFAULT 0.00,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Giftcards Table ---
    $giftcardsSql = "CREATE TABLE IF NOT EXISTS giftcards (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        card_no VARCHAR(64) NOT NULL UNIQUE,
        value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        customer_id INT DEFAULT NULL,
        expiry_date DATE NULL,
        image VARCHAR(255) DEFAULT NULL,
        created_by INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        status TINYINT(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (id),
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Giftcard Topups Table ---
    $giftcardTopupsSql = "CREATE TABLE IF NOT EXISTS giftcard_topups (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        giftcard_id INT UNSIGNED NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        note TEXT NULL,
        created_by INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX (giftcard_id),
        CONSTRAINT fk_giftcard_topups_giftcard FOREIGN KEY (giftcard_id) REFERENCES giftcards(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

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

    // --- UPDATED: Products Table ---
    $productsSql = "CREATE TABLE IF NOT EXISTS products (
        id INT(11) NOT NULL AUTO_INCREMENT,
        product_name VARCHAR(255) NOT NULL,
        product_code VARCHAR(100) NOT NULL UNIQUE,
        barcode_symbology VARCHAR(20) DEFAULT 'code128',
        category_id INT(11) NOT NULL,
        brand_id INT(11) DEFAULT NULL,
        unit_id INT(11) NOT NULL,
        purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        selling_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        
        /* NEW FIELDS ADDED HERE */
        wholesale_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        profit_margin DECIMAL(10,2) DEFAULT 0.00,
        box_id INT(11) DEFAULT NULL,
        currency_id INT(11) DEFAULT NULL,
        -- visibility_pos TINYINT(1) DEFAULT 1 COMMENT '1=Visible, 0=Hidden',
        sort_order INT(11) DEFAULT 0,
        
        tax_rate_id INT(11) DEFAULT NULL,
        tax_method VARCHAR(20) DEFAULT 'exclusive',
        opening_stock DECIMAL(15,2) UNSIGNED DEFAULT 0.00,
        alert_quantity INT(11) DEFAULT 5,
        per_customer_limit INT(11) DEFAULT 0,
        supplier_id INT(11) DEFAULT NULL,
        expire_date DATE DEFAULT NULL,
        description TEXT DEFAULT NULL,
        thumbnail VARCHAR(255) DEFAULT NULL,
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        
        /* FOREIGN KEYS */
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
        FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
        FOREIGN KEY (box_id) REFERENCES boxes(id) ON DELETE SET NULL,
        FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE SET NULL,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Product Images Table (For Multiple Images) --------------------------------------------------------
    $productImagesSql = "CREATE TABLE IF NOT EXISTS product_images (
        id INT(11) NOT NULL AUTO_INCREMENT,
        product_id INT(11) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
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

    // --- NEW: Box-Store Pivot ---
    $boxStoreSql = "CREATE TABLE IF NOT EXISTS box_stores (
        id INT(11) NOT NULL AUTO_INCREMENT,
        box_id INT(11) NOT NULL,
        store_id INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY box_store_unique (box_id, store_id),
        KEY box_id (box_id),
        KEY store_id (store_id),
        CONSTRAINT box_store_box_fk FOREIGN KEY (box_id) REFERENCES boxes (id) ON DELETE CASCADE,
        CONSTRAINT box_store_store_fk FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


    // --- Category-Store Pivot (Fix: Added IF NOT EXISTS) ---
    $categoryStoreSql ="CREATE TABLE IF NOT EXISTS category_store_map (
        id int(11) NOT NULL AUTO_INCREMENT,
        category_id int(11) NOT NULL,
        store_id int(11) NOT NULL,
        PRIMARY KEY (id),
        KEY category_id (category_id),
        KEY store_id (store_id),
        CONSTRAINT fk_category_map FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE,
        CONSTRAINT fk_store_map FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Supplier-Store Pivot ---
    $supplierStoreSql = "CREATE TABLE IF NOT EXISTS supplier_stores_map (
        id INT(11) NOT NULL AUTO_INCREMENT,
        supplier_id INT(11) NOT NULL,
        store_id INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY supplier_store_unique (supplier_id, store_id),
        KEY supplier_id (supplier_id),
        KEY store_id (store_id),
        CONSTRAINT supplier_store_supplier_fk FOREIGN KEY (supplier_id) REFERENCES suppliers (id) ON DELETE CASCADE,
        CONSTRAINT supplier_store_store_fk FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


     // --- Product-Store Pivot Table (with stock) ---
    $productStoreSql = "CREATE TABLE IF NOT EXISTS product_store_map (
        id INT(11) NOT NULL AUTO_INCREMENT,
        product_id INT(11) NOT NULL,
        store_id INT(11) NOT NULL,
        stock DECIMAL(15,4) UNSIGNED DEFAULT 0.0000,
        per_customer_limit INT(11) DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY product_store_unique (product_id, store_id),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";



        // --- NEW: Quotation Items Table ---
        $quotationItemsSql = "CREATE TABLE IF NOT EXISTS quotation_items (
            id INT(11) NOT NULL AUTO_INCREMENT,
            quotation_id INT(11) NOT NULL,
            product_id INT(11) NOT NULL,
            price DECIMAL(15,2) NOT NULL,
            qty DECIMAL(15,2) NOT NULL,
            tax_rate_id INT(11) DEFAULT NULL,
            tax_method VARCHAR(20) DEFAULT 'exclusive',
            subtotal DECIMAL(15,2) NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Purchase Info Table ---
    $purchaseInfoSql = "CREATE TABLE IF NOT EXISTS purchase_info (
        info_id INT(11) NOT NULL AUTO_INCREMENT,
        invoice_id VARCHAR(50) NOT NULL,
        inv_type VARCHAR(20) DEFAULT 'purchase',
        store_id INT(11) NOT NULL,
        sup_id INT(11) DEFAULT NULL,
        total_item DECIMAL(15,2) DEFAULT 0.00,
        order_tax DECIMAL(15,2) DEFAULT 0.00,        /* Notun Column */
        shipping_charge DECIMAL(15,2) DEFAULT 0.00,  /* Notun Column */
        discount_amount DECIMAL(15,2) DEFAULT 0.00,  /* Notun Column */
        status VARCHAR(20) DEFAULT 'stock',
        total_sell DECIMAL(15,2) DEFAULT 0.00,
        purchase_note TEXT DEFAULT NULL,
        attachment VARCHAR(500) DEFAULT NULL,
        is_visible TINYINT(1) DEFAULT 1,
        payment_status VARCHAR(20) DEFAULT 'due',
        checkout_status VARCHAR(20) DEFAULT NULL,
        shipping_status VARCHAR(20) DEFAULT 'received',
        created_by INT(11) NOT NULL,
        purchase_date DATE DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (info_id),
        UNIQUE KEY invoice_id (invoice_id),
        KEY store_id (store_id),
        KEY sup_id (sup_id),
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
        FOREIGN KEY (sup_id) REFERENCES suppliers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    // --- NEW: Purchase Item Table ---
    $purchaseItemSql = "CREATE TABLE IF NOT EXISTS purchase_item (
        id INT(11) NOT NULL AUTO_INCREMENT,
        invoice_id VARCHAR(50) NOT NULL,
        store_id INT(11) NOT NULL,
        item_id INT(11) NOT NULL,
        category_id INT(11) DEFAULT NULL,
        brand_id INT(11) DEFAULT NULL,
        item_name VARCHAR(255) NOT NULL,
        item_purchase_price DECIMAL(15,4) DEFAULT 0.0000,
        item_selling_price DECIMAL(15,4) DEFAULT 0.0000,
        item_quantity DECIMAL(15,4) DEFAULT 0.0000,
        total_sell DECIMAL(15,4) DEFAULT 0.0000,
        status VARCHAR(20) DEFAULT 'active',
        item_total DECIMAL(15,4) DEFAULT 0.0000,
        item_tax DECIMAL(15,4) DEFAULT 0.0000,
        tax_method VARCHAR(20) DEFAULT 'inclusive',
        tax DECIMAL(15,4) DEFAULT 0.0000,
        gst DECIMAL(15,4) DEFAULT 0.0000,
        cgst DECIMAL(15,4) DEFAULT 0.0000,
        sgst DECIMAL(15,4) DEFAULT 0.0000,
        igst DECIMAL(15,4) DEFAULT 0.0000,
        return_quantity DECIMAL(15,4) DEFAULT 0.0000,
        installment_quantity INT(11) DEFAULT 0,
        PRIMARY KEY (id),
        KEY invoice_id (invoice_id),
        KEY item_id (item_id),
        KEY store_id (store_id),
        FOREIGN KEY (item_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        // --- NEW: Customer-Store Pivot (ADDED) ---
    $customerStoreMapSql = "CREATE TABLE IF NOT EXISTS customer_stores_map (
        id INT(11) NOT NULL AUTO_INCREMENT,
        customer_id INT(11) NOT NULL,
        store_id INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY customer_store_unique (customer_id, store_id),
        KEY customer_id (customer_id),
        KEY store_id (store_id),
        CONSTRAINT customer_store_cust_fk FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE,
        CONSTRAINT customer_store_store_fk FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


    // --- NEW: Purchase Logs Table ---
    $purchaseLogsSql = "CREATE TABLE IF NOT EXISTS purchase_logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        sup_id INT(11) DEFAULT NULL,
        reference_no VARCHAR(50) NOT NULL,
        ref_invoice_id VARCHAR(50) NOT NULL,
        type VARCHAR(20) DEFAULT 'purchase',
        pmethod_id INT(11) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        amount DECIMAL(15,4) DEFAULT 0.0000,
        store_id INT(11) NOT NULL,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY sup_id (sup_id),
        KEY ref_invoice_id (ref_invoice_id),
        KEY pmethod_id (pmethod_id),
        FOREIGN KEY (sup_id) REFERENCES suppliers(id) ON DELETE SET NULL,
        FOREIGN KEY (pmethod_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Add these lines to your Execute Queries section:

// --- NEW: Purchase Images Table (For Multiple Files like PDF, Excel, Images) ---
$purchaseImageSql = "CREATE TABLE IF NOT EXISTS purchase_image (
    id INT(11) NOT NULL AUTO_INCREMENT,
    invoice_id VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY invoice_id (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";



    $userGroupSql = "CREATE TABLE IF NOT EXISTS user_groups (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        permission LONGTEXT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


// --- NEW: Users Table ---
$usersSql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    group_id INT(11) DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50) DEFAULT NULL,
    dob DATE DEFAULT NULL,
    sex ENUM('M', 'F', 'O') DEFAULT 'M',
    password VARCHAR(255) NOT NULL,
    pass_reset_code VARCHAR(255) DEFAULT NULL,
    reset_code_time DATETIME DEFAULT NULL,
    login_try INT(11) DEFAULT 0,
    last_login DATETIME DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    state VARCHAR(100) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    preference TEXT DEFAULT NULL,
    user_image VARCHAR(255) DEFAULT NULL,
    status TINYINT(1) DEFAULT 1 COMMENT '1=Active, 0=Inactive',
    sort_order INT(11) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (group_id) REFERENCES user_groups(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: POS Settings Table ---
    $posSettingsSql = "CREATE TABLE IF NOT EXISTS pos_settings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        store_id INT(11) DEFAULT NULL,
        setting_key VARCHAR(100) NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_setting (store_id, setting_key),
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// --- NEW: User-Store Pivot Table (For assigning users to specific stores) ---
$userStoreMapSql = "CREATE TABLE IF NOT EXISTS user_store_map (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    store_id INT(11) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY user_store_unique (user_id, store_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// --- NEW: Expense Category Table ---
    $expenseCategorySql = "CREATE TABLE IF NOT EXISTS expense_category (
        category_id INT(11) NOT NULL AUTO_INCREMENT,
        category_name VARCHAR(255) NOT NULL,
        category_slug VARCHAR(255) NOT NULL UNIQUE,
        parent_id INT(11) DEFAULT 0,
        category_details TEXT DEFAULT NULL,
        sell_return TINYINT(1) DEFAULT 0,
        sell_delete TINYINT(1) DEFAULT 0,
        loan_delete TINYINT(1) DEFAULT 0,
        loan_payment TINYINT(1) DEFAULT 0,
        giftcard_sell_delete TINYINT(1) DEFAULT 0,
        topup_delete TINYINT(1) DEFAULT 0,
        product_purchase TINYINT(1) DEFAULT 0,
        stock_transfer TINYINT(1) DEFAULT 0,
        due_paid TINYINT(1) DEFAULT 0,
        status TINYINT(1) DEFAULT 1,
        is_hide TINYINT(1) DEFAULT 0,
        sort_order INT(11) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Expenses Table ---
    $expensesSql = "CREATE TABLE IF NOT EXISTS expenses (
        id INT(11) NOT NULL AUTO_INCREMENT,
        store_id INT(11) NOT NULL,
        reference_no VARCHAR(50) DEFAULT NULL,
        category_id INT(11) NOT NULL,
        title VARCHAR(255) NOT NULL,
        amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        returnable TINYINT(1) DEFAULT 0,
        note TEXT DEFAULT NULL,
        attachment VARCHAR(255) DEFAULT NULL,
        status TINYINT(1) DEFAULT 1,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES expense_category(category_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";









    
    
    
    // Pivot tables (must run after core tables are created)




    // --- NEW: Selling Info Table (POS Sale Headers) ---
    $sellingInfoSql = "CREATE TABLE IF NOT EXISTS selling_info (
        info_id INT(11) NOT NULL AUTO_INCREMENT,
        invoice_id VARCHAR(50) NOT NULL UNIQUE,
        edit_counter INT(11) DEFAULT 0,
        inv_type VARCHAR(20) DEFAULT 'sale',
        store_id INT(11) NOT NULL,
        customer_id INT(11) DEFAULT NULL,
        customer_mobile VARCHAR(50) DEFAULT NULL,
        ref_invoice_id VARCHAR(50) DEFAULT NULL,
        ref_case_id VARCHAR(50) DEFAULT NULL,
        invoice_note TEXT DEFAULT NULL,
        total_items DECIMAL(15,2) DEFAULT 0.00,
        discount_amount DECIMAL(15,2) DEFAULT 0.00,
        tax_amount DECIMAL(15,2) DEFAULT 0.00,
        shipping_charge DECIMAL(15,2) DEFAULT 0.00,
        other_charge DECIMAL(15,2) DEFAULT 0.00,
        grand_total DECIMAL(15,2) DEFAULT 0.00,
        is_installment TINYINT(1) DEFAULT 0,
        status VARCHAR(20) DEFAULT 'active',
        purchased_id INT(11) DEFAULT NULL,
        payment_status VARCHAR(20) DEFAULT 'due',
        checkout_status VARCHAR(20) DEFAULT NULL,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (info_id),
        KEY store_id (store_id),
        KEY customer_id (customer_id),
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Selling Item Table (POS Sale Line Items) ---
    $sellingItemSql = "CREATE TABLE IF NOT EXISTS selling_item (
        id INT(11) NOT NULL AUTO_INCREMENT,
        invoice_id VARCHAR(50) NOT NULL,
        invoice_type VARCHAR(20) DEFAULT 'sale',
        store_id INT(11) NOT NULL,
        item_id INT(11) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        qty_sold DECIMAL(15,4) DEFAULT 0.0000,
        price_sold DECIMAL(15,4) DEFAULT 0.0000,
        subtotal DECIMAL(15,4) DEFAULT 0.0000,
        item_discount_percent DECIMAL(10,2) DEFAULT 0.00,
        discount_flat DECIMAL(15,4) DEFAULT 0.0000,
        tax_type VARCHAR(20) DEFAULT 'exclusive',
        tax_rate DECIMAL(10,2) DEFAULT 0.00,
        return_item DECIMAL(15,4) DEFAULT 0.0000,
        installment_quantity INT(11) DEFAULT 0,
        hold_status TINYINT(1) DEFAULT 0,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY invoice_id (invoice_id),
        KEY item_id (item_id),
        KEY store_id (store_id),
        FOREIGN KEY (item_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Sell Logs Table (Payment Transactions) ---
    $sellLogsSql = "CREATE TABLE IF NOT EXISTS sell_logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        customer_id INT(11) DEFAULT NULL,
        reference_no VARCHAR(50) NOT NULL,
        ref_invoice_id VARCHAR(50) NOT NULL,
        type VARCHAR(20) DEFAULT 'sale',
        pmethod_id INT(11) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        amount DECIMAL(15,4) DEFAULT 0.0000,
        store_id INT(11) NOT NULL,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY customer_id (customer_id),
        KEY ref_invoice_id (ref_invoice_id),
        KEY pmethod_id (pmethod_id),
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
        FOREIGN KEY (pmethod_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Installment Orders Table ---
    $installmentOrdersSql = "CREATE TABLE IF NOT EXISTS installment_orders (
        id INT(11) NOT NULL AUTO_INCREMENT,
        store_id INT(11) NOT NULL,
        invoice_id VARCHAR(50) NOT NULL,
        purchase_invoice_id VARCHAR(50) DEFAULT NULL,
        duration INT(11) DEFAULT 90,
        interval_count INT(11) DEFAULT 30,
        installment_count INT(11) DEFAULT 3,
        interest_percentage DECIMAL(10,2) DEFAULT 0.00,
        interest_amount DECIMAL(15,2) DEFAULT 0.00,
        initial_amount DECIMAL(15,2) DEFAULT 0.00,
        payment_status VARCHAR(20) DEFAULT 'due',
        last_installment_date DATETIME DEFAULT NULL,
        installment_end_date DATETIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY invoice_id (invoice_id),
        KEY store_id (store_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // --- NEW: Installment Payments Table ---
    $installmentPaymentsSql = "CREATE TABLE IF NOT EXISTS installment_payments (
        id INT(11) NOT NULL AUTO_INCREMENT,
        store_id INT(11) NOT NULL,
        invoice_id VARCHAR(50) NOT NULL,
        payment_date DATETIME DEFAULT NULL,
        pmethod_id INT(11) DEFAULT NULL,
        created_by INT(11) DEFAULT NULL,
        note TEXT DEFAULT NULL,
        capital DECIMAL(15,4) DEFAULT 0.0000,
        interest DECIMAL(15,4) DEFAULT 0.0000,
        payable DECIMAL(15,4) DEFAULT 0.0000,
        paid DECIMAL(15,4) DEFAULT 0.0000,
        due DECIMAL(15,4) DEFAULT 0.0000,
        payment_status VARCHAR(20) DEFAULT 'due',
        PRIMARY KEY (id),
        KEY invoice_id (invoice_id),
        KEY store_id (store_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";



    $holdingInfoSql = "CREATE TABLE IF NOT EXISTS holding_info (
        info_id INT(11) NOT NULL AUTO_INCREMENT,
        store_id INT(11) NOT NULL,
        order_title VARCHAR(255) DEFAULT NULL,
        ref_no VARCHAR(50) NOT NULL,
        customer_id INT(11) DEFAULT NULL,
        customer_mobile VARCHAR(50) DEFAULT NULL,
        invoice_note TEXT DEFAULT NULL,
        total_items INT(11) DEFAULT 0,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (info_id),
        UNIQUE KEY idx_unique_hold_info_ref (ref_no),
        KEY idx_hold_info_store (store_id),
        KEY idx_hold_info_customer (customer_id),
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $holdingItemSql = "CREATE TABLE IF NOT EXISTS holding_item (
        id INT(11) NOT NULL AUTO_INCREMENT,
        ref_no VARCHAR(50) NOT NULL,
        store_id INT(11) NOT NULL,
        item_id INT(11) NOT NULL,
        category_id INT(11) DEFAULT NULL,
        brand_id INT(11) DEFAULT NULL,
        sup_id INT(11) DEFAULT NULL,
        item_name VARCHAR(255) NOT NULL,
        item_price DECIMAL(15,2) DEFAULT 0.00,
        item_quantity DECIMAL(15,2) DEFAULT 0.00,
        item_total DECIMAL(15,2) DEFAULT 0.00,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_hold_item_ref (ref_no),
        KEY idx_hold_item_id (item_id),
        FOREIGN KEY (item_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $holdingPriceSql = "CREATE TABLE IF NOT EXISTS holding_price (
        price_id INT(11) NOT NULL AUTO_INCREMENT,
        ref_no VARCHAR(50) NOT NULL,
        store_id INT(11) NOT NULL,
        subtotal DECIMAL(15,2) DEFAULT 0.00,
        discount_type VARCHAR(20) DEFAULT 'plain',
        discount_amount DECIMAL(15,2) DEFAULT 0.00,
        item_tax DECIMAL(15,2) DEFAULT 0.00,
        order_tax DECIMAL(15,2) DEFAULT 0.00,
        cgst DECIMAL(15,2) DEFAULT 0.00,
        sgst DECIMAL(15,2) DEFAULT 0.00,
        igst DECIMAL(15,2) DEFAULT 0.00,
        shipping_type VARCHAR(20) DEFAULT 'plain',
        shipping_amount DECIMAL(15,2) DEFAULT 0.00,
        others_charge DECIMAL(15,2) DEFAULT 0.00,
        payable_amount DECIMAL(15,2) DEFAULT 0.00,
        PRIMARY KEY (price_id),
        UNIQUE KEY idx_unique_hold_price_ref (ref_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";



    // --- 3. Execute Queries in Dependency Order ---

    // Level 1: Independent Tables
    mysqli_query($conn, $currencySql);
    mysqli_query($conn, $userGroupSql);
    mysqli_query($conn, $paymentSql);
    mysqli_query($conn, $unitsSql);
    mysqli_query($conn, $brandsSql);
    mysqli_query($conn, $taxSql);
    mysqli_query($conn, $boxesSql);
    mysqli_query($conn, $categorySql);
    mysqli_query($conn, $suppliersSql);
    mysqli_query($conn, $customersSql);
    mysqli_query($conn, $printersSql);
    mysqli_query($conn, $expenseCategorySql);
    mysqli_query($conn, $expensesSql);
    mysqli_query($conn, $holdingPriceSql);


    // Level 2: Tables referencing Level 1
    mysqli_query($conn, $storesSql);
    mysqli_query($conn, $usersSql);
    mysqli_query($conn, $productsSql);
    mysqli_query($conn, $quotationsSql);
    mysqli_query($conn, $giftcardsSql);

    // Level 3: Tables referencing Level 1 & 2
    mysqli_query($conn, $purchaseInfoSql);
    mysqli_query($conn, $purchaseItemSql);
    mysqli_query($conn, $purchaseLogsSql);
    mysqli_query($conn, $purchaseImageSql);
    mysqli_query($conn, $sellingInfoSql);
    mysqli_query($conn, $sellingItemSql);
    mysqli_query($conn, $sellLogsSql);
    mysqli_query($conn, $holdingInfoSql);
    mysqli_query($conn, $holdingItemSql);
    mysqli_query($conn, $installmentOrdersSql);
    mysqli_query($conn, $installmentPaymentsSql);
    mysqli_query($conn, $giftcardTopupsSql);
    mysqli_query($conn, $productImagesSql);
    mysqli_query($conn, $posSettingsSql);

    // Level 4: Pivot / Map Tables
    mysqli_query($conn, $storeCurrencySql);
    mysqli_query($conn, $paymentStoreMapSql);
    mysqli_query($conn, $brandStoreSql);
    mysqli_query($conn, $taxStoreSql);
    mysqli_query($conn, $boxStoreSql);
    mysqli_query($conn, $categoryStoreSql);
    mysqli_query($conn, $supplierStoreSql);
    mysqli_query($conn, $productStoreSql);
    mysqli_query($conn, $quotationItemsSql);
    mysqli_query($conn, $customerStoreMapSql);
    mysqli_query($conn, $printerStoreMapSql);
    mysqli_query($conn, $userStoreMapSql);

    // Ensure is_installment column exists in sell_logs table
    $checkSellLogInstallment = @mysqli_query($conn, "SHOW COLUMNS FROM sell_logs LIKE 'is_installment'");
    if($checkSellLogInstallment && mysqli_num_rows($checkSellLogInstallment) == 0) {
        @mysqli_query($conn, "ALTER TABLE sell_logs ADD COLUMN is_installment TINYINT(1) DEFAULT 0 AFTER type");
    }
    $checkTransactionId = @mysqli_query($conn, "SHOW COLUMNS FROM sell_logs LIKE 'transaction_id'");
    if($checkTransactionId && mysqli_num_rows($checkTransactionId) == 0) {
        @mysqli_query($conn, "ALTER TABLE sell_logs ADD COLUMN transaction_id VARCHAR(255) DEFAULT NULL AFTER pmethod_id");
    }

    // Seeding
    $qG = "INSERT IGNORE INTO user_groups (name, slug) VALUES ('Admin', 'admin')";
    mysqli_query($conn, $qG);
    $check = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
    if(mysqli_num_rows($check) == 0) {
        $g = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM user_groups WHERE slug='admin'"));
        $gid = $g['id'];
        $p = password_hash('123456', PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO users (group_id, name, email, password) VALUES ('$gid', 'Admin', 'admin@store.com', '$p')");
    }


    



}

ensure_core_tables($conn);

// Ensure currency_id column exists in stores table (Added via Implementation Plan)
$checkStoreCurrency = @mysqli_query($conn, "SHOW COLUMNS FROM stores LIKE 'currency_id'");
if($checkStoreCurrency && mysqli_num_rows($checkStoreCurrency) == 0) {
    @mysqli_query($conn, "ALTER TABLE stores ADD CONSTRAINT fk_store_currency FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE SET NULL");
}

// Seed currencies if none exist
// $checkCurrencies = mysqli_query($conn, "SELECT id FROM currencies LIMIT 1");
// if($checkCurrencies && mysqli_num_rows($checkCurrencies) == 0) {
//     mysqli_query($conn, "INSERT INTO currencies (currency_name, code, symbol_left, status, sort_order) VALUES 
//         ('Bangladeshi Taka', 'BDT', '৳', 1, 1),
//         ('US Dollar', 'USD', '$', 1, 2),
//         ('Euro', 'EUR', '€', 1, 3),
//         ('British Pound', 'GBP', '£', 1, 4),
//         ('Indian Rupee', 'INR', '₹', 1, 5)
//     ");
// }

// --- Ensure UNSIGNED constraints for stock (Auto-fix for existing tables) ---
// This allows the user to simply reload the page to apply the DB updates.
$checkUnsignedProd = mysqli_query($conn, "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = 'products' AND COLUMN_NAME = 'opening_stock'");
if($res = mysqli_fetch_assoc($checkUnsignedProd)) {
    if(stripos($res['COLUMN_TYPE'], 'unsigned') === false) {
        mysqli_query($conn, "ALTER TABLE products MODIFY COLUMN opening_stock DECIMAL(15,2) UNSIGNED DEFAULT 0.00");
    }
}

$checkUnsignedMap = mysqli_query($conn, "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = 'product_store_map' AND COLUMN_NAME = 'stock'");
if($res = mysqli_fetch_assoc($checkUnsignedMap)) {
    if(stripos($res['COLUMN_TYPE'], 'unsigned') === false) {
         mysqli_query($conn, "ALTER TABLE product_store_map MODIFY COLUMN stock DECIMAL(15,4) UNSIGNED DEFAULT 0.0000");
    }
}

// Ensure per_customer_limit column exists in products table
$checkLimitCol = @mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'per_customer_limit'");
if($checkLimitCol && mysqli_num_rows($checkLimitCol) == 0) {
    @mysqli_query($conn, "ALTER TABLE products ADD COLUMN per_customer_limit INT(11) DEFAULT 0 AFTER alert_quantity");
}

// Ensure per_customer_limit column exists in product_store_map table
$checkMapLimitCol = @mysqli_query($conn, "SHOW COLUMNS FROM product_store_map LIKE 'per_customer_limit'");
if($checkMapLimitCol && mysqli_num_rows($checkMapLimitCol) == 0) {
    @mysqli_query($conn, "ALTER TABLE product_store_map ADD COLUMN per_customer_limit INT(11) DEFAULT 0 AFTER stock");
}
?>
