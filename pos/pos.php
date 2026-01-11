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

// Fetch customers
$customers_query = "SELECT id, name, mobile FROM customers WHERE status=1 ORDER BY name ASC";
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
}

/* POS Header Bar */
.pos-header-bar {
    background: linear-gradient(135deg, #0d9488 0%, #047857 100%);
    color: white;
    padding: 10px 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.pos-header-bar select {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 8px 12px;
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
    gap: 8px;
}

.pos-header-bar .nav-links a {
    color: white;
    padding: 6px 12px;
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

/* Search Bar */
.search-bar {
    padding: 12px 16px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
}

.search-bar input {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.2s;
    background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2394a3b8' viewBox='0 0 24 24'%3E%3Cpath d='M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z'/%3E%3C/svg%3E") no-repeat 14px center;
    background-size: 20px;
}

.search-bar input:focus {
    outline: none;
    border-color: #0d9488;
    background-color: #fff;
}

/* Category Filter */
.category-filter {
    padding: 10px 16px;
    background: #fff;
    display: flex;
    gap: 8px;
    overflow-x: auto;
    border-bottom: 1px solid #e2e8f0;
    min-height: 50px;
    flex-shrink: 0;
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
    display: flex;
    flex-direction: column;
}

.product-grid {
    flex: 1;
    min-height: 0; /* Important for scroll in flex */
    padding: 16px;
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* 4 Columns for smaller cards */
    gap: 16px;
    align-content: start;
    overflow-y: auto;
}

.product-card {
    background: #fff;
    border-radius: 12px;
    /* overflow: hidden; Removed to ensure button visibility if content expands */
    overflow: visible; 
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
    display: flex;
    flex-direction: column;
    height: auto;
    min-height: 280px; /* Reduced min-height */
    position: relative;
    border-radius: 12px; /* Ensure radius on card */
}

.product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    border-color: #0d9488;
}

.product-card img {
    width: 100%;
    height: 140px; /* Smaller image */
    object-fit: cover;
    background: #f8fafc;
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    image-rendering: -webkit-optimize-contrast; /* Sharpen images */
}

.product-card .info {
    padding: 12px;
}

.product-card .name {
    font-weight: 700;
    font-size: 14px;
    color: #1e293b;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-card .price {
    color: #0d9488;
    font-weight: 800;
    font-size: 16px;
}

.product-card .stock {
    font-size: 11px;
    color: #64748b;
    margin-top: 4px;
}

.product-card .info {
    padding: 12px;
    flex-grow: 1; /* Push button to bottom */
    display: flex;
    flex-direction: column;
}

.product-card .add-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #0d9488 0%, #047857 100%);
    color: white;
    border: none;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: auto; /* Ensure it stays at bottom */
    display: block; /* Ensure visibility */
    border-bottom-left-radius: 9px; /* Bottom radius */
    border-bottom-right-radius: 9px; /* Bottom radius */
}

.product-card .add-btn:hover {
    opacity: 0.9;
}

/* Pagination */
.pagination-wrapper {
    padding: 16px 20px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: center;
    gap: 8px;
}

.pagination-wrapper button {
    padding: 8px 14px;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
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
.customer-section {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
}

.customer-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: #fff;
    border-radius: 10px;
    border: 2px solid #10b981;
}

.customer-box .avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.customer-box .details {
    flex: 1;
    cursor: pointer;
}

.customer-box .name {
    font-weight: 700;
    color: #1e293b;
    font-size: 14px;
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

.cart-header {
    display: grid;
    grid-template-columns: 40px 1fr 90px 110px; /* Fixed spacing */
    gap: 12px; /* Increased gap */
    padding: 10px 12px;
    background: #f8fafc;
    font-weight: 700;
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 10;
}

.cart-item {
    display: grid;
    grid-template-columns: 40px 1fr 90px 110px; /* Matched to header */
    gap: 12px;
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    align-items: center;
    font-size: 13px;
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

/* Totals Section */
.totals-section {
    padding: 12px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.totals-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    font-size: 12px;
}

.totals-row.total {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    border-top: 2px solid #e2e8f0;
    margin-top: 8px;
    padding-top: 10px;
}

.totals-row input {
    width: 70px;
    text-align: right;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 4px 8px;
    font-weight: 600;
    font-size: 12px;
    background: #10b981;
    color: white;
    -moz-appearance: textfield; /* Hide arrows Firefox */
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
    gap: 12px;
    align-items: start; /* Align to top */
}

.totals-row .input-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.totals-row label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.totals-row input {
    width: 100%; /* Full width within grid cell */
    text-align: right;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px; /* Slightly larger padding */
    font-weight: 600;
    font-size: 13px;
    background: #10b981;
    color: white;
    -moz-appearance: textfield; 
}

/* POS Footer - Updated to match Reference */
.pos-footer {
    padding: 20px;
    background: #0d9488; /* Teal Background */
    display: flex;
    flex-direction: column;
    gap: 16px;
    flex-shrink: 0;
    border-top: 1px solid #115e59;
}

.pos-footer .total-display {
    text-align: center;
    color: white;
    margin-bottom: 4px;
}

.pos-footer .total-display .label {
    font-size: 11px;
    opacity: 0.6;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.pos-footer .total-display .amount {
    font-size: 36px;
    font-weight: 800;
    line-height: 1;
    color: #fff;
}

.pos-footer .footer-controls {
    display: grid;
    grid-template-columns: 1fr 0.8fr 1.2fr; /* Date, Hold, Pay */
    gap: 10px;
}

.pos-footer .date-input {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 12px;
    border-radius: 8px;
    font-size: 14px;
    width: 100%;
    margin-left: 0;
}

.pos-footer .date-input::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
    cursor: pointer;
}

.pos-footer .pay-btn {
    padding: 12px;
    background: #fff; /* White bg for contrast on Teal */
    color: #0d9488; /* Teal text */
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    width: 100%;
}

.pos-footer .pay-btn:hover {
    background: #059669;
}

.pos-footer .hold-btn {
    padding: 12px;
    background: #475569;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
}

.pos-footer .hold-btn:hover {
    background: #334155;
}
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    white-space: nowrap;
}

.pos-footer .hold-btn:hover {
    background: #64748b;
}

/* Modal Styles - Matching App Design */
.pos-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
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

/* Responsive Design */
@media (max-width: 1200px) {
    .pos-right {
        width: 340px;
    }
}

@media (max-width: 1024px) {
    .pos-container {
        flex-direction: column;
    }
    
    .pos-right {
        width: 100%;
        height: 60vh;
        border-left: none;
        border-top: 1px solid #e2e8f0;
    }
    
    .pos-left {
        height: 40vh;
    }
    
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 16px;
        padding: 16px;
    }
}

@media (max-width: 768px) {
    .pos-header-bar {
        padding: 8px 12px;
        gap: 8px;
    }
    
    .pos-header-bar select {
        min-width: 140px;
        font-size: 12px;
        padding: 6px 10px;
    }
    
    .pos-header-bar .nav-links a {
        padding: 6px 10px;
        font-size: 11px;
    }
    
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 12px;
        padding: 12px;
    }
    
    .product-card img {
        height: 100px;
    }
    
    .product-card .info {
        padding: 10px;
    }
    
    .product-card .name {
        font-size: 12px;
    }
    
    .product-card .price {
        font-size: 14px;
    }
    
    .cart-header,
    .cart-item {
        grid-template-columns: 40px 1fr 70px 80px;
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
        font-size: 18px;
    }
    
    .pos-footer .pay-btn,
    .pos-footer .hold-btn {
        padding: 8px 12px;
        font-size: 11px;
    }
    
    .pos-footer .date-input {
        padding: 6px 8px;
        font-size: 11px;
    }
}

@media (max-width: 480px) {
    .pos-header-bar {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .pos-header-bar select {
        flex: 1;
        min-width: 100%;
    }
    
    .pos-header-bar .nav-links {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        padding: 10px;
    }
    
    .product-card img {
        height: 80px;
    }
    
    .cart-header,
    .cart-item {
        grid-template-columns: 35px 1fr 60px 70px;
        font-size: 10px;
        padding: 6px 8px;
    }
    
    .pos-footer {
        flex-wrap: wrap;
    }
    
    .pos-footer .total-display {
        width: 100%;
        text-align: center;
        margin-bottom: 8px;
    }
    
    .pos-footer .footer-right {
        width: 100%;
        justify-content: center;
    }
}
    /* color: #0d9488;
} */

/* --- RESPONSIVE MEDIA QUERIES --- */

/* Large Tablets / Small Laptops */
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

/* Mobile Devices */
@media (max-width: 768px) {
    /* Main Container Stacking */
    .pos-container {
        flex-direction: column;
        height: auto;
        overflow-y: auto;
    }

    /* Left Section (Products) */
    .pos-left {
        width: 100%;
        height: auto;
        flex: none;
        overflow: visible;
    }

    /* Right Section (Cart) */
    .pos-right {
        width: 100%;
        height: auto;
        border-left: none;
        border-top: 1px solid #e2e8f0;
        flex: none;
        order: 2; /* Cart below products */
    }

    /* Header Adjustments */
    .pos-header-bar {
        padding: 10px;
        justify-content: space-between;
    }
    .pos-header-bar .nav-links {
        display: none; /* Hide nav links on mobile to save space */
    }
    
    /* Search Bar */
    .search-bar input {
        border-radius: 8px;
    }

    /* Product Grid */
    .product-grid-wrapper {
        height: 500px; /* Fixed height for scroll area on mobile */
    }
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        padding: 10px;
        gap: 10px;
        overflow-y: auto;
    }
    .product-card {
        min-height: 240px;
    }
    .product-card img {
        height: 120px;
    }

    /* Cart Section */
    .cart-section {
        max-height: 300px; /* Limit cart height */
    }

    /* Footer */
    .pos-footer {
        padding: 15px;
    }
    
    /* Sidebar Overrides if not hidden */
    #sidebar {
        display: none; /* Completely hide sidebar on mobile POS for max space */
    }
    #main-content {
        margin-left: 0 !important;
    }
}

/* Small Mobile */
@media (max-width: 480px) {
    .product-grid {
        grid-template-columns: 1fr; /* 1 col for very small screens */
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

</style>


<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300 overflow-hidden">
        <div class="navbar-fixed-top">
        </div>
        
        <div class="pos-container">
            <!-- Left Panel: Products -->
            <div class="pos-left">
                <!-- POS Header Bar -->
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
                        <a href="#"><i class="fas fa-file-invoice"></i> INVOICE</a>
                    </div>
                    
                    <div class="nav-links">
                        <a href="#" onclick="openModal('addProductModal')"><i class="fas fa-plus"></i> Product</a>
                        <a href="#" onclick="openModal('addCustomerModal')"><i class="fas fa-user-plus"></i> Customer</a>
                        <a href="#" onclick="openModal('giftcardModal')"><i class="fas fa-gift"></i> Giftcard</a>
                    </div>
                </div>
                
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
                    <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                        <div class="product-card" data-id="<?= $product['id']; ?>" data-name="<?= htmlspecialchars($product['product_name']); ?>" data-price="<?= $product['selling_price']; ?>" data-category="<?= $product['category_id']; ?>">
                            <?php if(!empty($product['thumbnail'])): ?>
                                <img src="<?= $product['thumbnail']; ?>" alt="<?= htmlspecialchars($product['product_name']); ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/120x80?text=No+Image" alt="No Image">
                            <?php endif; ?>
                            <div class="info">
                                <div class="name"><?= htmlspecialchars($product['product_name']); ?></div>
                                <div class="price">৳<?= number_format($product['selling_price'], 2); ?></div>
                                <div class="stock">Stock: <?= $product['opening_stock']; ?> <?= $product['unit_name'] ?? 'pcs'; ?></div>
                            </div>
                            <button class="add-btn" onclick="addToCart(<?= $product['id']; ?>, '<?= htmlspecialchars($product['product_name'], ENT_QUOTES); ?>', <?= $product['selling_price']; ?>)">
                                <i class="fas fa-cart-plus"></i> Add To Cart
                            </button>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <div class="pagination-wrapper" id="pagination-controls">
                    <?php if($page_no > 1): ?>
                        <button onclick="loadProducts(<?= $page_no - 1; ?>)">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                    <?php else: ?>
                        <button disabled><i class="fas fa-chevron-left"></i> Previous</button>
                    <?php endif; ?>
                    
                    <span style="padding: 8px 14px; font-weight: 600; color: #64748b;">
                        Page <?= (int)$page_no; ?> of <?= (int)$total_pages; ?>
                    </span>
                    
                    <?php if($page_no < $total_pages): ?>
                        <button onclick="loadProducts(<?= $page_no + 1; ?>)">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    <?php else: ?>
                        <button disabled>Next <i class="fas fa-chevron-right"></i></button>
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
                        <i class="fas fa-pen" style="color: #10b981; cursor: pointer; padding: 8px;" onclick="openWalkingCustomerModal()"></i>
                        <button type="button" onclick="openModal('addCustomerModal')" style="background: #10b981; color: white; border: none; padding: 8px 10px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <input type="hidden" id="selected_customer_id" value="">
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
                        <button class="hold-btn" onclick="openModal('holdModal')">
                            <i class="fas fa-pause"></i> HOLD
                        </button>
                        <button class="pay-btn" onclick="openModal('paymentModal')">
                            <i class="fas fa-credit-card"></i> PAY NOW
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Payment Modal -->
<div class="pos-modal" id="paymentModal">
    <div class="pos-modal-content">
        <div class="pos-modal-header">
            <h3><i class="fas fa-credit-card"></i> Payment</h3>
            <button class="close-btn" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <div class="pos-modal-body">
            <div class="payment-methods">
                <?php 
                mysqli_data_seek($payment_methods_result, 0);
                while($pm = mysqli_fetch_assoc($payment_methods_result)): 
                    $icon = 'fa-money-bill';
                    if(strtolower($pm['code']) == 'cash') $icon = 'fa-money-bill-wave';
                    elseif(strtolower($pm['code']) == 'card') $icon = 'fa-credit-card';
                    elseif(strtolower($pm['code']) == 'bank') $icon = 'fa-university';
                    elseif(strtolower($pm['code']) == 'bkash' || strtolower($pm['code']) == 'mobile') $icon = 'fa-mobile-alt';
                ?>
                    <div class="payment-method" data-id="<?= $pm['id']; ?>" onclick="selectPaymentMethod(this, <?= $pm['id']; ?>)">
                        <i class="fas <?= $icon; ?>"></i>
                        <span><?= htmlspecialchars($pm['name']); ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="payment-summary">
                <div class="payment-row">
                    <span>Subtotal</span>
                    <span id="payment-subtotal">৳0.00</span>
                </div>
                <div class="payment-row">
                    <span>Discount</span>
                    <span id="payment-discount">-৳0.00</span>
                </div>
                <div class="payment-row">
                    <span>Tax</span>
                    <span id="payment-tax">+৳0.00</span>
                </div>
                <div class="payment-row">
                    <span>Shipping</span>
                    <span id="payment-shipping">+৳0.00</span>
                </div>
                <div class="payment-row grand-total">
                    <span>Grand Total</span>
                    <span id="payment-total">৳0.00</span>
                </div>
            </div>
            
            <div class="payment-input-group">
                <label>Amount Received</label>
                <input type="number" id="amount-received" placeholder="0.00" oninput="calculateChange()">
            </div>
            
            <div class="change-display">
                <div class="label">Change</div>
                <div class="amount" id="change-amount">৳0.00</div>
            </div>
            
            <button class="complete-btn" onclick="completeSale()">
                <i class="fas fa-check-circle"></i> Complete Sale
            </button>
        </div>
    </div>
</div>

<!-- Hold Order Modal -->
<div class="pos-modal" id="holdModal">
    <div class="pos-modal-content" style="max-width: 450px;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-pause-circle"></i> Hold Order</h3>
            <button class="close-btn" onclick="closeModal('holdModal')">&times;</button>
        </div>
        <div class="pos-modal-body">
            <div class="payment-input-group">
                <label>Hold Note / Reference</label>
                <textarea id="hold-note" rows="3" style="resize: none;" placeholder="Enter a note for this held order..."></textarea>
            </div>
            <button class="complete-btn" style="background: #f59e0b;" onclick="holdOrder()">
                <i class="fas fa-pause"></i> Hold Order
            </button>
        </div>
    </div>
</div>

<!-- Customer Select Modal -->
<div class="pos-modal" id="customerSelectModal">
    <div class="pos-modal-content" style="max-width: 500px;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-users"></i> Select Customer</h3>
            <button class="close-btn" onclick="closeModal('customerSelectModal')">&times;</button>
        </div>
        <div class="pos-modal-body">
            <input type="text" id="customer-search-input" placeholder="Search customer by name or phone..." style="margin-bottom: 16px;">
            <div id="customer-list" style="max-height: 300px; overflow-y: auto;">
                <div class="customer-option" onclick="selectCustomer(0, 'Walking Customer', '0170000000000')" style="padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 8px; cursor: pointer;">
                    <strong>Walking Customer</strong>
                    <span style="color: #64748b; font-size: 13px; margin-left: 8px;">0170000000000</span>
                </div>
                <?php 
                mysqli_data_seek($customers_result, 0);
                while($customer = mysqli_fetch_assoc($customers_result)): 
                ?>
                    <div class="customer-option" onclick="selectCustomer(<?= $customer['id']; ?>, '<?= htmlspecialchars($customer['name'], ENT_QUOTES); ?>', '<?= $customer['mobile']; ?>')" style="padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 8px; cursor: pointer;">
                        <strong><?= htmlspecialchars($customer['name']); ?></strong>
                        <span style="color: #64748b; font-size: 13px; margin-left: 8px;"><?= $customer['mobile']; ?></span>
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
            <button class="close-btn" onclick="closeModal('walkingCustomerModal')">&times;</button>
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
            <button class="close-btn" onclick="closeModal('addCustomerModal')">&times;</button>
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

                <button type="submit" class="complete-btn">
                    <i class="fas fa-save"></i> Save Customer
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<!-- Add Product Modal -->
<div class="pos-modal" id="addProductModal">
    <div class="pos-modal-content" style="max-width: 900px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-box"></i> Quick Add Product</h3>
            <button class="close-btn" onclick="closeModal('addProductModal')" style="cursor: pointer; z-index: 100; font-size: 24px; color: #ef4444;">&times;</button>
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
            <h3><i class="fas fa-gift"></i> Add Gift Card</h3>
            <button class="close-btn" onclick="closeModal('giftcardModal')">&times;</button>
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
                    <button type="button" onclick="closeModal('giftcardModal')" style="padding: 12px 24px; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
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
let selectedPaymentMethod = null;

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

// Load Products via AJAX
function loadProducts(page) {
    const grid = document.querySelector('.product-grid');
    grid.style.opacity = '0.5';
    
    // Get selected store
    const storeId = document.getElementById('store_select').value;
    
    // Prevent default button behavior
    if(event) event.preventDefault();

    fetch('get_products.php?page=' + page + '&store_id=' + storeId)
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

// Add to cart
function addToCart(id, name, price) {
    const existingItem = cart.find(item => item.id === id);
    
    if(existingItem) {
        existingItem.qty++;
    } else {
        cart.push({
            id: id,
            name: name,
            price: parseFloat(price),
            qty: 1
        });
    }
    
    renderCart();
    showToast('Added to cart: ' + name);
}

// Update quantity
function updateQty(id, change) {
    const item = cart.find(item => item.id === id);
    if(item) {
        item.qty += change;
        if(item.qty <= 0) {
            removeFromCart(id);
        } else {
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

// Set quantity directly
function setQty(id, qty) {
    const item = cart.find(item => item.id === id);
    if(item) {
        item.qty = parseInt(qty) || 1;
        if(item.qty <= 0) {
            removeFromCart(id);
        } else {
            renderCart();
        }
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
function selectPaymentMethod(element, id) {
    document.querySelectorAll('.payment-method').forEach(pm => pm.classList.remove('selected'));
    element.classList.add('selected');
    selectedPaymentMethod = id;
}

function updatePaymentSummary() {
    let subtotal = 0;
    cart.forEach(item => {
        subtotal += item.price * item.qty;
    });
    
    const discount = parseFloat(document.getElementById('discount-input').value) || 0;
    const taxPercent = parseFloat(document.getElementById('tax-input').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping-input').value) || 0;
    const other = parseFloat(document.getElementById('other-input').value) || 0;
    
    const taxAmount = (subtotal - discount) * (taxPercent / 100);
    const grandTotal = subtotal - discount + taxAmount + shipping + other;
    
    document.getElementById('payment-subtotal').textContent = '৳' + subtotal.toFixed(2);
    document.getElementById('payment-discount').textContent = '-৳' + discount.toFixed(2);
    document.getElementById('payment-tax').textContent = '+৳' + taxAmount.toFixed(2);
    document.getElementById('payment-shipping').textContent = '+৳' + (shipping + other).toFixed(2);
    document.getElementById('payment-total').textContent = '৳' + grandTotal.toFixed(2);
}

function calculateChange() {
    const received = parseFloat(document.getElementById('amount-received').value) || 0;
    const grandTotal = parseFloat(document.getElementById('payment-total').textContent.replace('৳', '').replace(',', '')) || 0;
    const change = received - grandTotal;
    document.getElementById('change-amount').textContent = '৳' + (change >= 0 ? change.toFixed(2) : '0.00');
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

// Complete sale
function completeSale() {
    if(cart.length === 0) {
        showToast('Cart is empty!', 'error');
        return;
    }
    
    if(!selectedPaymentMethod) {
        showToast('Please select a payment method', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'process_payment');
    formData.append('cart', JSON.stringify(cart));
    formData.append('customer_id', document.getElementById('selected_customer_id').value || 0);
    formData.append('store_id', document.getElementById('store_select').value);
    formData.append('payment_method_id', selectedPaymentMethod);
    formData.append('discount', document.getElementById('discount-input').value || 0);
    formData.append('tax_percent', document.getElementById('tax-input').value || 0);
    formData.append('shipping', document.getElementById('shipping-input').value || 0);
    formData.append('other_charge', document.getElementById('other-input').value || 0);
    formData.append('amount_received', document.getElementById('amount-received').value || 0);
    formData.append('sale_date', document.getElementById('sale-date').value);
    
    fetch('/pos/pos/save_sale.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast('Sale completed successfully!', 'success');
            cart = [];
            renderCart();
            closeModal('paymentModal');
            document.getElementById('amount-received').value = '';
            document.getElementById('change-amount').textContent = '৳0.00';
            selectedPaymentMethod = null;
            document.querySelectorAll('.payment-method').forEach(pm => pm.classList.remove('selected'));
        } else {
            showToast(data.message || 'Error processing sale', 'error');
        }
    })
    .catch(error => {
        showToast('Error processing sale', 'error');
        console.error(error);
    });
}

// Hold order
function holdOrder() {
    if(cart.length === 0) {
        showToast('Cart is empty!', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'hold_order');
    formData.append('cart', JSON.stringify(cart));
    formData.append('customer_id', document.getElementById('selected_customer_id').value || 0);
    formData.append('store_id', document.getElementById('store_select').value);
    formData.append('note', document.getElementById('hold-note').value);
    
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
            closeModal('holdModal');
            document.getElementById('hold-note').value = '';
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
function selectCustomer(id, name, phone) {
    document.getElementById('selected_customer_id').value = id;
    document.getElementById('selected-customer-name').textContent = name;
    document.getElementById('selected-customer-phone').textContent = phone;
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

// Product search
document.getElementById('product_search').addEventListener('input', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.dataset.name.toLowerCase();
        if(name.includes(search) || search === '') {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// Category filter
document.querySelectorAll('.category-filter button').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.category-filter button').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const category = this.dataset.category;
        document.querySelectorAll('.product-card').forEach(card => {
            if(category === 'all' || card.dataset.category === category) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
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

    // Initialize status toggle for modal
    initStatusToggle('modal-giftcard-status-card', 'modal-giftcard-status-toggle', 'modal-giftcard-status-input', 'modal-giftcard-status-label');
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
</script>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<?php include('../includes/footer.php'); ?>
