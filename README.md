# Modern PHP POS System

A professional Point of Sale (POS) system built with PHP and Tailwind CSS.

## Prerequisites

To run this project, you need the following tools installed:
- **PHP 7.4+** (MAMP, XAMPP, or Laragon)
- **MySQL/MariaDB**
- **Node.js & NPM** (Required only for customizing Tailwind CSS styles)

## Setup and Installation

1. Clone or download the project repository.
2. Set up your database (check `config/dbcon.php` for database configuration).
3. Open your terminal in the project directory and run the following command to install Tailwind CSS dependencies:

```bash
npm install
```

## Tailwind CSS Usage

This project uses a local Tailwind CSS build process. To modify designs or add new Tailwind classes, follow these commands:

### 1. Development Mode
Run this command while editing code to automatically update styles:
```bash
npm run dev
```

### 2. Production Build
Run this command before deploying to a live server to generate minified and optimized CSS:
```bash
npm run build
```

## Key Project Files
- `assets/css/input.css`: The source CSS file containing Tailwind directives.
- `assets/css/output.css`: The final generated CSS file used by the application.
- `tailwind.config.js`: Configuration file for Tailwind CSS content and theme.
