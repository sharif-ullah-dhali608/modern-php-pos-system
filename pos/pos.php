<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Get current user and store info
$user_id = $_SESSION['auth_user']['id'] ?? 1;
$user_name = $_SESSION['auth_user']['name'] ?? 'Admin';

// Load Shared Modal Styles
echo '<link rel="stylesheet" href="/pos/assets/css/payment_modal.css">';



// Fetch active stores
$stores_query = "SELECT * FROM stores WHERE status=1 ORDER BY store_name ASC";
$stores_result = mysqli_query($conn, $stores_query);
$stores = [];
while($store = mysqli_fetch_assoc($stores_result)) {
    $stores[] = $store;
}
$current_store = $stores[0] ?? ['id' => 1, 'store_name' => 'Default Store'];

// Pagination settings
$items_per_page = 20;
$page_no = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page_no - 1) * $items_per_page;

// Get total product count
$count_query = "SELECT COUNT(*) as total FROM products WHERE status = 1";
$count_result = mysqli_query($conn, $count_query);
$total_products = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_products / $items_per_page);

// Fetch products for display
$products_query = "SELECT p.*, c.name as category_name, u.unit_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   LEFT JOIN units u ON p.unit_id = u.id 
                   WHERE p.status = 1 
                   ORDER BY p.product_name ASC 
                   LIMIT $items_per_page OFFSET $offset";
$products_result = mysqli_query($conn, $products_query);

// Fetch categories for filter
$categories_query = "SELECT id, name FROM categories WHERE status=1 ORDER BY name ASC";
$categories_result = mysqli_query($conn, $categories_query);

// Fetch customers with credit and giftcard info
$customers_query = "SELECT c.id, c.name, c.mobile, c.current_due as credit, c.opening_balance,
                    COALESCE(SUM(g.balance), 0) as giftcard_balance
                    FROM customers c
                    LEFT JOIN giftcards g ON c.id = g.customer_id AND g.status = 1
                    WHERE c.status=1 
                    GROUP BY c.id, c.name, c.mobile, c.current_due, c.opening_balance
                    ORDER BY c.name ASC";
$customers_result = mysqli_query($conn, $customers_query);

// Fetch payment methods
$payment_methods_query = "SELECT id, name, code FROM payment_methods WHERE status=1 ORDER BY sort_order ASC";
$payment_methods_result = mysqli_query($conn, $payment_methods_query);

// Fetch additional data for Quick Add Product Modal
$brands = mysqli_query($conn, "SELECT id, name FROM brands WHERE status='1' ORDER BY name ASC");
$units = mysqli_query($conn, "SELECT id, unit_name as name, code as short_name FROM units WHERE status='1'");
$taxes = mysqli_query($conn, "SELECT id, name, taxrate as rate FROM taxrates WHERE status='1'");
$boxes = mysqli_query($conn, "SELECT id, box_name as name FROM boxes WHERE status='1'");
$currencies = mysqli_query($conn, "SELECT id, currency_name as name, code FROM currencies WHERE status='1'");
$suppliers = mysqli_query($conn, "SELECT id, name FROM suppliers WHERE status='1' ORDER BY name ASC");

// Initialize cart session
if(!isset($_SESSION['pos_cart'])) {
    $_SESSION['pos_cart'] = [];
}

$page_title = "POS - Velocity POS";
include('../includes/header.php');
?>

<style>
/* Force sidebar to stay collapsed on POS page */
#sidebar {
    width: 80px !important;
}

#sidebar .link-text,
#sidebar .logo-text-container,
#sidebar .fa-chevron-right,
#sidebar .submenu {
    display: none !important;
}

#sidebar-toggle-container {
    display: none !important;
}

#main-content {
    margin-left: 80px !important;
}

/* POS Page Specific Styles */
.pos-container {
    display: flex;
    flex: 1;
    overflow: hidden;
    background: #f1f5f9;
    /* height: 100vh; Removed to fix layout overflow */
    height: 100%;
}

.pos-left {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-width: 0;
}

.pos-right {
    width: 440px;
    background: #fff;
    border-left: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    overflow-x: hidden;
}

/* POS Header Bar - Full Width Navbar */
.pos-header-bar {
    background: linear-gradient(135deg, #0d9488 0%, #047857 100%);
    color: white;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    min-height: 48px;
    flex-shrink: 0;
    width: 100%;
}

.pos-header-bar::-webkit-scrollbar {
    height: 0;
}

.pos-header-bar select {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 6px 10px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
}

.pos-header-bar select option {
    color: #1e293b;
}

.pos-header-bar .datetime {
    background: rgba(255,255,255,0.1);
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
}

.pos-header-bar .nav-links {
    display: flex;
    gap: 4px;
}

.pos-header-bar .nav-links a {
    color: white;
    padding: 6px 8px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.pos-header-bar .nav-links a:hover {
    background: rgba(255,255,255,0.2);
}

.pos-header-bar .nav-links a.active {
    background: rgba(255,255,255,0.25);
}

/* Fullscreen Button */
.fullscreen-btn {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.2s;
    margin-left: auto;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fullscreen-btn:hover {
    background: rgba(255,255,255,0.25);
}

.held-order-item:hover {
    background: #f1f5f9;
}

.held-order-item .btn-delete-held:hover {
    color: #ef4444 !important;
}

/* Search Bar - Height matches Customer Section */
.search-bar {
    padding: 8px 12px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    min-height: 56px;
    display: flex;
    align-items: center;
}

.search-bar input {
    width: 100%;
    padding: 10px 14px 10px 40px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    transition: all 0.2s;
    background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2394a3b8' viewBox='0 0 24 24'%3E%3Cpath d='M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z'/%3E%3C/svg%3E") no-repeat 12px center;
    background-size: 18px;
}

.search-bar input:focus {
    outline: none;
    border-color: #0d9488;
    background-color: #fff;
}

/* Category Filter - Height matches Cart Header */
.category-filter {
    padding: 4px 6px;
    background: #fff;
    display: flex;
    gap: 6px;
    overflow-x: auto;
    border-bottom: 1px solid #e2e8f0;
    min-height: 42px;
    flex-shrink: 0;
    align-items: center;
}

.category-filter::-webkit-scrollbar {
    height: 4px;
}

.category-filter::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.category-filter button {
    padding: 6px 14px;
    border: none;
    border-radius: 16px;
    background: #f1f5f9;
    color: #64748b;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}

.category-filter button:hover,
.category-filter button.active {
    background: #0d9488;
    color: white;
}

/* Product Grid - Bigger Cards with More Spacing */
.product-grid-wrapper {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
}

.product-grid {
    flex: 1;
    min-height: 0;
    padding: 12px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    align-content: start;
    overflow-y: auto;
    overflow-x: hidden;
    max-width: 100%;
    box-sizing: border-box;
}

.product-card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
    display: flex;
    flex-direction: column;
    height: 260px; /* Fixed height for consistent card size */
    position: relative;
    box-sizing: border-box;
    min-width: 0;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    border-color: #0d9488;
}


.product-card img {
    width: 100%;
    height: 140px;
    object-fit: cover;
    background: #f8fafc;
    flex-shrink: 0;
}

.product-card .info {
    padding: 10px;
    flex: 1;
    min-width: 0; /* Important for text truncation */
}

.product-card .name {
    font-weight: 600;
    font-size: 13px;
    color: #1e293b;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-card .price {
    color: #0d9488;
    font-weight: 700;
    font-size: 14px;
}

.product-card .stock {
    font-size: 10px;
    color: #64748b;
    margin-top: 2px;
}

.product-card .add-btn {
    width: 100%;
    padding: 10px 8px;
    background: linear-gradient(135deg, #0d9488 0%, #047857 100%);
    color: white;
    border: none;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    flex-shrink: 0;
}

.product-card .add-btn:hover {
    opacity: 0.9;
}

/* Stock Badges */
.product-card {
    position: relative;
}

.stock-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    z-index: 10;
}

.stock-badge.out-of-stock {
    background: #dc2626;
    color: white;
}

.stock-badge.low-stock {
    background: #f59e0b;
    color: white;
}

.out-of-stock-card {
    opacity: 0.7;
}

.out-of-stock-card img {
    filter: grayscale(50%);
}

.out-of-stock-btn {
    background: #94a3b8 !important;
    cursor: not-allowed !important;
}

.out-of-stock-btn:hover {
    opacity: 1 !important;
}

/* Pagination - Height matches Footer */
.pagination-wrapper {
    padding: 8px 12px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: center;
    gap: 6px;
    min-height: 50px;
    align-items: center;
}

.pagination-wrapper button {
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 11px;
    transition: all 0.2s;
}

.pagination-wrapper button:hover,
.pagination-wrapper button.active {
    background: #0d9488;
    color: white;
    border-color: #0d9488;
}

.pagination-wrapper button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Right Panel Styles */
/* Customer Section - Height matches Search Bar */
.customer-section {
    padding: 3px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    min-height: 45px;
    display: flex;
    align-items: center;    
}

.customer-box {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 6px 4px;
    background: #fff;
    border-radius: 10px;
    border: 2px solid #10b981;
    width: 100%;
}

.customer-box .avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.customer-box .details {
    flex: 1;
    cursor: pointer;
}

.customer-box .name {
    font-weight: 600;
    color: #1e293b;
    font-size: 13px;
}

.customer-box .phone {
    color: #64748b;
    font-size: 12px;
}

/* Cart Section */
.cart-section {
    flex: 1;
    overflow-y: auto;
}

/* Cart Header - Height matches Category Filter */
.cart-header {
    display: grid;
    grid-template-columns: 50px 1fr 90px 100px;
    gap: 6px;
    padding: 8px 10px;
    background: #f8fafc;
    font-weight: 700;
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 10;
    overflow: hidden;
    min-height: 42px;
    align-items: center;
}

.cart-header span:nth-child(3) {
    text-align: right;
    padding-right: 15px; /* Match price padding */
}

.cart-header span:nth-child(4) {
    text-align: right;
    padding-right: 32px; /* Offset for trash icon space */
}

.cart-item {
    display: grid;
    grid-template-columns: 50px 1fr 90px 100px;
    gap: 6px;
    padding: 8px 10px;
    border-bottom: 1px solid #f1f5f9;
    align-items: center;
    font-size: 12px;
    overflow: hidden;
}

.cart-item:hover {
    background: #f8fafc;
}

.cart-item .qty-control {
    display: flex;
    align-items: center;
    gap: 2px;
}

.cart-item .qty-control button {
    width: 22px;
    height: 22px;
    border: none;
    border-radius: 4px;
    background: #e2e8f0;
    color: #475569;
    cursor: pointer;
    font-weight: 700;
    font-size: 12px;
}

.cart-item .qty-control button:hover {
    background: #0d9488;
    color: white;
}

.cart-item .qty-control input {
    width: 28px;
    text-align: center;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    padding: 2px;
    font-weight: 700;
    font-size: 12px;
    -moz-appearance: textfield;
}

.cart-item .qty-control input::-webkit-outer-spin-button,
.cart-item .qty-control input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.cart-item .product-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cart-item .price {
    font-weight: 700;
    color: #64748b;
    font-size: 13px;
    text-align: left;
    padding-right: 15px; /* Space between Price and Subtotal column */\
    gap: 20px;

}

.cart-item .subtotal {
    font-weight: 800;
    color: #0d9488;
    font-size: 13px;
    text-align: right;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
}

.cart-item .remove-btn {
    color: #ef4444;
    cursor: pointer;
    font-size: 14px;
}

/* Totals Section - Compact */
.totals-section {
    padding: 8px 10px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.totals-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 3px 0;
    font-size: 11px;
}

.totals-row.total {
    font-size: 12px;
    font-weight: 700;
    color: #1e293b;
    border-top: 2px solid #e2e8f0;
    margin-top: 6px;
    padding-top: 6px;
}

.totals-row input {
    width: 60px;
    text-align: right;
    border: 1px solid #e2e8f0;
    border-radius: 5px;
    padding: 3px 6px;
    font-weight: 600;
    font-size: 11px;
    background: #fff;
    color: #1e293b;
    -moz-appearance: textfield;
}

/* Hide spin buttons Chrome/Safari/Edge */
.totals-row input::-webkit-outer-spin-button,
.totals-row input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.totals-row.split-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    align-items: start;
}

.totals-row .input-group {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
}

.totals-row label {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.totals-row input {
    width: 60px;
    text-align: right;
    border: 1px solid #e2e8f0;
    border-radius: 5px;
    padding: 4px 6px;
    font-weight: 600;
    font-size: 11px;
    background: #fff;
    color: #1e293b;
    -moz-appearance: textfield; 
    transition: border-color 0.2s;
}

.totals-row input:focus {
    border-color: #0d9488;
    outline: none;
}

/* POS Footer - Compact Version */
.pos-footer {
    padding: 6px 4px;
    background: #0d9488;
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex-shrink: 0;
    border-top: 1px solid #115e59;
}

.pos-footer .total-display {
    text-align: center;
    color: white;
    margin-bottom: 2px;
}

.pos-footer .total-display .label {
    font-size: 10px;
    opacity: 0.6;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.pos-footer .total-display .amount {
    font-size: 22px;
    font-weight: 800;
    line-height: 1;
    color: #fff;
}

.pos-footer .footer-controls {
    display: grid;
    grid-template-columns: 1fr 0.8fr 1.2fr;
    gap: 8px;
}

.pos-footer .date-input {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 8px 10px;
    border-radius: 6px;
    font-size: 12px;
    width: 100%;
    margin-left: 0;
}

.pos-footer .date-input::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
    cursor: pointer;
}

.pos-footer .pay-btn {
    padding: 4px 6px;
    background: #fff;
    color: #0d9488;
    border: none;
    border-radius: 6px;
    font-weight: 700;
    font-size: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
    width: 100%;
}

.pos-footer .pay-btn:hover {
    background: #0d9488;
    color: white;
}

.pos-footer .hold-btn {
    padding: 4px 6px;
    background: #475569;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 700;
    font-size: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
}

.pos-footer .hold-btn:hover {
    background: #334155;
}

/* Modal Styles - Matching App Design */
.pos-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1050;
    align-items: flex-start;
    justify-content: center;
    padding: 40px 20px;
    overflow-y: auto;
}

.pos-modal.active {
    display: flex;
}

.pos-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 600px;
    width: 100%;
    max-height: calc(100vh - 80px);
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    position: relative;
}

.pos-modal-header {
    background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
}

.pos-modal-header h3 {
    font-size: 16px;
    font-weight: 700;
    color: white;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.pos-modal-header h3 i {
    color: white !important;
}

.pos-modal-header .close-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%; /* Kept user's preference for circle */
    cursor: pointer;
    font-size: 24px; /* Increased slightly for better visual balance */
    line-height: 1; /* Critical for vertical centering */
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0; /* Reset padding to avoid offset */
    transition: all 0.2s;
}

.pos-modal-header .close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: none;
}

.pos-modal-body {
    padding: 20px;
}

/* Payment Modal */
.payment-methods {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}

.payment-method {
    padding: 16px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.payment-method:hover {
    border-color: #0d9488;
    background: #f0fdfa;
}

.payment-method.selected {
    border-color: #0d9488;
    background: #ccfbf1;
}

.payment-method i {
    font-size: 24px;
    color: #0d9488;
    margin-bottom: 6px;
}

.payment-method span {
    display: block;
    font-weight: 600;
    font-size: 12px;
    color: #1e293b;
}

.payment-summary {
    background: #f8fafc;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 20px;
}

.payment-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 14px;
}

.payment-row.grand-total {
    font-size: 20px;
    font-weight: 800;
    color: #0d9488;
    border-top: 2px solid #e2e8f0;
    margin-top: 10px;
    padding-top: 14px;
}

.payment-input-group {
    margin-bottom: 14px;
}

.payment-input-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #475569;
    font-size: 13px;
}

.payment-input-group input,
.payment-input-group select,
.payment-input-group textarea {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
}

.payment-input-group input:focus,
.payment-input-group select:focus {
    outline: none;
    border-color: #0d9488;
}

.change-display {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 16px;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 20px;
}

.change-display .label {
    font-size: 12px;
    opacity: 0.8;
}

.change-display .amount {
    font-size: 28px;
    font-weight: 800;
}

.complete-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}

.complete-btn:hover {
    transform: scale(1.01);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
}

/* Empty cart state */
.empty-cart {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
}

.empty-cart i {
    font-size: 40px;
    margin-bottom: 12px;
}

.empty-cart p {
    font-size: 13px;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive Design */

/* Extra Large Screens */
@media (min-width: 1600px) {
    .product-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

/* Large Screens */
@media (max-width: 1400px) {
    .pos-right {
        width: 440px;
    }
    .product-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 1200px) {
    .pos-right {
        width: 340px;
    }
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .pos-header-bar .nav-links a {
        padding: 5px 8px;
        font-size: 11px;
    }
    .fullscreen-btn {
        padding: 6px 10px;
        font-size: 14px;
    }
}

@media (max-width: 1024px) {
    .pos-container {
        flex-direction: column;
    }
    
    .pos-right {
        width: 100%;
        height: 50vh;
        border-left: none;
        border-top: 1px solid #e2e8f0;
    }
    
    .pos-left {
        height: 50vh;
    }
    
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        padding: 12px;
    }
    
    .product-card {
        height: 240px;
    }
    
    .product-card img {
        height: 120px;
    }
    
    .product-card .info {
        padding: 8px;
    }
    
    .product-card .name {
        font-size: 12px;
    }
    
    .product-card .price {
        font-size: 13px;
    }
    
    .cart-header,
    .cart-item {
        grid-template-columns: 45px 1fr 80px 100px;
        gap: 6px;
        font-size: 11px;
        padding: 8px 10px;
    }
    
    .cart-item .qty-input {
        width: 35px;
        font-size: 12px;
        padding: 4px 2px;
    }
    
    .totals-section {
        padding: 10px;
    }
    
    .totals-row {
        font-size: 11px;
    }
    
    .totals-row input {
        width: 60px;
        font-size: 11px;
        padding: 3px 6px;
    }
    
    .pos-footer {
        padding: 10px;
        gap: 8px;
    }
    
    .pos-footer .total-display .amount {
        font-size: 8px;
    }
    
    .pos-footer .pay-btn,
    .pos-footer .hold-btn {
        padding: 4px 6px;
        font-size: 10px;
    }
    
    .pos-footer .date-input {
        padding: 4px 6px;
        font-size: 8px;
    }
}

@media (max-width: 768px) {
    /* Main Container - Side by side on tablet */
    .pos-container {
        flex-direction: row;
        height: calc(100vh - 100px);
        overflow: hidden;
    }

    /* Hide sidebar on mobile */
    #sidebar {
        display: none !important;
    }
    #main-content {
        margin-left: 0 !important;
    }
    
    .pos-header-bar {
        padding: 6px 8px;
        gap: 4px;
        flex-wrap: wrap;
    }
    
    .pos-header-bar select {
        min-width: 100px;
        font-size: 10px;
        padding: 4px 6px;
    }
    
    .pos-header-bar .nav-links {
        flex-wrap: wrap;
        gap: 3px;
    }
    
    .pos-header-bar .nav-links a {
        padding: 4px 6px;
        font-size: 9px;
    }
    
    .fullscreen-btn {
        padding: 4px 6px;
        font-size: 10px;
    }

    /* Left Panel - Takes 50% */
    .pos-left {
        width: 50%;
        height: 100%;
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    /* Right Panel - Takes 50% */
    .pos-right {
        width: 50%;
        height: 100%;
        border-left: 1px solid #e2e8f0;
        border-top: none;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .product-grid-wrapper {
        flex: 1;
        overflow-y: auto;
    }
    
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        padding: 8px;
    }
    
    .product-card {
        height: 180px;
    }
    
    .product-card img {
        height: 80px;
    }
    
    .product-card .info {
        padding: 6px;
    }
    
    .product-card .name {
        font-size: 10px;
    }
    
    .product-card .price {
        font-size: 11px;
    }
    
    .product-card .add-btn {
        padding: 6px;
        font-size: 10px;
    }

    .cart-section {
        flex: 1;
        overflow-y: auto;
    }
    
    .cart-header,
    .cart-item {
        grid-template-columns: 35px 1fr 60px 70px;
        font-size: 9px;
        padding: 4px 6px;
        gap: 4px;
    }
    
    .customer-section {
        padding: 3px;
        min-height: 40px;
    }
    
    .customer-box {
        padding: 4px 6px;
        gap: 4px;
    }
    
    .customer-box .avatar {
        width: 26px;
        height: 26px;
        font-size: 12px;
    }
    
    .customer-box .name {
        font-size: 11px;
    }
    
    .customer-box .phone {
        font-size: 9px;
    }
    
    .totals-section {
        padding: 6px 8px;
    }
    
    .totals-row {
        font-size: 9px;
        padding: 2px 0;
    }
    
    .totals-row label {
        font-size: 8px;
    }
    
    .totals-row input {
        width: 50px;
        padding: 2px 4px;
        font-size: 9px;
    }
    
    .pos-footer {
        padding: 4px 6px;
        gap: 4px;
    }
    
    .pos-footer .total-display .label {
        font-size: 8px;
    }
    
    .pos-footer .total-display .amount {
        font-size: 16px;
    }
    
    .pos-footer .footer-controls {
        gap: 4px;
    }
    
    .pos-footer .date-input {
        padding: 4px 6px;
        font-size: 9px;
    }
    
    .pos-footer .pay-btn,
    .pos-footer .hold-btn {
        padding: 4px 6px;
        font-size: 9px;
    }
}

@media (max-width: 480px) {
    /* Header bar - 3 column grid */
    .pos-header-bar {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr);
        gap: 4px;
        padding: 6px 8px;
    }
    
    /* Row 1: Store | POS | Fullscreen */
    .pos-header-bar select {
        grid-column: 1;
        grid-row: 1;
        font-size: 10px;
        padding: 6px 8px;
    }
    
    .fullscreen-btn {
        grid-column: 3;
        grid-row: 1;
        position: static;
        padding: 6px 8px;
        font-size: 12px;
        margin: 0;
        order: 0;
    }
    
    /* Nav links - fills remaining grid */
    .pos-header-bar .nav-links {
        grid-column: 1 / -1;
        grid-row: 2;
        display: contents !important;
    }
    
    /* First nav link (POS) goes to row 1, column 2 */
    .pos-header-bar .nav-links a:first-child {
        grid-column: 2;
        grid-row: 1;
    }
    
    /* All other nav links in 3-column grid from row 2 */
    .pos-header-bar .nav-links a {
        padding: 6px 4px;
        font-size: 9px;
        text-align: center;
        justify-content: center;
        border-radius: 6px;
    }
    
    .pos-header-bar .nav-links a i {
        font-size: 10px;
    }
    
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        padding: 8px;
    }
    
    .product-card {
        height: 200px;
    }
    
    .product-card img {
        height: 80px;
    }
    
    .product-card .info {
        padding: 6px;
    }
    
    .product-card .name {
        font-size: 11px;
    }
    
    .product-card .price {
        font-size: 12px;
    }
    
    .product-card .add-btn {
        padding: 6px;
        font-size: 10px;
    }
    
    /* Cart section - horizontal scroll */
    .cart-section {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 440px;
    }
    
    .cart-header {
        display: grid;
        grid-template-columns: 50px 120px 65px 80px;
        min-width: 420px;
        font-size: 4px;
        padding: 4px 6px;
        gap: 4px;
        white-space: nowrap;
    }
    
    .cart-item {
        display: grid;
        grid-template-columns: 75px 120px 60px 75px;
        min-width: 420px;
        font-size: 9px;
        padding: 8px 10px;
        gap: 12px;
        align-items: center;
    }
    
    .cart-item .qty-control {
        display: flex;
        align-items: center;
        gap: 2px;
    }
    
    .cart-item .qty-control button {
        width: 16px;
        height: 16px;
        font-size: 9px;
        padding: 0;
    }
    
    .cart-item .qty-input {
        width: 20px;
        font-size: 9px;
        padding: 1px;
        text-align: center;
    }
    
    .cart-item .product-name {
        white-space: nowrap;
        overflow: visible;
        font-weight: 500;
    }
    
    .cart-item .price {
        text-align: right;
        white-space: nowrap;
    }
    
    .cart-item .subtotal {
        text-align: right;
        white-space: nowrap;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
    }
    
    .cart-item .remove-btn {
        display: inline-flex !important;
        color: #ef4444;
        font-size: 11px;
        cursor: pointer;
    }
    
    .pos-footer .total-display .amount {
        font-size: 18px;
    }
    
    .pos-footer .pay-btn,
    .pos-footer .hold-btn {
        padding: 8px 10px;
        font-size: 11px;
    }
    
    .pos-footer .date-input {
        padding: 5px 8px;
        font-size: 10px;
    }
    
    /* Pagination - 1 line, icons only */
    .pagination-wrapper {
        padding: 4px 6px;
        gap: 4px;
        min-height: 32px;
        flex-wrap: nowrap;
    }
    
    .pagination-wrapper button {
        padding: 4px 8px;
        font-size: 11px;
        min-width: auto;
    }
    
    .pagination-wrapper .pagination-text {
        display: none !important;
    }
    
    .pagination-wrapper .page-label {
        display: none;
    }
    
    .pagination-wrapper .pagination-info {
        padding: 4px 8px !important;
        font-size: 10px;
        white-space: nowrap;
    }
    
    /* Modal responsive for mobile */
    .pos-modal {
        padding: 10px;
        align-items: flex-start;
    }
    
    .pos-modal-content {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0;
        border-radius: 12px;
    }
    
    .pos-modal-header {
        padding: 12px 15px;
    }
    
    .pos-modal-header h3 {
        font-size: 14px;
    }
    
    .pos-modal-body {
        padding: 12px 15px;
    }
    
    /* Force ALL grids to single column on mobile */
    .pos-modal .form-grid,
    .pos-modal .grid-2,
    .pos-modal .grid-3,
    .pos-modal .form-row,
    .pos-modal [style*="grid-template-columns"],
    .pos-modal-body > div[style*="display: grid"],
    .pos-modal-body .form-section > div {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
    
    /* Card Information section */
    .pos-modal .card-info-grid,
    .pos-modal .form-section .grid {
        grid-template-columns: 1fr !important;
    }
    
    /* Make inline flex rows stack */
    .pos-modal [style*="display: flex"],
    .pos-modal .flex-row {
        flex-direction: column !important;
        gap: 10px !important;
    }
    
    .pos-modal .form-group {
        width: 100% !important;
        min-width: 100% !important;
    }
    
    .pos-modal .form-group label {
        font-size: 11px;
    }
    
    .pos-modal .form-group input,
    .pos-modal .form-group select,
    .pos-modal .form-group textarea {
        padding: 8px 10px;
        font-size: 12px;
        width: 100% !important;
    }
    
    /* Status and preview side by side - stack them */
    .pos-modal .giftcard-status-preview,
    .pos-modal .card-logo-status {
        flex-direction: column !important;
        gap: 15px !important;
    }
    
    .pos-modal .giftcard-preview {
        transform: scale(0.85);
        transform-origin: top center;
    }
    
    .pos-modal .status-card {
        padding: 10px;
        width: 100% !important;
    }
    
    .pos-modal .status-card h4 {
        font-size: 12px;
    }
    
    /* Giftcard preview barcode fix */
    .pos-modal .giftcard-preview-card,
    .pos-modal .giftcard-card-preview {
        width: 100% !important;
        max-width: 280px;
        margin: 0 auto;
        overflow: hidden;
    }
    
    /* Barcode container fix */
    .pos-modal #modal-barcode-container {
        max-width: 100% !important;
        width: 100% !important;
        height: 50px !important;
    }
    
    /* Barcode parent container - the white box */
    .pos-modal div[style*="width: 60%"] {
        width: 90% !important;
    }
    
    .pos-modal .giftcard-preview-card svg,
    .pos-modal .giftcard-card-preview svg,
    .pos-modal #barcode-preview,
    .pos-modal .barcode-container,
    .pos-modal .barcode-container svg,
    .pos-modal svg[id*="barcode"] {
        max-width: 100% !important;
        width: 100% !important;
        height: auto !important;
        overflow: hidden;
    }
    
    .pos-modal .giftcard-preview-card .barcode,
    .pos-modal .barcode-wrapper {
        max-width: 100%;
        overflow: hidden;
    }
    
    /* Make the giftcard preview card smaller */
    .pos-modal div[style*="bg-gradient-to-b"],
    .pos-modal .preview-card-container {
        transform: scale(0.9);
        transform-origin: top center;
    }
    
    /* Payment Modal - full width on mobile */
    #paymentModal .pos-modal-content {
        max-width: 100% !important;
    }
    
    #paymentModal .pos-modal-body > div[style*="grid"] {
        display: block !important;
        grid-template-columns: 1fr !important;
    }
    
    /* Payment methods sidebar - 4 per row */
    .payment-methods-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 6px !important;
    }
    
    .sidebar-payment-method {
        padding: 8px 4px !important;
        flex-direction: column !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        font-size: 8px !important;
        gap: 3px !important;
        min-width: auto !important;
        height: auto !important;
    }
    
    /* Selected Payment Method Style - Mobile */
    .sidebar-payment-method.selected {
        background: #10b981 !important;
        border-color: #10b981 !important;
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2) !important;
    }
    
    .sidebar-payment-method.selected i,
    .sidebar-payment-method.selected span,
    .sidebar-payment-method.selected div {
        color: white !important;
    }

    .sidebar-payment-method i {
        font-size: 16px !important;
        width: auto !important;
    }
    
    .sidebar-payment-method span,
    .sidebar-payment-method div {
        font-size: 7px !important;
        line-height: 1.1 !important;
        word-break: break-word !important;
    }
    
    .sidebar-payment-method > div[style*="flex: 1"] {
        flex: none !important;
    }
    
    /* Payment Modal Header - Fix for mobile */
    #paymentModal .pos-modal-header {
        padding: 10px 12px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px;
    }
    
    #paymentModal .pos-modal-header h3 {
        font-size: 11px !important;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
        margin: 0;
        flex: 1;
    }
    
    #paymentModal .pos-modal-header h3 i {
        font-size: 14px;
    }
    
    #paymentModal .pos-modal-header span {
        font-size: 10px;
    }
    
    #paymentModal .pos-modal-header .close-btn {
        width: 32px;
        height: 32px;
        min-width: 32px;
        padding: 0 !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 9999 !important;
        cursor: pointer !important;
        background: rgba(255,255,255,0.2) !important;
        border-radius: 50% !important;
        border: none !important;
    }
    
    #paymentModal .pos-modal-header .close-btn:active {
        transform: scale(0.95);
    }
    
    /* Payment Modal - Vertical scroll */
    #paymentModal {
        overflow-y: auto !important;
        padding: 10px !important;
    }
    
    #paymentModal .pos-modal-content {
        max-height: 90vh !important;
        overflow-y: auto !important;
        margin: auto;
    }
    
    #paymentModal .pos-modal-body {
        max-height: calc(90vh - 60px) !important;
        overflow-y: auto !important;
    }
    
    /* Payment summary - 2 columns grid */
    /* Target the summary container (div with background: #f8fafc) */
    #paymentModal div[style*="background: #f8fafc"][style*="padding: 15px"] {
        display: flex !important;
        flex-wrap: wrap !important;
        padding: 8px !important;
        gap: 0 !important;
    }
    
    /* Each summary row - 2 per row */
    #paymentModal div[style*="background: #f8fafc"][style*="padding: 15px"] > div[style*="display: flex"][style*="margin-bottom: 8px"] {
        width: 48% !important;
        margin: 1% !important;
        padding: 6px 8px !important;
        background: white !important;
        border-radius: 6px !important;
        border: 1px solid #e2e8f0 !important;
        font-size: 10px !important;
        box-sizing: border-box;
    }
    
    /* Payable row - full width */
    /* Previous Due row (7th child) - full width */
    #paymentModal div[style*="background: #f8fafc"][style*="padding: 15px"] > div[style*="margin-bottom: 12px"][style*="border-bottom"] {
        width: 100% !important;
        background: #fff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 6px !important;
        padding: 8px !important;
        margin-bottom: 8px !important;
    }
    
    /* Payable Amount row - full width with green highlight */
    #paymentModal div[style*="background: #f8fafc"][style*="padding: 15px"] > div:has(span[style*="font-weight: 600"]):not(:has(span[style*="color: #10b981"])):not(:has(span[style*="color: #ef4444"])) {
        width: 100% !important;
        background: linear-gradient(135deg, #ecfdf5, #d1fae5) !important;
        border: 1px solid #34d399 !important;
        border-radius: 8px !important;
        padding: 10px !important;
        margin: 4px 0 !important;
    }
    
    /* Paid Amount row - full width */  
    #paymentModal div[style*="background: #f8fafc"][style*="padding: 15px"] > div:has(span[style*="color: #10b981"]) {
        width: 48% !important;
        background: #f0fdf4 !important;
        border: 1px solid #86efac !important;
    }
    
    /* Due Amount row - full width */
    #paymentModal div[style*="background: #f8fafc"][style*="padding: 15px"] > div:has(span[style*="color: #ef4444"]) {
        width: 48% !important;
        background: #fef2f2 !important;
        border: 1px solid #fca5a5 !important;
    }
    
    /* Balance row - full width */
    #paymentModal div[style*="background: #f8fafc"][style*="padding: 15px"] > div[style*="border-top: 2px"] {
        width: 100% !important;
        margin-top: 8px !important;
        padding-top: 10px !important;
        background: #f1f5f9 !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        padding: 8px !important;
    }
    
    
    /* Labels */
    #paymentModal .pos-modal-body > div > div:last-child span:first-child {
        font-size: 9px !important;
        color: #64748b;
    }
    
    /* Values */
    #paymentModal .pos-modal-body > div > div:last-child span:last-child {
        font-size: 11px !important;
        font-weight: 600;
    }
    
    /* Payment type buttons - SAME ROW */
    #paymentModal div[style*="display: flex"][style*="gap: 10px"],
    #paymentModal div[style*="display: flex"][style*="gap: 12px"] {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        gap: 8px !important;
        width: 100% !important;
    }
    
    #paymentModal div[style*="display: flex"][style*="gap: 10px"] > button,
    #paymentModal div[style*="display: flex"][style*="gap: 12px"] > button,
    #paymentModal div[style*="display: flex"][style*="gap: 10px"] > label,
    #paymentModal div[style*="display: flex"][style*="gap: 12px"] > label {
        flex: 1 !important;
        min-width: 0 !important;
        padding: 10px 6px !important;
        font-size: 9px !important;
        white-space: nowrap;
        border-radius: 8px !important;
    }
    
    /* Input section */
    #paymentModal div[style*="display: grid"][style*="gap: 15px"] {
        display: block !important;
    }
    
    #paymentModal div[style*="display: grid"][style*="gap: 15px"] > div {
        margin-bottom: 10px;
    }
    
    /* ============================================
       UNIQUE PAYMENT MODAL DESIGN - MOBILE
       ============================================ */
    
    /* Modal container - glassmorphism style */
    #paymentModal .pos-modal-content {
        background: linear-gradient(145deg, #ffffff 0%, #f0f9ff 100%) !important;
        border-radius: 20px !important;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
    }
    
    /* Header - gradient with pattern */
    #paymentModal .pos-modal-header {
        background: linear-gradient(135deg, #0d9488 0%, #14b8a6 50%, #0d9488 100%) !important;
        padding: 12px 15px !important;
        position: relative;
        overflow: hidden;
    }
    
    #paymentModal .pos-modal-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: shimmer 3s infinite;
    }
    
    @keyframes shimmer {
        0%, 100% { transform: translateX(-20%) rotate(0deg); }
        50% { transform: translateX(20%) rotate(5deg); }
    }
    
    #paymentModal .pos-modal-header h3 {
        font-size: 12px !important;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    /* Payment methods - pill style grid */
    .sidebar-payment-method {
        background: linear-gradient(145deg, #ffffff, #f1f5f9) !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 10px 6px !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    .sidebar-payment-method:hover,
    .sidebar-payment-method.selected {
        background: linear-gradient(145deg, #0d9488, #14b8a6) !important;
        border-color: #0d9488 !important;
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3) !important;
    }
    
    .sidebar-payment-method:hover i,
    .sidebar-payment-method.selected i,
    .sidebar-payment-method:hover span,
    .sidebar-payment-method.selected span {
        color: white !important;
    }
    
    .sidebar-payment-method i {
        font-size: 18px !important;
        color: #0d9488;
        transition: all 0.3s !important;
    }
    
    /* Cart items section - card style */
    #paymentModal [style*="border: 1px solid #e2e8f0"][style*="border-radius: 6px"] {
        background: linear-gradient(145deg, #f8fafc, #f1f5f9) !important;
        border-radius: 12px !important;
        padding: 8px !important;
        margin-bottom: 10px !important;
    }
    
    /* Summary section - modern cards */
    #paymentModal .pos-modal-body > div > div:last-child {
        padding: 12px !important;
    }
    
    #paymentModal [style*="border-bottom: 1px solid #e2e8f0"] {
        border-color: rgba(226, 232, 240, 0.5) !important;
        padding: 6px 0 !important;
    }
    
    /* Totals styling */
    #paymentModal [style*="font-weight: bold"],
    #paymentModal [style*="font-weight: 600"] {
        color: #0d9488 !important;
    }
    
    /* Payable amount - highlight card */
    #paymentModal [style*="background: #f0fdf4"] {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
        border-radius: 12px !important;
        padding: 10px !important;
        margin: 8px 0 !important;
        border: 1px solid #34d399 !important;
    }
    
    /* Paid/Due amounts - colored badges */
    #paymentModal [style*="color: #10b981"] {
        background: linear-gradient(135deg, #10b981, #059669) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        font-weight: 700 !important;
    }
    
    #paymentModal [style*="color: #ef4444"] {
        background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        font-weight: 700 !important;
    }
    
    /* Payment type buttons */
    #paymentModal button[style*="border-radius: 8px"] {
        border-radius: 12px !important;
        padding: 10px 15px !important;
        font-weight: 600 !important;
        transition: all 0.3s !important;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
    }
    
    #paymentModal button[style*="background: #10b981"],
    #paymentModal button[style*="background: linear-gradient"] {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    }
    
    #paymentModal button:active {
        transform: scale(0.98) !important;
    }
    
    /* Input fields - modern style */
    #paymentModal input[type="number"],
    #paymentModal input[type="text"] {
        border-radius: 10px !important;
        border: 2px solid #e2e8f0 !important;
        padding: 8px 12px !important;
        font-size: 14px !important;
        transition: all 0.3s !important;
    }
    
    #paymentModal input:focus {
        border-color: #14b8a6 !important;
        box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1) !important;
    }
    
    /* Scrollbar styling */
    #paymentModal ::-webkit-scrollbar {
        width: 4px;
    }
    
    #paymentModal ::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    #paymentModal ::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #14b8a6, #0d9488);
        border-radius: 4px;
    }
}

/* Very Small Screens */
@media (max-width: 360px) {
    .product-grid {
        grid-template-columns: 1fr;
    }
    
    .pos-header-bar .nav-links a span {
        display: none;
    }
}

@media (max-width: 1200px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Tablets */
@media (max-width: 992px) {
    .pos-right {
        width: 350px;
    }
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .cart-header, .cart-item {
        grid-template-columns: 30px 1fr 70px 90px;
        gap: 8px;
        font-size: 11px;
    }
    .pos-footer .total-display .amount {
        font-size: 28px;
    }
}

/* Small Mobile */
@media (max-width: 480px) {
    .product-grid {
        grid-template-columns: 1fr;
    }
    .pos-footer .footer-controls {
        grid-template-columns: 1fr;
    }
    .pos-header-bar select {
        width: 100%;
        margin-bottom: 5px;
    }
}


/* Invisible Scrollbar Utility */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Modal Validation Styles - Matching Customer Page */
input.error-border, select.error-border, textarea.error-border { 
    border-color: #ef4444 !important; 
    background-color: #fef2f2; 
}
.error-msg-text { 
    color: #ef4444; 
    font-size: 0.75rem; 
    margin-top: 4px; 
    display: none; 
}

/* Section Headers for Modals */
.modal-section-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
}
.modal-section-header i { color: #3b82f6; }

/* Selected Payment Method - Global Override */
.sidebar-payment-method.selected {
    background: #10b981 !important;
    border-color: #10b981 !important;
    box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2) !important;
}

.sidebar-payment-method.selected i,
.sidebar-payment-method.selected span,
.sidebar-payment-method.selected div {
    color: white !important;
}

.sidebar-payment-method:hover {
    background: linear-gradient(135deg, #f0fdfa, #ccfbf1) !important;
    border-color: #14b8a6 !important;
}

/* Base style for payment method cards */
.sidebar-payment-method {
    padding: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    height: 100%; /* Fill the grid cell */
    min-height: 100px;
    width: 100%;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.sidebar-payment-method .pm-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    width: 100%;
    padding: 10px;
}

.sidebar-payment-method i {
    color: #0d9488;
    font-size: 24px;
    margin-bottom: 8px;
}

.sidebar-payment-method .pm-name {
    font-size: 13px;
    color: #334155;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    width: 100%;
    padding: 0 5px;
}

.sidebar-payment-method .pm-balance {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
}

.payment-methods-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr)); /* Force equal width regardless of content */
    grid-auto-rows: 1fr; /* Force all rows to have the same height */
    gap: 12px;
    width: 100%;
}

/* Hide spin buttons for number inputs POS-wide */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
    -webkit-appearance: none; 
    margin: 0; 
}
input[type=number] {
    -moz-appearance: textfield;
}

</style>


<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300 overflow-hidden">
        <div class="navbar-fixed-top">
        </div>
        
        <!-- POS Header Bar (Full Width Navbar) -->
        <div class="pos-header-bar">
            <select id="store_select" class="store-select">
                <option value="all" selected>All Stores</option>
                <?php foreach($stores as $store): ?>
                    <option value="<?= $store['id']; ?>">
                        <?= htmlspecialchars($store['store_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <div class="nav-links">
                <a href="/pos/pos/" class="active"><i class="fas fa-cash-register"></i> POS</a>
                <a href="#"><i class="fas fa-book"></i> CASHBOOK</a>
                <a href="/pos/invoice/list"><i class="fas fa-file-invoice"></i> INVOICE</a>
                <a href="#" onclick="openHeldOrdersModal()"><i class="fas fa-pause-circle"></i> HOLD ORDER</a>
                <a href="#" onclick="openModal('addProductModal')"><i class="fas fa-plus"></i> Product</a>
                <a href="#" onclick="openModal('addCustomerModal')"><i class="fas fa-user-plus"></i> Customer</a>
                <a href="#" onclick="openModal('giftcardModal')"><i class="fas fa-gift"></i> Giftcard</a>
                <a href="/pos/admin/stock_alert.php"><i class="fas fa-exclamation-triangle"></i> Stock Alert</a>
                <a href="/pos/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="#" onclick="lockScreen()"><i class="fas fa-lock"></i> Lockscreen</a>
            </div>
            
            <!-- Fullscreen Toggle Button -->
            <button id="fullscreen-btn" onclick="toggleFullscreen()" class="fullscreen-btn" title="Toggle Fullscreen">
                <i class="fas fa-expand" id="fullscreen-icon"></i>
            </button>
        </div>
        
        <!-- Main Content Below Header -->
        <div class="pos-container">
            <!-- Left Panel: Products -->
            <div class="pos-left">
                <!-- Search Bar -->
                <div class="search-bar">
                    <input type="text" id="product_search" placeholder="Search/Barcode Scan..." autofocus>
                </div>
                
                <!-- Category Filter -->
                <div class="category-filter">
                    <button class="active" data-category="all">View All</button>
                    <?php 
                    mysqli_data_seek($categories_result, 0);
                    while($cat = mysqli_fetch_assoc($categories_result)): ?>
                        <button data-category="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></button>
                    <?php endwhile; ?>
                </div>
                
                <!-- Product Grid -->
                <div class="product-grid" id="product-grid">
                    <?php while($product = mysqli_fetch_assoc($products_result)): 
                        $stock = floatval($product['opening_stock']);
                        $alert_qty = floatval($product['alert_quantity'] ?? 5);
                        $is_out_of_stock = $stock <= 0;
                        $is_low_stock = $stock > 0 && $stock <= $alert_qty;
                    ?>
                        <div class="product-card <?= $is_out_of_stock ? 'out-of-stock-card' : '' ?>" data-id="<?= $product['id']; ?>" data-name="<?= htmlspecialchars($product['product_name']); ?>" data-price="<?= $product['selling_price']; ?>" data-category="<?= $product['category_id']; ?>" data-stock="<?= $stock; ?>">
                            <?php if($is_out_of_stock): ?>
                                <div class="stock-badge out-of-stock">Out of Stock</div>
                            <?php elseif($is_low_stock): ?>
                                <div class="stock-badge low-stock">Low Stock</div>
                            <?php endif; ?>
                            <?php if(!empty($product['thumbnail'])): ?>
                                <img src="<?= $product['thumbnail']; ?>" alt="<?= htmlspecialchars($product['product_name']); ?>">
                            <?php else: ?>
                                <img src="../assets/images/no-image.png" alt="No Image">
                            <?php endif; ?>
                            <div class="info">
                                <div class="name"><?= htmlspecialchars($product['product_name']); ?></div>
                                <div class="price">৳<?= number_format($product['selling_price'], 2); ?></div>
                                <div class="stock">Stock: <?= $product['opening_stock']; ?> <?= $product['unit_name'] ?? 'pcs'; ?></div>
                            </div>
                            <?php if($is_out_of_stock): ?>
                                <button class="add-btn out-of-stock-btn" disabled>
                                    <i class="fas fa-ban"></i> Out of Stock
                                </button>
                            <?php else: ?>
                                <button class="add-btn" onclick="addToCart(<?= $product['id']; ?>, '<?= htmlspecialchars($product['product_name'], ENT_QUOTES); ?>', <?= $product['selling_price']; ?>)">
                                    <i class="fas fa-cart-plus"></i> Add To Cart
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <div class="pagination-wrapper" id="pagination-controls">
                    <?php if($page_no > 1): ?>
                        <button onclick="loadProducts(<?= $page_no - 1; ?>)">
                            <i class="fas fa-chevron-left"></i><span class="pagination-text"> Previous</span>
                        </button>
                    <?php else: ?>
                        <button disabled><i class="fas fa-chevron-left"></i><span class="pagination-text"> Previous</span></button>
                    <?php endif; ?>
                    
                    <span class="pagination-info" style="padding: 8px 14px; font-weight: 600; color: #64748b;">
                        <span class="page-label">Page </span><?= (int)$page_no; ?><span class="page-of"> of </span><?= (int)$total_pages; ?>
                    </span>
                    
                    <?php if($page_no < $total_pages): ?>
                        <button onclick="loadProducts(<?= $page_no + 1; ?>)">
                            <span class="pagination-text">Next </span><i class="fas fa-chevron-right"></i>
                        </button>
                    <?php else: ?>
                        <button disabled><span class="pagination-text">Next </span><i class="fas fa-chevron-right"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Panel: Cart -->
            <div class="pos-right">
                <!-- Customer Section -->
                <div class="customer-section">
                    <div class="customer-box">
                        <div class="avatar"><i class="fas fa-user"></i></div>
                        <div class="details" onclick="openModal('customerSelectModal')">
                            <div class="name" id="selected-customer-name">Walking Customer</div>
                            <div class="phone" id="selected-customer-phone">0170000000000</div>
                        </div>
                        <div id="selected-customer-due-display" style="margin-left: auto; margin-right: 10px; display: none;">
                            <span style="background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid #fecaca;">Due: <span id="selected-customer-due-amount">0.00</span></span>
                        </div>
                        <i class="fas fa-pen" style="color: #10b981; cursor: pointer; padding: 4px;" onclick="openWalkingCustomerModal()"></i>
                        <button type="button" onclick="openModal('addCustomerModal')" style="background: #10b981; color: white; border: none; padding: 4px 6px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <input type="hidden" id="selected_customer_id" value="">
                    <input type="hidden" id="customer_total_balance" value="0">
                    <input type="hidden" id="customer_credit" value="0">
                    <input type="hidden" id="customer_due_balance" value="0">
                    <input type="hidden" id="customer_giftcard_balance" value="0">
                    <input type="hidden" id="customer_opening_balance" value="0">
                </div>
                
                <!-- Cart Items -->
                <div class="cart-section">
                    <div class="cart-header">
                        <span>QTY</span>
                        <span>PRODUCT</span>
                        <span>PRICE</span>
                        <span>SUBTOTAL</span>
                    </div>
                    <div id="cart-items">
                        <div class="empty-cart" id="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Cart is empty. Add products to begin.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Totals Section -->
                <div class="totals-section">
                    <div class="totals-row">
                        <span>TOTAL ITEM</span>
                        <span id="total-items">0 (0)</span>
                        <span>TOTAL</span>
                        <span id="cart-total">0.00</span>
                    </div>
                    <div class="totals-row split-row">
                        <div class="input-group">
                            <label>DISCOUNT</label>
                            <input type="number" id="discount-input" value="0" min="0" step="0.01">
                        </div>
                        <div class="input-group">
                            <label>TAX AMOUNT (%)</label>
                            <input type="number" id="tax-input" value="0" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="totals-row split-row">
                        <div class="input-group">
                            <label>SHIPPING CHARGE</label>
                            <input type="number" id="shipping-input" value="0" min="0" step="0.01">
                        </div>
                        <div class="input-group">
                            <label>OTHER CHARGE</label>
                            <input type="number" id="other-input" value="0" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="totals-row total">
                        <span>TOTAL PAYABLE</span>
                        <span id="grand-total">৳0.00</span>
                    </div>
                </div>
                
                <!-- Footer Actions -->
                <div class="pos-footer">
                    <div class="total-display">
                        <div class="label">TOTAL</div>
                        <div class="amount" id="footer-total">৳0.00</div>
                    </div>
                    <div class="footer-controls">
                        <input type="date" class="date-input" id="sale-date" value="<?= date('Y-m-d'); ?>">
                        <button class="hold-btn" onclick="prepareHoldModal()">
                            <i class="fas fa-pause"></i> HOLD
                        </button>
                        <button class="pay-btn" onclick="prepareAndOpenPaymentModal()">
                            <i class="fas fa-credit-card"></i> PAY NOW
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Payment Modal -->
<?php include('../includes/payment_modal.php'); ?>


<!-- Hold Order Modal (Premium Teal Redesign) -->
<div class="pos-modal" id="holdModal">
    <div class="pos-modal-content" style="max-width: 750px; border-radius: 12px; border: none; padding: 0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: visible;">
        <!-- Header with unique layout -->
        <div class="pos-modal-header" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); padding: 18px 25px; position: relative; border-radius: 12px 12px 0 0; border-bottom: 2px solid rgba(255,255,255,0.1);">
            <h3 style="color: white; margin: 0; display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 700; letter-spacing: 0.5px;">
                <i class="fas fa-save" style="font-size: 22px;"></i> 
                <span>HOLD CURRENT ORDER</span>
            </h3>
            <button class="close-btn" onclick="closeModal('holdModal')" style="display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.2); width: 32px; height: 32px; border-radius: 50%; color: white; transition: all 0.3s ease;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="pos-modal-body" style="padding: 20px; background: #fdfdfd;">
            <!-- Reference Input -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; align-items: center; background: white; border: 2px solid #f1f5f9; border-radius: 10px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                    <div style="background: #f8fafc; padding: 12px 18px; border-right: 2px solid #f1f5f9; color: #0d9488;">
                        <i class="fas fa-pen-alt"></i>
                    </div>
                    <input type="text" id="hold-reference" style="flex: 1; border: none; padding: 12px 15px; font-size: 15px; outline: none; color: #1e293b;" placeholder="Add order note or reference here...">
                </div>
            </div>

            <!-- Scrollable Order Details -->
            <div style="border: 2px solid #f1f5f9; border-radius: 12px; background: white; overflow: hidden; margin-bottom: 15px;">
                <div style="background: #f8fafc; padding: 10px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-weight: 700; color: #334155; font-size: 13px;">ITEMIZED BREAKDOWN</span>
                    <span style="background: #0d9488; color: white; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;"><span id="hold-item-count">0</span> Items</span>
                </div>

                <div style="max-height: 280px; overflow-y: auto; scrollbar-width: thin;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tbody id="hold-order-items">
                            <!-- Items will be injected here -->
                        </tbody>
                    </table>
                </div>

                <!-- Redesigned Footer with 2 rows for totals as requested -->
                <div style="background: #fbfcfd; border-top: 2px solid #f1f5f9; padding: 15px 20px;">
                    <!-- Row 1: Subtotal & Discount -->
                    <div style="display: flex; justify-content: space-between; gap: 20px; margin-bottom: 10px;">
                        <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 8px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                            <span style="font-size: 13px; color: #64748b; font-weight: 600;">Subtotal</span>
                            <span id="hold-subtotal" style="font-weight: 700; color: #1e293b;">0.00</span>
                        </div>
                        <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 8px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                            <span style="font-size: 13px; color: #cc4d4d; font-weight: 600;">Discount</span>
                            <span id="hold-discount" style="font-weight: 700; color: #cc4d4d;">0.00</span>
                        </div>
                    </div>
                    <!-- Row 2: Tax & Charges -->
                    <div style="display: flex; justify-content: space-between; gap: 20px; margin-bottom: 10px;">
                        <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 8px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                            <span style="font-size: 13px; color: #64748b; font-weight: 600;">Tax</span>
                            <span id="hold-tax" style="font-weight: 700; color: #1e293b;">0.00</span>
                        </div>
                        <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 8px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                            <span style="font-size: 13px; color: #64748b; font-weight: 600;">Charges</span>
                            <span style="font-weight: 700; color: #1e293b;"><span id="hold-shipping">0.00</span> + <span id="hold-other">0.00</span></span>
                        </div>
                    </div>
                    <!-- Net Payable -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px; padding: 12px; background: #f0fdfa; border-radius: 10px; border: 1px solid #ccfbf1;">
                        <span style="font-weight: 800; color: #0f766e; font-size: 15px; text-transform: uppercase; letter-spacing: 1px;">Net Payable</span>
                        <span id="hold-payable" style="font-size: 22px; font-weight: 900; color: #0d9488; font-family: 'Inter', sans-serif;">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Styled Teal Save Button -->
        <div style="padding: 15px 25px; text-align: right; background: white; border-top: 1px solid #f1f5f9; display: flex; justify-content: center; gap: 12px; border-radius: 0 0 12px 12px;">
            <button onclick="closeModal('holdModal')" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                Cancel
            </button>
            <button onclick="holdOrder()" style="padding: 10px 45px; border-radius: 8px; border: none; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3); display: flex; align-items: center; gap: 10px; transition: all 0.2s; font-size: 16px;">
                <i class="fas fa-check-circle"></i>
                <span>SAVE HOLD</span>
            </button>
        </div>
    </div>
</div>

<!-- Customer Select Modal -->
<!-- Held Orders List Modal -->
<div class="pos-modal" id="heldOrdersModal">
    <div class="pos-modal-content" style="max-width: 1050px; width: 95%; height: 85vh; border-radius: 12px; overflow: hidden; border: none; padding: 0; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        <!-- Premium Header -->
        <div class="pos-modal-header" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); padding: 15px 25px; flex-shrink: 0; display: flex; align-items: center; border-bottom: none; position: relative;">
            <h3 style="color: white; margin: 0; display: flex; align-items: center; gap: 12px; font-size: 18px; font-weight: 700; letter-spacing: 0.5px;">
                <i class="fas fa-history" style="font-size: 20px;"></i>
                <span>HELD ORDERS HISTORY</span>
                <span id="held-order-title-ref" style="font-weight: 400; font-size: 14px; background: rgba(255,255,255,0.15); padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); margin-left: 10px;">Select an order</span>
            </h3>
            <button class="close-btn" onclick="closeModal('heldOrdersModal')" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.2); color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.3); font-size: 14px; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="pos-modal-body" style="padding: 0; background: #f8fafc; flex: 1; display: flex; overflow: hidden;">
            <!-- Modern Sidebar -->
            <div style="width: 350px; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; background: #fff; box-shadow: 10px 0 15px -10px rgba(0,0,0,0.05);">
                <div style="padding: 20px; border-bottom: 1px solid #f1f5f9;">
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #0d9488;"></i>
                        <input type="text" id="held-order-search" placeholder="Search by name or reference..." oninput="filterHeldOrders()" style="width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #f1f5f9; border-radius: 10px; font-size: 14px; outline: none; background: #fdfdfd; transition: all 0.3s ease;">
                    </div>
                </div>
                <div id="held-orders-list" class="custom-scroll" style="flex: 1; overflow-y: auto; padding: 10px;">
                    <!-- List items injected here -->
                </div>
            </div>

            <!-- Enhanced Content Area -->
            <div id="held-order-detail-view" style="flex: 1; display: flex; flex-direction: column; background: #fdfdfd;">
                <div id="held-order-placeholder" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #cbd5e1;">
                    <div style="width: 120px; height: 120px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fas fa-receipt" style="font-size: 50px; opacity: 0.3;"></i>
                    </div>
                    <p style="font-size: 16px; font-weight: 600; letter-spacing: 0.5px;">SELECT AN ORDER TO VIEW DETAILS</p>
                </div>
                
                <div id="held-order-content" style="display: none; flex: 1; flex-direction: column; overflow: hidden;">
                    <div style="padding: 25px; flex: 1; overflow-y: auto;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
                            <div>
                                <h4 style="margin: 0 0 5px 0; font-size: 20px; color: #334155; font-weight: 800;">ORDER DETAILS</h4>
                                <div style="display: flex; align-items: center; gap: 15px; font-size: 13px; color: #64748b;">
                                    <span><i class="fas fa-user-circle"></i> <span id="held-detail-customer"></span></span>
                                    <span><i class="fas fa-clock"></i> <span id="held-detail-date"></span></span>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="background: #f0fdfa; color: #0d9488; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 700; border: 1px solid #ccfbf1;">
                                    ACTIVE ORDER
                                </div>
                            </div>
                        </div>

                        <!-- Product Table -->
                        <div style="background: white; border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                                        <th style="padding: 15px; text-align: center; width: 50px; color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;">#</th>
                                        <th style="padding: 15px; text-align: left; color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;">Product</th>
                                        <th style="padding: 15px; text-align: center; width: 120px; color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;">Price</th>
                                        <th style="padding: 15px; text-align: center; width: 100px; color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;">Qty</th>
                                        <th style="padding: 15px; text-align: right; width: 130px; color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="held-detail-items">
                                </tbody>
                            </table>
                        </div>

                        <!-- Restructured Totals into Two Rows as requested -->
                        <div style="margin-top: 25px; background: #fbfcfd; border-top: 2px solid #f1f5f9; padding: 20px; border-radius: 0 0 12px 12px; border: 1px solid #f1f5f9; border-top: none;">
                            <!-- Row 1: Subtotal & Discount -->
                            <div style="display: flex; justify-content: space-between; gap: 20px; margin-bottom: 12px;">
                                <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border-radius: 10px; border: 1px solid #f1f5f9;">
                                    <span style="font-size: 13px; color: #64748b; font-weight: 600;">Subtotal</span>
                                    <span id="held-detail-subtotal" style="font-weight: 700; color: #1e293b;">0.00</span>
                                </div>
                                <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border-radius: 10px; border: 1px solid #f1f5f9;">
                                    <span style="font-size: 13px; color: #cc4d4d; font-weight: 600;">Discount</span>
                                    <span id="held-detail-discount" style="font-weight: 700; color: #cc4d4d;">0.00</span>
                                </div>
                            </div>
                            <!-- Row 2: Tax & Charges -->
                            <div style="display: flex; justify-content: space-between; gap: 20px; margin-bottom: 12px;">
                                <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border-radius: 10px; border: 1px solid #f1f5f9;">
                                    <span style="font-size: 13px; color: #64748b; font-weight: 600;">Tax Amount</span>
                                    <span id="held-detail-tax" style="font-weight: 700; color: #1e293b;">0.00</span>
                                </div>
                                <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border-radius: 10px; border: 1px solid #f1f5f9;">
                                    <span style="font-size: 13px; color: #64748b; font-weight: 600;">Other Charges</span>
                                    <span style="font-weight: 700; color: #1e293b;"><span id="held-detail-shipping">0.00</span> + <span id="held-detail-other">0.00</span></span>
                                </div>
                            </div>
                            <!-- Net Payable Highlight -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding: 15px 20px; background: #0d9488; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.2);">
                                <span style="font-weight: 800; color: white; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;">NET PAYABLE AMOUNT</span>
                                <span id="held-detail-payable" style="font-size: 26px; font-weight: 900; color: white; font-family: 'Inter', sans-serif;">0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detail Footer Actions -->
                    <div style="padding: 20px 25px; text-align: right; background: white; border-top: 1px solid #f1f5f9; display: flex; justify-content: center; gap: 15px;">
                        <button onclick="closeModal('heldOrdersModal')" style="padding: 12px 25px; border-radius: 10px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                            Close Window
                        </button>
                        <button id="btn-resume-held" style="padding: 12px 40px; border-radius: 10px; border: none; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3); display: flex; align-items: center; gap: 10px; transition: all 0.2s; font-size: 15px;">
                            <i class="fas fa-edit"></i>
                            <span>RESUME THIS ORDER</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="pos-modal" id="customerSelectModal">
    <div class="pos-modal-content" style="max-width: 500px;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-user-plus"></i> Quick Select Customer</h3>
            <button class="close-btn" onclick="closeModal('customerSelectModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="pos-modal-body" style="padding: 20px;">
            <div style="position: relative; margin-bottom: 20px;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                <input type="text" id="customer-search-input" placeholder="Search customer by name or phone..." 
                       style="width: 100%; padding: 12px 12px 12px 42px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px; transition: all 0.2s; background: #f8fafc; outline: none;"
                       onfocus="this.style.borderColor='#10b981'; this.style.backgroundColor='#fff'; this.style.boxShadow='0 0 0 4px rgba(16, 185, 129, 0.1)';"
                       onblur="this.style.borderColor='#e2e8f0'; this.style.backgroundColor='#f8fafc'; this.style.boxShadow='none';">
            </div>
            <div id="customer-list" style="max-height: 380px; overflow-y: auto; padding-right: 4px;" class="custom-scrollbar">
                <div class="customer-option" onclick="selectCustomer(0, 'Walking Customer', '0170000000000', 0, 0, 0)" 
                     style="padding: 14px 16px; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 10px; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: all 0.2s;"
                     onmouseover="this.style.borderColor='#10b981'; this.style.background='#f0fdf4';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='white';">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b;">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <strong style="display: block; color: #1e293b; font-size: 14px;">Walking Customer</strong>
                        <span style="color: #64748b; font-size: 12px;">0170000000000</span>
                    </div>
                </div>
                <?php 
                mysqli_data_seek($customers_result, 0);
                while($customer = mysqli_fetch_assoc($customers_result)): 
                    $credit = floatval($customer['credit']);
                    $gc_balance = floatval($customer['giftcard_balance']);
                    $opening_bal = floatval($customer['opening_balance']);
                ?>
                    <div class="customer-option" onclick="selectCustomer(<?= $customer['id']; ?>, '<?= htmlspecialchars($customer['name'], ENT_QUOTES); ?>', '<?= $customer['mobile']; ?>', <?= $credit; ?>, <?= $gc_balance; ?>, <?= $opening_bal; ?>)" 
                         style="padding: 14px 16px; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 10px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s;"
                         onmouseover="this.style.borderColor='#10b981'; this.style.background='#f0fdf4';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='white';">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #10b981;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <strong style="display: block; color: #1e293b; font-size: 14px;"><?= htmlspecialchars($customer['name']); ?></strong>
                                <span style="color: #64748b; font-size: 12px;"><?= $customer['mobile']; ?></span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                            <?php if($opening_bal > 0): ?>
                                <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; border: 1px solid #bae6fd;">Wallet: <?= number_format($opening_bal, 2); ?></span>
                            <?php endif; ?>
                            <?php if($credit > 0): ?>
                                <span style="background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; border: 1px solid #fecaca;">Due: <?= number_format($credit, 2); ?></span>
                            <?php endif; ?>
                            <?php if($gc_balance > 0): ?>
                                <span style="background: #f0fdf4; color: #10b981; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; border: 1px solid #d1fae5;">GC: <?= number_format($gc_balance, 2); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>





<!-- Walking Customer Edit Modal -->
<div class="pos-modal" id="walkingCustomerModal">
    <div class="pos-modal-content" style="max-width: 450px;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-pen"></i> Walking Customer (<span id="walking-display-phone">0170000000000</span>)</h3>
            <button class="close-btn" onclick="closeModal('walkingCustomerModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="pos-modal-body">
            <div class="payment-input-group">
                <input type="text" id="walking-mobile-input" placeholder="Mobile Number..." style="text-align: left;">
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal('walkingCustomerModal')" style="flex: 1; padding: 12px; background: #f59e0b; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" onclick="saveWalkingCustomer()" style="flex: 1; padding: 12px; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    <i class="fas fa-check"></i> Ok
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="pos-modal" id="addCustomerModal">
    <div class="pos-modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-user-plus"></i> Quick Add Customer</h3>
            <button class="close-btn" onclick="closeModal('addCustomerModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="pos-modal-body">
            <form id="quickCustomerForm" enctype="multipart/form-data">
                <!-- Identity & Classification -->
                <div class="modal-section-header">
                    <i class="fas fa-id-card"></i>
                    <span>Identity & Classification</span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 2fr 2fr; gap: 12px; margin-bottom: 16px;">
                    <div class="payment-input-group">
                        <label>Code *</label>
                        <input type="text" name="code_name" id="modal_code_name" required placeholder="CUST-001">
                        <span class="error-msg-text" id="error-modal_code_name">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" id="modal_name" required placeholder="e.g. John Doe">
                        <span class="error-msg-text" id="error-modal_name">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Company</label>
                        <input type="text" name="company_name" placeholder="Company Name (Optional)">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>Customer Group *</label>
                        <select name="customer_group" id="modal_customer_group" required>
                            <option value="">Select Group</option>
                            <option value="Retail">Retail</option>
                            <option value="Wholesale">Wholesale</option>
                            <option value="VIP">VIP</option>
                            <option value="Corporate">Corporate</option>
                        </select>
                        <span class="error-msg-text" id="error-modal_customer_group">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Membership Level *</label>
                        <select name="membership_level" id="modal_membership_level" required>
                            <option value="">Select Level</option>
                            <option value="Standard">Standard</option>
                            <option value="Silver">Silver</option>
                            <option value="Gold">Gold</option>
                            <option value="Platinum">Platinum</option>
                        </select>
                        <span class="error-msg-text" id="error-modal_membership_level">Required</span>
                    </div>
                </div>

                <!-- Contact & Personal -->
                <div class="modal-section-header">
                    <i class="fas fa-address-book"></i>
                    <span>Contact & Personal</span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div class="payment-input-group">
                        <label>Phone *</label>
                        <input type="tel" name="mobile" id="modal_mobile" required placeholder="017...">
                        <span class="error-msg-text" id="error-modal_mobile">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Email Address</label>
                        <input type="email" name="email" id="modal_email" placeholder="email@example.com">
                        <span class="error-msg-text" id="error-modal_email">Invalid email</span>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob">
                    </div>
                    <div class="payment-input-group">
                        <label>Sex *</label>
                        <select name="sex" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="payment-input-group">
                        <label>Age</label>
                        <input type="number" name="age" placeholder="Age">
                    </div>
                </div>

                <!-- Financial Status -->
                <div class="modal-section-header">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Financial Status</span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>Opening Balance *</label>
                        <input type="number" step="0.01" name="opening_balance" id="modal_opening_balance" value="0" required placeholder="0.00">
                        <span class="error-msg-text" id="error-modal_opening_balance">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Credit Limit *</label>
                        <input type="number" step="0.01" name="credit_limit" id="modal_credit_limit" value="0" required placeholder="0.00">
                        <span class="error-msg-text" id="error-modal_credit_limit">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Reward Points</label>
                        <input type="number" name="reward_points" value="0" placeholder="0">
                    </div>
                </div>

                <!-- Location Details -->
                <div class="modal-section-header">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Location Details</span>
                </div>
                <div class="payment-input-group" style="margin-bottom: 12px;">
                    <label>Full Address</label>
                    <textarea name="address" rows="2" style="resize: none;" placeholder="Street, Area, etc."></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>City *</label>
                        <input type="text" name="city" id="modal_city" required placeholder="City">
                        <span class="error-msg-text" id="error-modal_city">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>State *</label>
                        <input type="text" name="state" id="modal_state" required placeholder="State">
                        <span class="error-msg-text" id="error-modal_state">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Country *</label>
                        <select name="country" id="modal_country" required>
                            <option value="">Select Country</option>
                            <option value="Bangladesh" selected>Bangladesh</option>
                            <option value="India">India</option>
                            <option value="Pakistan">Pakistan</option>
                            <option value="United States">United States</option>
                            <option value="United Kingdom">United Kingdom</option>
                            <option value="Canada">Canada</option>
                            <option value="Australia">Australia</option>
                        </select>
                        <span class="error-msg-text" id="error-modal_country">Required</span>
                    </div>
                </div>

                <!-- Additional Fields -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>Profile Picture</label>
                        <input type="file" name="image" accept="image/*" style="font-size: 13px;">
                        <small style="color: #64748b; font-size: 11px;">Max 2MB (JPG, PNG)</small>
                    </div>
                    <div class="payment-input-group">
                        <label>Sort Order *</label>
                        <input type="number" name="sort_order" id="modal_sort_order" value="0" required min="0">
                        <span class="error-msg-text" id="error-modal_sort_order">Required</span>
                    </div>
                </div>

                <!-- Status -->
                <div class="modal-section-header">
                    <i class="fas fa-toggle-on"></i>
                    <span>Customer Status</span>
                </div>
                <div style="margin-bottom: 20px;">
                    <?php
                        $current_status = '1';
                        $status_title = 'Customer';
                        $card_id = 'modal-customer-status-card';
                        $label_id = 'modal-customer-status-label';
                        $input_id = 'modal_customer_status';
                        $toggle_id = 'modal-customer-status-toggle';
                        include('../includes/status_card.php');
                    ?>
                </div>

                <div style="display: flex; justify-content: center; margin-top: 10px;">
                    <button type="submit" style="
                        background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
                        color: white;
                        border: none;
                        padding: 12px 40px;
                        border-radius: 50px;
                        font-weight: 700;
                        font-size: 15px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        position: relative;
                        overflow: hidden;
                    " onmouseover="this.style.transform='translateY(-2px) scale(1.02)'; this.style.boxShadow='0 8px 20px rgba(13, 148, 136, 0.4)';" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 4px 15px rgba(13, 148, 136, 0.3)';">
                        <i class="fas fa-save" style="font-size: 18px;"></i>
                        <span>Save Customer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<!-- Add Product Modal -->
<div class="pos-modal" id="addProductModal">
    <div class="pos-modal-content" style="max-width: 900px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-user-plus"></i> Quick Add Product</h3>
            <button class="close-btn" onclick="closeModal('addProductModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="pos-modal-body no-scrollbar" style="overflow-y: auto; flex: 1; padding: 20px;">
            <form id="quickProductForm" enctype="multipart/form-data" onsubmit="handleQuickAddProduct(event)">
                
                <!-- Basic Info -->
                <div class="section-title text-sm font-bold text-teal-700 mb-2 border-b border-teal-100 pb-1">Basic Information</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="payment-input-group">
                        <label>Product Name *</label>
                        <input type="text" name="product_name" required placeholder="Enter product name">
                    </div>
                    <div class="payment-input-group">
                        <label>Product Code *</label>
                        <div class="flex">
                            <input type="text" name="product_code" id="modal_product_code" required placeholder="SKU / Barcode" class="rounded-r-none">
                            <button type="button" onclick="generateModalCode()" class="bg-teal-700 text-white px-3 rounded-r-lg hover:bg-teal-800"><i class="fas fa-random"></i></button>
                        </div>
                    </div>
                    <div class="payment-input-group">
                        <label>Barcode Symbology</label>
                        <select name="barcode_symbology">
                            <option value="code128">Code 128</option>
                            <option value="code39">Code 39</option>
                            <option value="ean13">EAN-13</option>
                            <option value="upc">UPC</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="payment-input-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php 
                            mysqli_data_seek($categories_result, 0);
                            while($cat = mysqli_fetch_assoc($categories_result)): 
                            ?>
                                <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="payment-input-group">
                        <label>Brand</label>
                        <select name="brand_id">
                            <option value="">Select Brand</option>
                            <?php while($brand = mysqli_fetch_assoc($brands)): ?>
                                <option value="<?= $brand['id']; ?>"><?= htmlspecialchars($brand['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="payment-input-group">
                        <label>Unit *</label>
                        <select name="unit_id" required>
                            <option value="">Select Unit</option>
                            <?php while($unit = mysqli_fetch_assoc($units)): ?>
                                <option value="<?= $unit['id']; ?>"><?= htmlspecialchars($unit['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="section-title text-sm font-bold text-teal-700 mb-2 border-b border-teal-100 pb-1 mt-4">Pricing & Tax</div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="payment-input-group">
                        <label>Purchase Price</label>
                        <input type="number" name="purchase_price" id="modal_purchase_price" step="0.01" placeholder="0.00" oninput="calculateModalPrices()">
                    </div>
                    <div class="payment-input-group">
                        <label>Tax Rate</label>
                        <select name="tax_rate_id" id="modal_tax_rate_id" onchange="calculateModalPrices()">
                            <option value="" data-rate="0">No Tax</option>
                            <?php while($tax = mysqli_fetch_assoc($taxes)): ?>
                                <option value="<?= $tax['id']; ?>" data-rate="<?= $tax['rate']; ?>"><?= htmlspecialchars($tax['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="payment-input-group">
                        <label>Tax Method</label>
                        <select name="tax_method" id="modal_tax_method" onchange="calculateModalPrices()">
                            <option value="exclusive">Exclusive</option>
                            <option value="inclusive">Inclusive</option>
                        </select>
                    </div>
                    <div class="payment-input-group">
                        <label>Selling Price *</label>
                        <input type="number" name="selling_price" id="modal_selling_price" required step="0.01" placeholder="0.00" class="font-bold text-teal-700">
                    </div>
                </div>

                <!-- Inventory & Extras -->
                <div class="section-title text-sm font-bold text-teal-700 mb-2 border-b border-teal-100 pb-1 mt-4">Inventory & Details</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="payment-input-group">
                        <label>Opening Stock</label>
                        <input type="number" name="opening_stock" value="0">
                    </div>
                    <div class="payment-input-group">
                        <label>Alert Quantity</label>
                        <input type="number" name="alert_quantity" value="5">
                    </div>
                     <div class="payment-input-group">
                        <label>Product Image</label>
                        <input type="file" name="product_image" accept="image/*" class="text-xs">
                    </div>
                </div>

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="payment-input-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" class="w-full border rounded p-2 text-sm" placeholder="Product details..."></textarea>
                    </div>
                     <div class="payment-input-group">
                        <label>Expire Date</label>
                        <input type="date" name="expire_date">
                    </div>
                </div>

                <div class="sticky bottom-0 bg-white pt-4 border-t mt-4 flex justify-center gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="closeModal('addProductModal')">Cancel</button>
                    <button type="submit" class="complete-btn" style="width: auto; padding: 10px 24px;">
                        <i class="fas fa-save"></i> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Add Giftcard Modal -->
<div class="pos-modal" id="giftcardModal">
    <div class="pos-modal-content" style="max-width: 1000px; max-height: 90vh; overflow-y: auto;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-user-plus"></i> Quick Add Giftcard</h3>
            <button class="close-btn" onclick="closeModal('giftcardModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="pos-modal-body no-scrollbar" style="padding: 20px;">
            <form id="quickGiftcardForm" enctype="multipart/form-data">
                
                <!-- Card Information Section -->
                <div class="modal-section-header">
                    <i class="fas fa-credit-card"></i>
                    <span>Card Information</span>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div class="payment-input-group">
                        <label>Card Number *</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" name="card_no" id="modal_card_no" required placeholder="Scan or Enter Code" style="flex: 1;" maxlength="64">
                            <button type="button" onclick="generateModalCardNumber()" class="bg-teal-700 hover:bg-teal-800 text-white px-3 rounded-lg transition-all" title="Generate Card Number">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <span class="error-msg-text" id="error-modal_card_no">Card Number is required</span>
                    </div>

                    <div class="payment-input-group" style="position: relative;">
                        <label>Customer</label>
                        <input type="text" id="modal_customer_search" placeholder="Search customer..." autocomplete="off">
                        <input type="hidden" name="customer_id" id="modal_customer_id">
                        <!-- Customer Dropdown -->
                        <div id="modal_customer_dropdown" class="absolute left-0 right-0 mt-1 bg-white border border-teal-500 rounded-lg overflow-y-auto hidden shadow-2xl z-[9999]" style="position: absolute; width: 100%; max-height: 200px;">
                            <?php 
                            mysqli_data_seek($customers_result, 0);
                            while($customer = mysqli_fetch_assoc($customers_result)): 
                            ?>
                                <div class="modal-customer-option px-4 py-3 hover:bg-teal-50 cursor-pointer transition-colors border-b border-slate-100 last:border-b-0" 
                                     data-id="<?= $customer['id']; ?>" 
                                     data-name="<?= htmlspecialchars($customer['name']); ?>">
                                    <span class="font-medium text-slate-800"><?= htmlspecialchars($customer['name']); ?></span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div class="payment-input-group">
                        <label>Card Value (USD) *</label>
                        <input type="number" step="0.01" name="value" id="modal_value" required placeholder="0.00" min="0">
                        <span class="error-msg-text" id="error-modal_value">Card Value is required</span>
                    </div>

                    <div class="payment-input-group">
                        <label>Current Balance (USD)</label>
                        <input type="number" step="0.01" name="balance" id="modal_balance" placeholder="0.00" min="0" readonly style="background: #f1f5f9;">
                        <small style="color: #64748b; font-size: 11px;">Auto-set to card value</small>
                    </div>

                    <div class="payment-input-group">
                        <label>Expiry Date *</label>
                        <input type="date" name="expiry_date" id="modal_expiry_date" required>
                        <span class="error-msg-text" id="error-modal_expiry_date">Expiry Date is required</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>Card Logo</label>
                        <input type="file" name="image" id="modal_image" accept="image/*" style="font-size: 13px;">
                        <small style="color: #64748b; font-size: 11px;">Max 2MB (JPG, PNG)</small>
                    </div>

                    <!-- Status Card -->
                    <div>
                        <?php
                            $current_status = '1';
                            $status_title = 'Giftcard';
                            $card_id = 'modal-giftcard-status-card';
                            $label_id = 'modal-giftcard-status-label';
                            $input_id = 'modal-giftcard-status-input';
                            $toggle_id = 'modal-giftcard-status-toggle';
                            include('../includes/status_card.php');
                        ?>
                    </div>
                </div>

                <!-- Card Preview -->
                <div class="modal-section-header">
                    <i class="fas fa-eye"></i>
                    <span>Card Preview</span>
                </div>
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-6 text-white relative overflow-hidden" style="margin-bottom: 20px;">
                    <div class="relative z-10">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 40px;">
                            <div>
                                <p style="font-size: 12px; opacity: 0.8;">Giftcard</p>
                                <p id="modal-preview-card-no" style="font-size: 16px; font-weight: bold; margin-top: 4px;">••••••••••••••••</p>
                            </div>
                            <div id="modal-preview-logo" style="width: 48px; height: 48px; background: rgba(255,255,255,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image" style="opacity: 0.5;"></i>
                            </div>
                        </div>
                        <div style="text-align: center; margin-bottom: 30px;">
                            <p id="modal-preview-value" style="font-size: 32px; font-weight: bold;">USD 0.00</p>
                            <p style="font-size: 10px; opacity: 0.8; margin-top: 4px; text-transform: uppercase; letter-spacing: 1px;">Card Balance</p>
                        </div>
                        <div style="background: white; border-radius: 8px; padding: 12px; margin: 0 auto; width: 60%; height: 80px; display: flex; align-items: center; justify-content: center;">
                            <svg id="modal-barcode-container" width="100%" height="60" style="display: block;"></svg>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <div>
                                <p style="font-size: 10px; opacity: 0.8;">Card Holder</p>
                                <p style="font-weight: 600;">GiftCard</p>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 10px; opacity: 0.8;">Expiry</p>
                                <p id="modal-preview-expiry" style="font-weight: 600;">MM/YY</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <!-- <button type="button" onclick="closeModal('giftcardModal')" style="padding: 12px 24px; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-times"></i> Cancel
                    </button> -->
                    <button type="submit" class="complete-btn" style="width: auto; padding: 12px 24px;">
                        <i class="fas fa-save"></i> Save Gift Card
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Force sidebar to be collapsed on POS page
(function() {
    // Set localStorage to unlock (collapsed) state
    localStorage.setItem('sidebarLocked', 'false');
    
    // Apply collapsed state immediately
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const toggleIcon = document.getElementById('toggle-icon');
    
    if(sidebar) {
        sidebar.classList.add('collapsed');
    }
    if(mainContent) {
        mainContent.style.marginLeft = '80px';
    }
    if(toggleIcon) {
        toggleIcon.classList.add('rotate-180');
    }
})();

// Cart data
let cart = [];

var customerId = 0; // Current customer ID
const stores = <?= json_encode($stores); ?>;

// Modal functions
function openModal(id) {
    document.getElementById(id).classList.add('active');
    if(id === 'paymentModal') {
        updatePaymentSummary();
    }
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Close modal on backdrop click
// --- QUICK ADD PRODUCT LOGIC ---

// 1. Generate Random Code
function generateModalCode() {
    const min = 10000000;
    const max = 99999999;
    document.getElementById('modal_product_code').value = Math.floor(Math.random() * (max - min + 1)) + min;
}

// 2. Calculate Prices
function calculateModalPrices() {
    const pInput = document.getElementById('modal_purchase_price');
    const sInput = document.getElementById('modal_selling_price');
    const taxSelect = document.getElementById('modal_tax_rate_id');
    const taxMethodSelect = document.getElementById('modal_tax_method');

    let purchase = parseFloat(pInput.value) || 0;
    
    // Get Tax Rate
    let taxRate = 0;
    if (taxSelect && taxSelect.value) {
        taxRate = parseFloat(taxSelect.options[taxSelect.selectedIndex].getAttribute('data-rate')) || 0;
    }

    let method = taxMethodSelect ? taxMethodSelect.value : 'exclusive';
    
    let finalSellingPrice = purchase; 

    // Simple Logic: selling = purchase * (1 + tax/100) if exclusive.
    if (method === 'exclusive') {
        finalSellingPrice = purchase * (1 + (taxRate / 100));
    }
    
    sInput.value = finalSellingPrice.toFixed(2);
}

// 3. Handle AJAX Submission
function handleQuickAddProduct(e) {
    e.preventDefault();
    const form = document.getElementById('quickProductForm');
    const formData = new FormData(form);
    
    // Add current store ID context if needed
    const storeId = document.getElementById('store_select').value;
    formData.append('current_store_id', storeId);

    // Show loading state
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;

    fetch('ajax_add_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            showToast('Success: ' + data.message, 'success');
            closeModal('addProductModal');
            form.reset();
            
            // Reload products to show new item
            loadProducts(1);
            
            // Optional: Auto-add to cart logic could go here
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Server Error', 'error');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}


document.querySelectorAll('.pos-modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if(e.target === this) {
            this.classList.remove('active');
        }
    });
});


// Store Selection Listener
document.getElementById('store_select').addEventListener('change', function() {
    loadProducts(1); // Reload from page 1 when store changes
});

// Track current filters
let currentCategory = 'all';
let currentSearch = '';

// Load Products via AJAX
function loadProducts(page) {
    const grid = document.querySelector('.product-grid');
    grid.style.opacity = '0.5';
    
    // Get selected store
    const storeId = document.getElementById('store_select').value;
    const categoryId = currentCategory === 'all' ? 0 : currentCategory;
    const searchQuery = encodeURIComponent(currentSearch);
    
    // Prevent default button behavior
    if(event) event.preventDefault();

    let url = `get_products.php?page=${page}&store_id=${storeId}`;
    if(categoryId > 0) url += `&category_id=${categoryId}`;
    if(searchQuery) url += `&search=${searchQuery}`;

    fetch(url)
    .then(response => response.json())
    .then(data => {
        grid.innerHTML = data.html;
        document.getElementById('pagination-controls').innerHTML = data.pagination_html;
        grid.style.opacity = '1';
        
        // Scroll to top of grid
        grid.scrollTop = 0;
    })
    .catch(error => {
        console.error('Error loading products:', error);
        grid.style.opacity = '1';
    });
}

// Add to cart with stock validation
function addToCart(id, name, price, stock = null) {
    // Get stock from product card if not provided
    if(stock === null) {
        const productCard = document.querySelector(`.product-card[data-id="${id}"]`);
        stock = productCard ? parseFloat(productCard.dataset.stock) || 0 : 999;
    }
    
    const existingItem = cart.find(item => item.id === id);
    const currentQty = existingItem ? existingItem.qty : 0;
    
    // Check if adding would exceed stock
    if(currentQty + 1 > stock) {
        showToast(`Cannot add more! Only ${stock} in stock.`, 'error');
        return;
    }
    
    if(existingItem) {
        existingItem.qty++;
        existingItem.stock = stock; // Update stock info
    } else {
        cart.push({
            id: id,
            name: name,
            price: parseFloat(price),
            qty: 1,
            stock: stock
        });
    }
    
    renderCart();
    showToast('Added to cart: ' + name);
}

// Update quantity with stock validation
function updateQty(id, change) {
    const item = cart.find(item => item.id === id);
    if(item) {
        const newQty = item.qty + change;
        
        // Check stock limit when increasing
        if(change > 0 && newQty > item.stock) {
            showToast(`Cannot exceed stock! Only ${item.stock} available.`, 'error');
            return;
        }
        
        if(newQty <= 0) {
            removeFromCart(id);
        } else {
            item.qty = newQty;
            renderCart();
        }
    }
}

// Set quantity directly with stock validation
function setQty(id, newQty) {
    const item = cart.find(item => item.id === id);
    if(item) {
        newQty = parseInt(newQty) || 0;
        
        if(newQty > item.stock) {
            showToast(`Cannot exceed stock! Only ${item.stock} available.`, 'error');
            item.qty = item.stock;
            renderCart();
            return;
        }
        
        if(newQty <= 0) {
            removeFromCart(id);
        } else {
            item.qty = newQty;
            renderCart();
        }
    }
}

// Remove from cart
function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    renderCart();
}

// Render cart
function renderCart() {
    const container = document.getElementById('cart-items');
    
    if(cart.length === 0) {
        container.innerHTML = '<div class="empty-cart"><i class="fas fa-shopping-cart"></i><p>Cart is empty. Add products to begin.</p></div>';
        updateTotals();
        return;
    }
    
    let html = '';
    cart.forEach(item => {
        const subtotal = item.price * item.qty;
        html += `
            <div class="cart-item">
                <div class="qty-control">
                    <button onclick="updateQty(${item.id}, -1)">-</button>
                    <input type="number" class="qty-input" value="${item.qty}" min="1" 
                        onchange="setQty(${item.id}, this.value)" 
                        onfocus="this.select()">
                    <button onclick="updateQty(${item.id}, 1)">+</button>
                </div>
                <div class="product-name">${item.name}</div>
                <div class="price">৳${item.price.toFixed(2)}</div>
                <div class="subtotal">
                    ৳${subtotal.toFixed(2)}
                    <span class="remove-btn" onclick="removeFromCart(${item.id})"><i class="fas fa-trash"></i></span>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    updateTotals();
    
    // Auto-focus and select last added item
    const inputs = container.querySelectorAll('.qty-input');
    if(inputs.length > 0) {
        const lastInput = inputs[inputs.length - 1];
        lastInput.focus();
        lastInput.select();
    }
}

// Update totals
function updateTotals() {
    let subtotal = 0;
    let totalQty = 0;
    
    cart.forEach(item => {
        subtotal += item.price * item.qty;
        totalQty += item.qty;
    });
    
    const discount = parseFloat(document.getElementById('discount-input').value) || 0;
    const taxPercent = parseFloat(document.getElementById('tax-input').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping-input').value) || 0;
    const other = parseFloat(document.getElementById('other-input').value) || 0;
    
    const taxAmount = (subtotal - discount) * (taxPercent / 100);
    const grandTotal = subtotal - discount + taxAmount + shipping + other;
    
    document.getElementById('total-items').textContent = `${cart.length} (${totalQty})`;
    document.getElementById('cart-total').textContent = subtotal.toFixed(2);
    document.getElementById('grand-total').textContent = '৳' + grandTotal.toFixed(2);
    document.getElementById('footer-total').textContent = '৳' + grandTotal.toFixed(2);
}

// Recalculate on input change
['discount-input', 'tax-input', 'shipping-input', 'other-input'].forEach(id => {
    document.getElementById(id).addEventListener('input', updateTotals);
});

// Payment functions
function prepareAndOpenPaymentModal() {
    // Check if cart is empty
    if (cart.length === 0) {
        showToast('Cart is empty', 'error');
        return;
    }
    
    // Toggle Opening Balance Wallet visibility based on updated UI
    const openingBal = parseFloat(document.getElementById('customer_opening_balance').value) || 0;
    const pmOpeningBal = document.getElementById('pm-opening-balance');
    const pmOpeningBalVal = document.getElementById('pm-opening-balance-value');
    if (openingBal > 0) {
        if(pmOpeningBal) pmOpeningBal.style.display = 'flex';
        if(pmOpeningBalVal) pmOpeningBalVal.textContent = openingBal.toFixed(2);
    } else {
        if(pmOpeningBal) pmOpeningBal.style.display = 'none';
        if(pmOpeningBal) pmOpeningBal.classList.remove('selected');
    }

    // Toggle Gift Card method visibility
    const giftcardBal = parseFloat(document.getElementById('customer_giftcard_balance').value) || 0;
    const pmGiftcard = document.getElementById('pm-giftcard');
    const pmGiftcardBal = document.getElementById('pm-giftcard-balance');
    if (giftcardBal > 0) {
        if(pmGiftcard) pmGiftcard.style.display = 'flex';
        if(pmGiftcardBal) pmGiftcardBal.textContent = giftcardBal.toFixed(2);
    } else {
        if(pmGiftcard) pmGiftcard.style.display = 'none';
        if(pmGiftcard) pmGiftcard.classList.remove('selected');
    }
    
    // Calculate current totals using the shared function to populate global elements first
    if(window.updatePaymentSummary) window.updatePaymentSummary();
    
    const grandTotal = parseFloat(document.getElementById('grand-total').textContent.replace('৳', '')) || 0;
    const currentCustomerId = parseInt(document.getElementById('selected_customer_id').value) || 0;
    const customerName = document.getElementById('selected-customer-name').textContent || 'Walking Customer';
    
    // Open the shared payment modal
    window.openPaymentModal({
        totalPayable: grandTotal,
        previousDue: parseFloat(document.getElementById('customer_due_balance').value) || 0,
        customerId: currentCustomerId,
        customerName: customerName,
        onSubmit: submitPosSale
    });
    
    // Default to Full Payment view
    if(window.setPaymentType) window.setPaymentType('full');
}



// Toast notification
function showToast(message, type = 'success') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
    Toast.fire({
        icon: type,
        title: message
    });
}

// Toggle Fullscreen Mode
function toggleFullscreen() {
    const icon = document.getElementById('fullscreen-icon');
    
    if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement && !document.msFullscreenElement) {
        // Enter fullscreen
        const elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.mozRequestFullScreen) {
            elem.mozRequestFullScreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
        icon.classList.remove('fa-expand');
        icon.classList.add('fa-compress');
    } else {
        // Exit fullscreen
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
        icon.classList.remove('fa-compress');
        icon.classList.add('fa-expand');
    }
}

// Listen for fullscreen change to update icon
document.addEventListener('fullscreenchange', updateFullscreenIcon);
document.addEventListener('webkitfullscreenchange', updateFullscreenIcon);
document.addEventListener('mozfullscreenchange', updateFullscreenIcon);
document.addEventListener('MSFullscreenChange', updateFullscreenIcon);

function updateFullscreenIcon() {
    const icon = document.getElementById('fullscreen-icon');
    if (document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement) {
        icon.classList.remove('fa-expand');
        icon.classList.add('fa-compress');
    } else {
        icon.classList.remove('fa-compress');
        icon.classList.add('fa-expand');
    }
}

// Lock Screen Function
function lockScreen() {
    Swal.fire({
        title: '<i class="fas fa-lock" style="font-size: 48px; color: #0d9488; margin-bottom: 15px;"></i><br>Screen Locked',
        html: `
            <p style="color: #64748b; margin-bottom: 20px;">Enter your password to unlock</p>
            <input type="password" id="unlock-password" class="swal2-input" placeholder="Password" style="margin: 0;">
        `,
        showCancelButton: false,
        confirmButtonText: '<i class="fas fa-unlock"></i> Unlock',
        confirmButtonColor: '#0d9488',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showCloseButton: false,
        backdrop: `
            rgba(15, 23, 42, 0.95)
            left top
            no-repeat
        `,
        customClass: {
            popup: 'lock-screen-popup'
        },
        preConfirm: () => {
            const password = document.getElementById('unlock-password').value;
            if (!password) {
                Swal.showValidationMessage('Please enter your password');
                return false;
            }
            // You can add actual password validation here via AJAX
            // For now, we just check if password is entered
            return password;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            showToast('Screen Unlocked', 'success');
        }
    });
    
    // Focus on password input after modal opens
    setTimeout(() => {
        document.getElementById('unlock-password')?.focus();
    }, 100);
}

// Convert number to words (for invoice)
function numberToWords(num) {
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 
                  'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 
                  'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    
    if (num === 0) return 'Zero Taka Only';
    
    function convertGroup(n) {
        if (n < 20) return ones[n];
        if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + ones[n % 10] : '');
        return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' ' + convertGroup(n % 100) : '');
    }
    
    let result = '';
    const crore = Math.floor(num / 10000000);
    const lakh = Math.floor((num % 10000000) / 100000);
    const thousand = Math.floor((num % 100000) / 1000);
    const remainder = Math.floor(num % 1000);
    const paisa = Math.round((num - Math.floor(num)) * 100);
    
    if (crore) result += convertGroup(crore) + ' Crore ';
    if (lakh) result += convertGroup(lakh) + ' Lakh ';
    if (thousand) result += convertGroup(thousand) + ' Thousand ';
    if (remainder) result += convertGroup(remainder);
    
    result = result.trim() + ' Taka';
    if (paisa) result += ' and ' + convertGroup(paisa) + ' Paisa';
    result += ' Only';
    
    return result;
}

// Complete sale - acts as the callback for the shared payment modal
function submitPosSale(paymentData) {
    if(cart.length === 0) {
        showToast('Cart is empty!', 'error');
        return;
    }
    
    // Original pos.php used store_select from the modal DOM, but shared modal might not have it inside the modal form?
    // Wait, the store select is usually in the MAIN POS header, not the modal.
    // Let's check where store_select is. Usually in header.
    const storeSelect = document.getElementById('store_select');
    if (!storeSelect || !storeSelect.value || storeSelect.value === 'all') {
        closeModal('paymentModal');
        Swal.fire({
            icon: 'warning',
            title: 'Store Selection Required',
            text: 'Please select a specific store to proceed with the sale.',
            confirmButtonColor: '#0d9488'
        });
        
        if(storeSelect) {
            storeSelect.style.border = '2px solid red';
            setTimeout(() => storeSelect.style.border = '', 2000);
        }
        return;
    }

    // Walking customer check is handled by payment_modal.js UI logic (disable button), 
    // but we can double check here or just rely on server.
    // Server side walking customer check is already fixed.

    const formData = new FormData();
    formData.append('action', 'process_payment');
    formData.append('cart', JSON.stringify(cart));
    formData.append('customer_id', document.getElementById('selected_customer_id').value || 0);
    formData.append('store_id', storeSelect.value);
    formData.append('payment_method_id', paymentData.selectedPaymentMethod);
    formData.append('discount', document.getElementById('discount-input').value || 0);
    formData.append('tax_percent', document.getElementById('tax-input').value || 0);
    formData.append('shipping', document.getElementById('shipping-input').value || 0);
    formData.append('other_charge', document.getElementById('other-input').value || 0);
    formData.append('amount_received', paymentData.amountReceived);
    
    // Ensure payments are formatted correctly for PHP
    // paymentData.appliedPayments is array of objects { type, amount, label }
    // PHP expects exact structure? 
    // PHP decoding: is_array($payments). 
    formData.append('payments', JSON.stringify(paymentData.appliedPayments));
    
    formData.append('sale_date', document.getElementById('sale-date').value);
    formData.append('walking_customer_mobile', document.getElementById('selected-customer-phone').textContent || '');
    
    const previousDue = parseFloat(document.getElementById('payment-previous-due')?.textContent) || 0;
    formData.append('previous_due', previousDue);
    
    const btn = document.querySelector('.complete-btn'); // Shared modal button class
    if(btn) {
        var originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;
    }

    fetch('/pos/pos/save_sale.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            closeModal('paymentModal');
            // Reset shared modal state via window global if needed, or openPaymentModal resets it next time.
            
            // Update customer balance in UI
            if (data.new_balance !== undefined) {
                const totalBal = parseFloat(data.new_balance);
                document.getElementById('customer_total_balance').value = totalBal;
                
                const currentDue = totalBal > 0 ? totalBal : 0;
                const spendableCredit = totalBal < 0 ? Math.abs(totalBal) : 0;
                
                document.getElementById('customer_due_balance').value = currentDue;
                document.getElementById('customer_credit').value = spendableCredit;

                const dueDisplay = document.getElementById('selected-customer-due-display');
                const dueAmount = document.getElementById('selected-customer-due-amount');
                if (dueDisplay && dueAmount) {
                    if (currentDue > 0) {
                        dueDisplay.style.display = 'block';
                        dueAmount.textContent = currentDue.toFixed(2);
                    } else {
                        dueDisplay.style.display = 'none';
                    }
                }
            }
            
            // Populate Invoice
            document.getElementById('inv-id').textContent = data.invoice_id;
            document.getElementById('inv-modal-id').textContent = data.invoice_id;
            document.getElementById('inv-date').textContent = new Date().toLocaleString();
            
            // Store Info
            const currentStore = stores.find(s => s.id == storeSelect.value) || stores[0];
            if(currentStore) {
                document.getElementById('inv-store-name').textContent = currentStore.store_name;
                document.getElementById('inv-store-address').textContent = currentStore.address || '';
                document.getElementById('inv-store-city').textContent = currentStore.city_zip || '';
                document.getElementById('inv-store-contact').textContent = `Mobile: ${currentStore.phone || ''}, Email: ${currentStore.email || ''}`;
            }

            // Barcode
            JsBarcode("#inv-barcode", data.invoice_id, {
                format: "CODE128",
                width: 2,
                height: 40,
                displayValue: true,
                fontSize: 10,
                margin: 0
            });
            
            const customerNameEl = document.getElementById('selected-customer-name');
            const customerName = customerNameEl ? customerNameEl.textContent : 'Walking Customer';
            
            const customerPhoneEl = document.getElementById('selected-customer-phone');
            const customerPhone = customerPhoneEl ? customerPhoneEl.textContent : '';
            
            if(document.getElementById('inv-customer')) document.getElementById('inv-customer').textContent = customerName;
            if(document.getElementById('inv-phone')) document.getElementById('inv-phone').textContent = customerPhone;
            if(document.getElementById('inv-address')) document.getElementById('inv-address').textContent = ''; 
            
            // Items
            const itemsBody = document.getElementById('inv-items');
            itemsBody.innerHTML = '';
            cart.forEach((item, index) => {
                const total = (item.price * item.qty).toFixed(2);
                itemsBody.innerHTML += `
                    <tr style="border-bottom: 1px dotted #e5e7eb;">
                        <td style="text-align: left; padding: 5px 0;">
                            ${index + 1}. ${item.name}
                        </td>
                        <td style="text-align: right; padding: 5px 0;">${item.qty} ${item.unit || ''}</td>
                        <td style="text-align: right; padding: 5px 0;">${parseFloat(item.price).toFixed(2)}</td>
                        <td style="text-align: right; padding: 5px 0;">${total}</td>
                    </tr>
                `;
            });
            
            // Totals
            // Use values from shared modal inputs? Or just recalculate/use what was sent?
            // Actually payment_modal inputs are reset on close.
            // Better to use response data or recalculate.
            // Existing logic uses document.getElementById('payment-subtotal').textContent
            // This might still work if modal is just hidden, not destroyed.
            document.getElementById('inv-subtotal').textContent = document.getElementById('payment-subtotal').textContent;
            document.getElementById('inv-discount').textContent = document.getElementById('payment-discount').textContent;
            document.getElementById('inv-tax').textContent = document.getElementById('payment-tax').textContent;
            document.getElementById('inv-shipping').textContent = document.getElementById('payment-shipping').textContent;
            document.getElementById('inv-grand-total').textContent = document.getElementById('payment-payable').textContent;
            document.getElementById('inv-paid').textContent = paymentData.amountReceived; // Use data from callback
            
            // Calc Change
            const payable = parseFloat(document.getElementById('payment-payable').textContent) || 0;
            const paid = parseFloat(paymentData.amountReceived) || 0; 
            // Wait, amountReceived is TOTAL paid? Or just cash input?
            // paymentData.amountReceived is from input. 
            // Total paid is input + applied.
            const totalPaid = paid + paymentData.appliedPayments.reduce((s,p) => s + p.amount, 0);
            
            // Correct logic:
            document.getElementById('inv-paid').textContent = totalPaid.toFixed(2); // Show TOTAL paid
            
            const change = Math.max(0, totalPaid - payable);
            const due = Math.max(0, payable - totalPaid);
            
            // Invoice in words
            const inWords = numberToWords(payable);
            document.getElementById('inv-in-words').textContent = inWords;
            
            document.getElementById('inv-change').textContent = change.toFixed(2);
            document.getElementById('inv-due').textContent = due.toFixed(2);
            
            // Fix: Populate Previous Due and Total Due on Invoice
            const prevDueVal = parseFloat(document.getElementById('payment-previous-due').textContent) || 0;
            document.getElementById('inv-prev-due').textContent = prevDueVal.toFixed(2);
            
            // data.new_balance is the current_due after this sale
            const totalDueVal = data.new_balance !== undefined ? parseFloat(data.new_balance) : (due + prevDueVal);
            document.getElementById('inv-total-due').textContent = totalDueVal.toFixed(2);
            
            openModal('invoiceModal');
            
            // Reset Cart
            cart = [];
            updateCartUI();
            
            // Reset Customer
            document.getElementById('selected_customer_id').value = 0;
            document.getElementById('selected-customer-name').textContent = 'Walking Customer';
            document.getElementById('selected-customer-phone').textContent = '0170000000000';
            document.getElementById('customer_due_balance').value = 0;
            document.getElementById('customer_opening_balance').value = 0;
            document.getElementById('customer_giftcard_balance').value = 0;
            document.getElementById('customer_total_balance').value = 0;
            if(document.getElementById('selected-customer-due-display')) {
                document.getElementById('selected-customer-due-display').style.display = 'none';
            }
            
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
            if(btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if(btn) {
             btn.innerHTML = originalText;
             btn.disabled = false;
        }
    });
}

let allHeldOrders = [];
function openHeldOrdersModal() {
    allHeldOrders = []; // Clear before fetching fresh data
    const storeId = document.getElementById('store_select').value;
    const formData = new FormData();
    formData.append('action', 'get_held_orders');
    formData.append('store_id', storeId === 'all' ? 0 : storeId);
    formData.append('_t', Date.now()); // Cache buster

    fetch('/pos/pos/save_sale.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            allHeldOrders = data.orders;
            renderHeldOrdersList(allHeldOrders);
            // Hide detail content, show placeholder
            document.getElementById('held-order-content').style.display = 'none';
            document.getElementById('held-order-placeholder').style.display = 'flex';
            document.getElementById('held-order-title-ref').textContent = 'Select an order';
            openModal('heldOrdersModal');
        } else {
            showToast('Error fetching held orders', 'error');
        }
    });
}


function renderHeldOrdersList(orders) {
    const listBody = document.getElementById('held-orders-list');
    listBody.innerHTML = '';
    
    if (orders.length === 0) {
        listBody.innerHTML = '<div style="padding: 30px; text-align: center; color: #cbd5e1; font-size: 14px;"><i class="fas fa-folder-open" style="font-size: 40px; display: block; margin-bottom: 15px; opacity: 0.3;"></i>No orders found</div>';
        return;
    }

    orders.forEach(order => {
        const item = document.createElement('div');
        item.className = 'held-order-item';
        item.dataset.id = order.invoice_id;
        item.style.cssText = 'padding: 15px 20px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: space-between; position: relative; border-radius: 12px; margin-bottom: 8px;';
        item.onclick = () => showHeldOrderSummary(order.invoice_id);
        
        item.innerHTML = `
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <span class="order-ref" style="font-weight: 800; color: #334155; font-size: 14px;">${order.invoice_note || order.invoice_id}</span>
                <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">${new Date(order.created_at).toLocaleDateString()} • <span style="color: #0d9488;">${order.total_items} Items</span></span>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-weight: 800; color: #0d9488; font-size: 14px;">${parseFloat(order.grand_total).toFixed(2)}</span>
                <i class="fas fa-trash-alt btn-delete-held" style="color: #cbd5e1; cursor: pointer; padding: 8px; transition: all 0.2s;" onmouseover="this.style.color=\'#f43f5e\'" onmouseout="this.style.color=\'#cbd5e1\'" onclick="event.stopPropagation(); deleteHeldOrder(\'${order.invoice_id}\')"></i>
            </div>
        `;
        
        listBody.appendChild(item);
    });
}

function showHeldOrderSummary(invoiceId) {
    const order = allHeldOrders.find(o => o.invoice_id === invoiceId);
    if (!order) return;

    document.querySelectorAll('.held-order-item').forEach(el => {
        el.style.background = 'transparent';
        el.style.borderLeft = 'none';
        el.style.boxShadow = 'none';
    });
    const selectedItem = document.querySelector(`.held-order-item[data-id="${invoiceId}"]`);
    if (selectedItem) {
        selectedItem.style.background = '#f0fdfa';
        selectedItem.style.borderLeft = '4px solid #0d9488';
        selectedItem.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.05)';
    }

    // Update Detail View
    document.getElementById('held-order-title-ref').textContent = order.invoice_note || order.invoice_id;
    document.getElementById('held-detail-customer').textContent = order.customer_name || 'Walking Customer';
    document.getElementById('held-detail-date').textContent = new Date(order.created_at).toLocaleString();
    
    const itemsBody = document.getElementById('held-detail-items');
    itemsBody.innerHTML = '';
    
    order.items.forEach((item, index) => {
        itemsBody.innerHTML += `
            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                <td style="padding: 12px 15px; text-align: center; color: #94a3b8; font-weight: 700;">${index + 1}</td>
                <td style="padding: 12px 15px; color: #334155; font-weight: 600;">${item.item_name}</td>
                <td style="padding: 12px 15px; text-align: center; color: #64748b;">${parseFloat(item.item_price).toFixed(2)}</td>
                <td style="padding: 12px 15px; text-align: center; color: #64748b;"><span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-weight: 700;">${parseFloat(item.item_quantity).toFixed(2)}</span></td>
                <td style="padding: 12px 15px; text-align: right; color: #0d9488; font-weight: 800;">${parseFloat(item.item_total).toFixed(2)}</td>
            </tr>
        `;
    });

    document.getElementById('held-detail-subtotal').textContent = parseFloat(order.subtotal).toFixed(2);
    document.getElementById('held-detail-discount').textContent = parseFloat(order.discount_amount).toFixed(2);
    document.getElementById('held-detail-tax').textContent = parseFloat(order.item_tax).toFixed(2);
    document.getElementById('held-detail-shipping').textContent = parseFloat(order.shipping_amount).toFixed(2);
    document.getElementById('held-detail-other').textContent = parseFloat(order.others_charge).toFixed(2);
    document.getElementById('held-detail-payable').textContent = parseFloat(order.grand_total).toFixed(2);

    document.getElementById('btn-resume-held').onclick = () => resumeHeldOrder(invoiceId);

    document.getElementById('held-order-placeholder').style.display = 'none';
    document.getElementById('held-order-content').style.display = 'flex';
}

function resumeHeldOrder(invoiceId) {
    const formData = new FormData();
    formData.append('action', 'resume_held_order');
    formData.append('invoice_id', invoiceId);

    fetch('/pos/pos/save_sale.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const order = data.order;
            const items = data.items;
            
            // Load cart
            cart = items;
            renderCart();
            
            // Set Customer
            selectCustomer(
                order.customer_id || 0, 
                order.customer_name, 
                order.customer_phone, 
                order.customer_balance, 
                0 // giftcardBalance not stored in hold, default 0
            );
            
            // Set Totals Inputs
            document.getElementById('discount-input').value = order.discount_amount;
            document.getElementById('tax-input').value = order.tax_percent;
            document.getElementById('shipping-input').value = order.shipping_charge;
            document.getElementById('other-input').value = order.other_charge;
            
            updateTotals();
            closeModal('heldOrdersModal');
            showToast('Order resumed successfully', 'success');
        } else {
            showToast(data.message || 'Error resuming order', 'error');
        }
    });
}

function deleteHeldOrder(invoiceId) {
    closeModal('heldOrdersModal');
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the held order!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete_held_order');
            formData.append('invoice_id', invoiceId);

            fetch('/pos/pos/save_sale.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    allHeldOrders = allHeldOrders.filter(o => o.invoice_id !== invoiceId);
                    renderHeldOrdersList(allHeldOrders);
                    document.getElementById('held-order-content').style.display = 'none';
                    document.getElementById('held-order-placeholder').style.display = 'flex';
                    showToast('Deleted successfully', 'success');
                } else {
                    showToast(data.message || 'Error deleting', 'error');
                }
            });
        }
    });
}


function filterHeldOrders() {
    const q = document.getElementById('held-order-search').value.toLowerCase();
    const filtered = allHeldOrders.filter(o => 
        (o.invoice_note && o.invoice_note.toLowerCase().includes(q)) || 
        (o.customer_name && o.customer_name.toLowerCase().includes(q)) ||
        o.invoice_id.toLowerCase().includes(q)
    );
    renderHeldOrdersList(filtered);
}

// Prepare and open Hold Order Modal
function prepareHoldModal() {
    if(cart.length === 0) {
        showToast('Cart is empty!', 'error');
        return;
    }

    const itemsBody = document.getElementById('hold-order-items');
    itemsBody.innerHTML = '';
    
    let subtotal = 0;
    cart.forEach((item, index) => {
        const itemTotal = item.price * item.qty;
        subtotal += itemTotal;
        itemsBody.innerHTML += `
            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s ease;">
                <td style="padding: 15px 20px; text-align: center; color: #64748b; font-weight: 600;">${index + 1}</td>
                <td style="padding: 15px 10px;">
                    <div style="font-weight: 700; color: #334155; font-size: 14px;">${item.name}</div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                        <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">Qty: ${item.qty}</span> × 
                        <span style="color: #64748b;">${parseFloat(item.price).toFixed(2)}</span>
                    </div>
                </td>
                <td style="padding: 15px 20px; text-align: right; font-weight: 700; color: #1e293b; font-size: 14px;">${itemTotal.toFixed(2)}</td>
            </tr>
        `;
    });

    const discount = parseFloat(document.getElementById('discount-input').value) || 0;
    const taxPercent = parseFloat(document.getElementById('tax-input').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping-input').value) || 0;
    const other = parseFloat(document.getElementById('other-input').value) || 0;
    
    const taxAmount = (subtotal - discount) * (taxPercent / 100);
    const grandTotal = subtotal - discount + taxAmount + shipping + other;

    document.getElementById('hold-subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('hold-discount').textContent = discount.toFixed(2);
    document.getElementById('hold-tax').textContent = taxAmount.toFixed(2);
    document.getElementById('hold-shipping').textContent = shipping.toFixed(2);
    document.getElementById('hold-other').textContent = other.toFixed(2);
    document.getElementById('hold-payable').textContent = grandTotal.toFixed(2);
    document.getElementById('hold-item-count').textContent = cart.length;
    
    document.getElementById('hold-reference').value = '';
    openModal('holdModal');
}

// Hold order
function holdOrder() {
    const reference = document.getElementById('hold-reference').value;
    if(!reference.trim()) {
        showToast('Please enter an order reference', 'error');
        return;
    }
    
    // Calculate totals to send
    let subtotal = 0;
    cart.forEach(item => subtotal += (item.price * item.qty));
    
    const discount = parseFloat(document.getElementById('discount-input').value) || 0;
    const taxPercent = parseFloat(document.getElementById('tax-input').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping-input').value) || 0;
    const other = parseFloat(document.getElementById('other-input').value) || 0;
    
    const taxAmount = (subtotal - discount) * (taxPercent / 100);
    const grandTotal = subtotal - discount + taxAmount + shipping + other;
    
    const formData = new FormData();
    formData.append('action', 'hold_order');
    formData.append('cart', JSON.stringify(cart));
    formData.append('customer_id', document.getElementById('selected_customer_id').value || 0);
    const storeId = document.getElementById('store_select').value;
    if(storeId === 'all') {
        showToast('Please select a specific store before holding an order', 'error');
        return;
    }
    
    formData.append('store_id', storeId);
    formData.append('note', reference);
    
    // Extra fields
    formData.append('discount', discount);
    formData.append('tax_percent', taxPercent);
    formData.append('tax_amount', taxAmount);
    formData.append('shipping', shipping);
    formData.append('other_charge', other);
    formData.append('grand_total', grandTotal);
    
    fetch('/pos/pos/save_sale.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast('Order held successfully!', 'success');
            cart = [];
            renderCart();
            // Reset totals in UI as well
            updateTotals(); 
            closeModal('holdModal');
            document.getElementById('hold-reference').value = '';
            // Reload the page to ensure fresh data
            location.reload();
        } else {
            showToast(data.message || 'Error holding order', 'error');
        }
    })
    .catch(error => {
        showToast('Error holding order', 'error');
        console.error(error);
    });
}

// Customer selection
// Customer selection
function selectCustomer(id, name, phone, balance = 0, giftcardBalance = 0, openingBalance = 0) {
    customerId = id;
    document.getElementById('selected_customer_id').value = id;
    document.getElementById('selected-customer-name').textContent = name;
    document.getElementById('selected-customer-phone').textContent = phone;
    
    // Store balances
    const currentDue = parseFloat(balance);
    const giftcardBal = parseFloat(giftcardBalance);
    const openingBal = parseFloat(openingBalance);
    
    document.getElementById('customer_due_balance').value = currentDue;
    document.getElementById('customer_total_balance').value = currentDue;
    document.getElementById('customer_credit').value = 0; // Legacy field
    document.getElementById('customer_giftcard_balance').value = giftcardBal;
    document.getElementById('customer_opening_balance').value = openingBal;
    
    // Update due display in POS UI
    const dueDisplay = document.getElementById('selected-customer-due-display');
    const dueAmount = document.getElementById('selected-customer-due-amount');
    if (currentDue > 0) {
        dueDisplay.style.display = 'block';
        dueAmount.textContent = currentDue.toFixed(2);
    } else {
        dueDisplay.style.display = 'none';
    }
    
    closeModal('customerSelectModal');
    showToast('Customer selected: ' + name);
}

// Open Walking Customer Edit Modal
function openWalkingCustomerModal() {
    const currentPhone = document.getElementById('selected-customer-phone').textContent;
    document.getElementById('walking-mobile-input').value = currentPhone;
    document.getElementById('walking-display-phone').textContent = currentPhone;
    openModal('walkingCustomerModal');
}

// Save Walking Customer
function saveWalkingCustomer() {
    const mobile = document.getElementById('walking-mobile-input').value.trim();
    if(mobile) {
        document.getElementById('selected-customer-name').textContent = 'Walking Customer';
        document.getElementById('selected-customer-phone').textContent = mobile;
        document.getElementById('selected_customer_id').value = '';
        closeModal('walkingCustomerModal');
        showToast('Walking Customer updated');
    } else {
        showToast('Please enter a mobile number', 'error');
    }
}

// Product search with debounce
let searchTimeout = null;
document.getElementById('product_search').addEventListener('input', function() {
    currentSearch = this.value.trim();
    
    // Debounce search to avoid too many requests
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadProducts(1); // Reload products with new search
    }, 300); // Wait 300ms after user stops typing
});

// Category filter
document.querySelectorAll('.category-filter button').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.category-filter button').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        currentCategory = this.dataset.category;
        loadProducts(1); // Reload products with new category filter
    });
});

// Quick add customer form
document.getElementById('quickCustomerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    let isValid = true;
    const errors = [];
    
    // Helper function to show/hide errors
    const showError = (id, show) => {
        const el = document.getElementById(id);
        const errEl = document.getElementById('error-' + id);
        if(el) {
            if(show) {
                el.classList.add('error-border');
                if(errEl) errEl.style.display = 'block';
                errors.push(el.previousElementSibling.textContent + ' is required.'); // Assuming label is previous sibling
                isValid = false;
            } else {
                el.classList.remove('error-border');
                if(errEl) errEl.style.display = 'none';
            }
        }
    };
    
    // Clear all previous errors
    document.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));
    document.querySelectorAll('.error-msg-text').forEach(el => el.style.display = 'none');
    
    // Validate required fields
    showError('modal_code_name', document.getElementById('modal_code_name').value.trim() === "");
    showError('modal_name', document.getElementById('modal_name').value.trim() === "");
    showError('modal_customer_group', document.getElementById('modal_customer_group').value === "");
    showError('modal_membership_level', document.getElementById('modal_membership_level').value === "");
    showError('modal_mobile', document.getElementById('modal_mobile').value.trim() === "");
    showError('modal_opening_balance', document.getElementById('modal_opening_balance').value === "");
    showError('modal_credit_limit', document.getElementById('modal_credit_limit').value === "");
    showError('modal_city', document.getElementById('modal_city').value.trim() === "");
    showError('modal_state', document.getElementById('modal_state').value.trim() === "");
    showError('modal_country', document.getElementById('modal_country').value === "");
    showError('modal_sort_order', document.getElementById('modal_sort_order').value === "");
    
    // Email validation (optional but must be valid if entered)
    const email = document.getElementById('modal_email');
    if(email.value.trim() !== "") {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        showError('modal_email', !emailPattern.test(email.value));
    }
    
    if(!isValid) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: errors.join('<br>'),
            confirmButtonColor: '#0d9488'
        });
        return;
    }
    
    // If validation passes, submit the form
    const formData = new FormData(this);
    formData.append('save_customer_btn', 'true');
    
    // Automatically add the currently selected store from POS
    const currentStoreId = document.getElementById('store_select').value;
    formData.append('store_ids[]', currentStoreId);
    
    // Show loading state
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    fetch('/pos/customers/save_customer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is redirect or JSON
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }
        // If it's a redirect, consider it success
        return { success: true, message: 'Customer added successfully' };
    })
    .then(data => {
        if(data.success || data.msg_type === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Customer added successfully',
                confirmButtonColor: '#0d9488',
                timer: 2000
            });
            closeModal('addCustomerModal');
            this.reset();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error adding customer',
                confirmButtonColor: '#0d9488'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Server error occurred',
            confirmButtonColor: '#0d9488'
        });
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});



// ==========================================
// GIFT CARD MODAL HANDLERS
// ==========================================

// Generate random card number (16 digits)
function generateModalCardNumber() {
    let cardNo = '';
    for (let i = 0; i < 16; i++) {
        cardNo += Math.floor(Math.random() * 10);
    }
    document.getElementById('modal_card_no').value = cardNo;
    document.getElementById('modal-preview-card-no').textContent = cardNo;
    generateModalBarcode(cardNo);
}

// Generate barcode for modal
function generateModalBarcode(cardNo) {
    if (cardNo && cardNo.length > 0 && typeof JsBarcode !== 'undefined') {
        const barcodeContainer = document.getElementById('modal-barcode-container');
        if(barcodeContainer) {
            JsBarcode("#modal-barcode-container", cardNo, {
                format: "CODE128",
                width: 2,
                height: 60,
                displayValue: false,
                margin: 0
            });
            barcodeContainer.style.display = 'block';
        }
    }
}

// Initialize Gift Card Modal
function initGiftCardModal() {
    // Card number input - update preview and barcode
    const cardNoInput = document.getElementById('modal_card_no');
    if(cardNoInput) {
        cardNoInput.addEventListener('input', function() {
            document.getElementById('modal-preview-card-no').textContent = this.value || '••••••••••••••••';
            if(this.value) {
                generateModalBarcode(this.value);
            }
        });
    }

    // Value input - auto-set balance and update preview
    const valueInput = document.getElementById('modal_value');
    if(valueInput) {
        valueInput.addEventListener('input', function() {
            const value = parseFloat(this.value) || 0;
            document.getElementById('modal_balance').value = value.toFixed(2);
            document.getElementById('modal-preview-value').textContent = 'USD ' + value.toFixed(2);
        });
    }

    // Expiry date - update preview
    const expiryInput = document.getElementById('modal_expiry_date');
    if(expiryInput) {
        expiryInput.addEventListener('change', function() {
            document.getElementById('modal-preview-expiry').textContent = this.value || 'MM/YY';
        });
    }

    // Image upload - update preview
    const imageInput = document.getElementById('modal_image');
    if(imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('modal-preview-logo').innerHTML = '\u003cimg src="' + event.target.result + '" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;"\u003e';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Customer search dropdown
    const customerSearch = document.getElementById('modal_customer_search');
    const customerDropdown = document.getElementById('modal_customer_dropdown');
    const customerOptions = document.querySelectorAll('.modal-customer-option');
    const customerIdInput = document.getElementById('modal_customer_id');

    if(customerSearch && customerDropdown) {
        // Show dropdown on focus
        customerSearch.addEventListener('focus', () => {
            customerDropdown.classList.remove('hidden');
        });

        // Filter customers as user types
        customerSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            customerOptions.forEach(option => {
                const customerName = option.getAttribute('data-name').toLowerCase();
                option.style.display = customerName.includes(searchTerm) || searchTerm === '' ? 'block' : 'none';
            });
        });

        // Select customer
        customerOptions.forEach(option => {
            option.addEventListener('click', function() {
                const customerId = this.getAttribute('data-id');
                const customerName = this.getAttribute('data-name');
                customerSearch.value = customerName;
                customerIdInput.value = customerId;
                customerDropdown.classList.add('hidden');
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#modal_customer_search') && !e.target.closest('#modal_customer_dropdown')) {
                customerDropdown.classList.add('hidden');
            }
        });
    }

    // Initialize status toggle for modal (Safety Check)
    if(document.getElementById('modal-giftcard-status-card')) {
        initStatusToggle('modal-giftcard-status-card', 'modal-giftcard-status-toggle', 'modal-giftcard-status-input', 'modal-giftcard-status-label');
    }
}

// Gift Card Form Submission
document.getElementById('quickGiftcardForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    let isValid = true;
    const errors = [];

    // Clear previous errors
    document.querySelectorAll('.error-msg-text').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));

    // Validate Card Number
    const cardNo = document.getElementById('modal_card_no').value.trim();
    if (!cardNo) {
        document.getElementById('error-modal_card_no').style.display = 'block';
        document.getElementById('modal_card_no').classList.add('error-border');
        errors.push('Card Number is required');
        isValid = false;
    }

    // Validate Card Value
    const value = parseFloat(document.getElementById('modal_value').value);
    if (!value || value <= 0) {
        document.getElementById('error-modal_value').style.display = 'block';
        document.getElementById('modal_value').classList.add('error-border');
        errors.push('Card Value must be greater than 0');
        isValid = false;
    }

    // Validate Expiry Date
    const expiryDate = document.getElementById('modal_expiry_date').value;
    if (!expiryDate) {
        document.getElementById('error-modal_expiry_date').style.display = 'block';
        document.getElementById('modal_expiry_date').classList.add('error-border');
        errors.push('Expiry Date is required');
        isValid = false;
    }

    if (!isValid) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: errors.join('\u003cbr\u003e'),
            confirmButtonColor: '#0d9488'
        });
        return;
    }

    // If validation passes, submit the form
    const formData = new FormData(this);
    formData.append('save_giftcard_btn', 'true');

    // Show loading state
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '\u003ci class="fas fa-spinner fa-spin"\u003e\u003c/i\u003e Saving...';
    btn.disabled = true;

    fetch('/pos/giftcard/save', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json ? response.json() : response.text())
    .then(data => {
        // Handle both JSON and redirect responses
        if(typeof data === 'string') {
            // If it's a redirect or HTML response, consider it success
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Gift card created successfully',
                confirmButtonColor: '#0d9488',
                timer: 2000
            });
            closeModal('giftcardModal');
            this.reset();
            // Reset preview
            document.getElementById('modal-preview-card-no').textContent = '••••••••••••••••';
            document.getElementById('modal-preview-value').textContent = 'USD 0.00';
            document.getElementById('modal-preview-expiry').textContent = 'MM/YY';
            document.getElementById('modal-preview-logo').innerHTML = '\u003ci class="fas fa-image" style="opacity: 0.5;"\u003e\u003c/i\u003e';
            document.getElementById('modal-barcode-container').style.display = 'none';
        } else if(data.success || data.msg_type === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Gift card created successfully',
                confirmButtonColor: '#0d9488',
                timer: 2000
            });
            closeModal('giftcardModal');
            this.reset();
            // Reset preview
            document.getElementById('modal-preview-card-no').textContent = '••••••••••••••••';
            document.getElementById('modal-preview-value').textContent = 'USD 0.00';
            document.getElementById('modal-preview-expiry').textContent = 'MM/YY';
            document.getElementById('modal-preview-logo').innerHTML = '\u003ci class="fas fa-image" style="opacity: 0.5;"\u003e\u003c/i\u003e';
            document.getElementById('modal-barcode-container').style.display = 'none';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error creating gift card',
                confirmButtonColor: '#0d9488'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Server error occurred',
            confirmButtonColor: '#0d9488'
        });
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});

// Initialize gift card modal on page load
document.addEventListener('DOMContentLoaded', function() {
    initGiftCardModal();
    
    // Initialize customer status toggle
    initStatusToggle('modal-customer-status-card', 'modal-customer-status-toggle', 'modal_customer_status', 'modal-customer-status-label');
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // F2 - Focus search
    if(e.key === 'F2') {
        e.preventDefault();
        document.getElementById('product_search').focus();
    }
    // F4 - Open payment
    if(e.key === 'F4') {
        e.preventDefault();
        if(cart.length > 0) {
            openModal('paymentModal');
        }
    }
    // Escape - Close modals
    if(e.key === 'Escape') {
        document.querySelectorAll('.pos-modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
    }
});
// Reset Cart and Form
function resetCart() {
    cart = [];
    renderCart();
    
    // Reset payment inputs
    document.getElementById('discount-input').value = '';
    document.getElementById('tax-input').value = '';
    document.getElementById('shipping-input').value = '';
    document.getElementById('other-input').value = '';
    document.getElementById('amount-received').value = '';
    document.getElementById('change-amount').textContent = '৳0.00';
    
    // Reset selection
    selectedPaymentMethod = null;
    document.querySelectorAll('.payment-method').forEach(pm => pm.classList.remove('selected'));
    document.querySelectorAll('.sidebar-payment-method').forEach(pm => {
        pm.style.background = 'white';
        pm.style.borderColor = '#e2e8f0';
    });
    
    // Reset customer to default (Walking Customer)
    selectCustomer(0, 'Walking Customer', '0170000000000', 0, 0);
    
    updateTotals();
}

// Quick Select Customer Search Logic
document.getElementById('customer-search-input')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    const customerList = document.getElementById('customer-list');
    const options = customerList.querySelectorAll('.customer-option');
    let hasResults = false;

    options.forEach(option => {
        const text = option.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            option.style.display = 'flex';
            hasResults = true;
        } else {
            option.style.display = 'none';
        }
    });

    // Handle no results
    const noResultsId = 'customer-no-results';
    let noResultsEl = document.getElementById(noResultsId);
    
    if (!hasResults) {
        if (!noResultsEl) {
            noResultsEl = document.createElement('div');
            noResultsEl.id = noResultsId;
            noResultsEl.style.padding = '20px';
            noResultsEl.style.textAlign = 'center';
            noResultsEl.style.color = '#94a3b8';
            noResultsEl.innerHTML = '<i class="fas fa-user-slash" style="font-size: 24px; display: block; margin-bottom: 8px;"></i> No customers found';
            customerList.appendChild(noResultsEl);
        }
    } else if (noResultsEl) {
        noResultsEl.remove();
    }
});
</script>

<!-- Invoice Modal -->
<?php include('../includes/invoice_modal.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="/pos/assets/js/invoice_modal.js"></script>
<script src="/pos/assets/js/payment_logic.js"></script>
