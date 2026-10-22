# Karobar POS — Sales and Inventory

A PHP and MySQL application for managing store products, customers and sales through admin, manager and cashier areas.

## Features

- Product categories and stock management
- Customer and user administration
- Inventory and sales report exports

## Technology

PHP, MySQL, HTML, CSS and JavaScript.

## Local setup

Clone the repository:

```bash
git clone https://github.com/nasirabbas827/pos-sufyan.git
cd pos-sufyan
```

1. Install a local PHP web server and MySQL, such as XAMPP.
2. Place the project in the server's document root, or configure a virtual host pointing to this folder.
3. Import `Database/pos_db.sql` using phpMyAdmin or your MySQL client.
4. Update the database connection in `config.php` and in role-specific copies of `config.php` to match your local database name and credentials.
5. Start Apache and MySQL, then open `http://localhost/pos-sufyan/`.

## Repository layout

| Path | Purpose |
|---|---|
| `Database/` | SQL database export |
| `admin/` | Role-specific pages |
| `cashier/` | Role-specific pages |
| `config.php` | Database connection configuration |
| `index.php` | Web entry page |
| `manager/` | Role-specific pages |

## Development notes

Setup steps describe local development. Configure your own database, mail and API credentials where applicable. Review debug settings and replace development secrets before deploying. The application was not executed as part of this documentation update.

Maintained by [Nasir Abbas](https://github.com/nasirabbas827).
