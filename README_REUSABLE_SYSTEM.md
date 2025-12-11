# Reusable Design System - Velocity POS

## Overview
This project now uses a modern, reusable design system with consistent components that can be used across all modules.

## File Structure

### Includes Files
- `includes/header.php` - Common header with meta tags, CSS, and JS
- `includes/navbar.php` - Top navigation bar
- `includes/sidebar.php` - Left sidebar navigation menu
- `includes/footer.php` - Footer section with scripts
- `includes/reusable_list.php` - Reusable list component for displaying data tables

## How to Use

### 1. Creating a New Page

```php
<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// Your page logic here
$page_title = "Your Page Title";
include('../includes/header.php');
?>

<div class="flex">
    <?php include('../includes/sidebar.php'); ?>
    
    <main class="flex-1 ml-64 main-content min-h-screen">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="p-6">
            <!-- Your content here -->
        </div>
        
        <?php include('../includes/footer.php'); ?>
    </main>
</div>
```

### 2. Using the Reusable List Component

The reusable list component can be used for any list (currency, users, products, etc.):

```php
<?php
// Fetch your data
$query = "SELECT * FROM your_table ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Configure the list
$list_config = [
    'title' => 'Your List Title',
    'add_url' => '/pos/your_module/add_item.php',
    'table_id' => 'yourTableId',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Name', 'sortable' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $data,
    'edit_url' => '/pos/your_module/add_item.php',
    'delete_url' => '/pos/your_module/save_item.php',
    'status_url' => '/pos/your_module/save_item.php',
    'primary_key' => 'id',
    'name_field' => 'name'
];

include('../includes/reusable_list.php');
renderReusableList($list_config);
?>
```

### 3. Column Types

The reusable list supports different column types:

- **text** (default) - Regular text display
- **status** - Status badge with toggle functionality
- **badge** - Custom badge with custom class
- **date** - Formatted date display
- **currency** - Currency formatted with symbol
- **image** - Image thumbnail display
- **actions** - Action buttons (View, Edit, Delete)

### 4. Example: Currency Module

See `currency/currency_list.php` for a complete example of using the reusable list component.

### 5. Creating CRUD Operations

Follow the pattern used in the currency module:

- `add_currency.php` - Form for adding/editing
- `currency_list.php` - List view using reusable component
- `save_currency.php` - Handles create, update, delete, and status toggle

### 6. Database Table Structure

For the reusable system to work properly, your tables should have:
- `id` - Primary key
- `status` - Status field (0 or 1)
- Other fields as needed

## Features

- ✅ Dark theme design
- ✅ Responsive layout
- ✅ Reusable components
- ✅ DataTables integration
- ✅ SweetAlert2 notifications
- ✅ Consistent styling
- ✅ Easy to extend

## Notes

- Always include `session_start()` before including header.php
- Use relative paths for includes (../includes/)
- Use absolute paths for URLs (/pos/module/file.php)
- The sidebar automatically highlights active menu items
- All forms use the same styling for consistency

