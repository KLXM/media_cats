# REDAXO media_cats AddOn

The media_cats AddOn is a REDAXO CMS extension that allows safe management and reorganization of media categories within the REDAXO media pool. It provides the ability to move media categories (which is not possible in standard REDAXO) with built-in backup and cycle detection functionality.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Repository Structure and Dependencies
- **AddOn Type**: REDAXO CMS AddOn (PHP 8.1+ framework extension)
- **Cannot run standalone**: Requires full REDAXO CMS installation to function
- **No build system**: Uses REDAXO's internal AddOn loading mechanism
- **No external dependencies**: Only requires REDAXO 5.18.1+ and PHP 8.1+

### Required Environment
- PHP 8.1+ with PDO and PDO_MySQL extensions
- REDAXO CMS 5.18.1+ installation (for functional testing)
- Web server environment (Apache/Nginx) for REDAXO

### Validation Commands (ALWAYS run these before committing)
- **PHP Syntax Check All Files**: `find . -name "*.php" -exec php -l {} \;` -- takes 0.33 seconds. Set timeout to 10 seconds. NEVER CANCEL.
- **PHP Syntax Check Single File**: `php -l [filepath]` -- takes <0.05 seconds. Set timeout to 5 seconds.
- **Validate Pages Directory**: `find pages/ -name "*.php" -exec php -l {} \;` -- takes <0.2 seconds. Set timeout to 5 seconds.

### File Structure Overview
```
/home/runner/work/media_cats/media_cats/
├── boot.php                  # AddOn initialization and asset loading
├── install.php              # AddOn installation script
├── package.yml              # AddOn configuration and dependencies
├── lib/CategoryManager.php   # Main functionality class
├── pages/                   # Admin interface pages
│   ├── categories.php       # Category management interface
│   ├── backups.php         # Backup management interface
│   ├── info.php            # Information page
│   └── index.php           # Default page
├── assets/                  # CSS and JavaScript files
│   ├── media_cats.css      # Styling
│   └── media_cats.js       # Frontend functionality
└── lang/                   # Language files
    ├── de_de.lang          # German translations
    └── en_gb.lang          # English translations
```

## Testing and Validation

### What You CAN Validate
- **PHP Syntax**: Always run `find . -name "*.php" -exec php -l {} \;` before committing (0.33 seconds)
- **File Structure**: Verify all required REDAXO AddOn files are present (package.yml, boot.php, install.php)
- **Code Style**: Follow existing PHP code style in the repository
- **Language Files**: Ensure all language keys have translations in both de_de.lang and en_gb.lang
- **AddOn Configuration**: Validate package.yml syntax and structure
- **Asset Files**: Check that CSS and JS files are properly referenced in boot.php

### What You CANNOT Validate (without REDAXO installation)
- **Functional Testing**: Cannot test actual media category operations
- **Database Operations**: Cannot test backup/restore functionality
- **User Interface**: Cannot load admin interface pages
- **REDAXO Integration**: Cannot test framework integration

### Manual Validation Scenarios (requires REDAXO installation)
If you have access to a REDAXO installation, test these scenarios:
1. **Category Movement**: Create a test category, move it to a different parent, verify the change persists
2. **Backup Creation**: Create a backup, verify the JSON file is created in addon_data/media_cats/backups/
3. **Cycle Prevention**: Attempt to create a circular dependency, verify it is blocked
4. **Restore Functionality**: Restore a backup, verify categories are restored correctly

## Common Development Tasks

### Adding New Language Keys
1. Add key to both `lang/de_de.lang` and `lang/en_gb.lang`
2. Use in PHP with `rex_i18n::msg('key_name')`
3. Always validate syntax: `php -l lang/de_de.lang` and `php -l lang/en_gb.lang`

### Modifying CategoryManager Class
1. Edit `lib/CategoryManager.php`
2. Follow existing code patterns (namespace KLXM\MediaCats, use proper exception handling)
3. Always validate syntax: `php -l lib/CategoryManager.php`
4. Test with REDAXO installation if available

### Updating Admin Interface
1. Modify files in `pages/` directory
2. Follow REDAXO fragment pattern for UI consistency
3. Update both PHP backend logic and JavaScript/CSS if needed
4. Validate all PHP files: `find pages/ -name "*.php" -exec php -l {} \;`

### Asset Changes
- **CSS**: Edit `assets/media_cats.css` for styling changes
- **JavaScript**: Edit `assets/media_cats.js` for frontend functionality
- Assets are loaded via `boot.php` only when on media_cats admin pages

## CRITICAL Constraints and Limitations

### Cannot Do Without REDAXO
- **No functional testing**: AddOn requires REDAXO CMS to run
- **No database testing**: Uses REDAXO's database abstraction
- **No UI testing**: Admin interface requires REDAXO backend
- **No end-to-end validation**: Cannot simulate user workflows
- **No dependency resolution**: Cannot validate REDAXO class usage without framework

### Installation and Deployment
- AddOn is installed via REDAXO admin interface, not command line
- Deployment happens through REDAXO's package management or GitHub releases
- Uses GitHub Actions workflow (`.github/workflows/publish-to-redaxo.yml`) for automated publishing
- AddOn data directory created at: `addon_data/media_cats/` during installation

### Development Constraints
- **Cannot test database operations**: All database calls use `rex_sql` which requires REDAXO
- **Cannot validate UI rendering**: Uses `rex_fragment` and `rex_view` for admin interface
- **Cannot test i18n**: Language loading requires REDAXO's `rex_i18n` system
- **Cannot validate cache operations**: Uses `rex_media_cache` which requires REDAXO media system

## Key Files and Their Purposes

### Core Files
- **`boot.php`**: Loads CSS/JS assets when on AddOn pages
- **`install.php`**: Creates required directories during AddOn installation
- **`package.yml`**: Defines AddOn metadata, dependencies, and admin page structure

### Main Functionality
- **`lib/CategoryManager.php`**: Contains all category management logic
  - Backup creation/restoration
  - Category hierarchy validation
  - Cycle detection
  - Database operations

### Admin Interface
- **`pages/categories.php`**: Category management interface with accordion layout
- **`pages/backups.php`**: Backup management with restore/delete functionality  
- **`pages/info.php`**: Displays README content
- **`pages/index.php`**: Default page redirect

## Development Best Practices

### Code Style
- Follow existing namespace pattern: `namespace KLXM\MediaCats;`
- Use REDAXO conventions for database operations (`rex_sql`)
- Implement proper error handling with try/catch blocks
- Use REDAXO's i18n system for all user-facing text

### Security
- Always use CSRF tokens for forms (`rex_csrf_token::factory()`)
- Sanitize user input with `rex_post()` and proper types
- Use `rex_escape()` for output sanitization

### Performance
- Cache operations use REDAXO's `rex_media_cache` system
- Database operations are optimized for single-category updates
- Recursive operations (like path updates) are minimized

## Frequently Referenced Information

### Common rex_post() Parameters
```php
rex_post('save_category', 'boolean')     // Form submission check
rex_post('category_id', 'int', 0)        // Category ID with default
rex_post('category_name', 'string', '')   // Category name
rex_post('parent_id', 'int', 0)          // Parent category ID
```

### REDAXO Database Tables
- `rex_media_category`: Main category table with id, name, parent_id, path columns
- Uses `rex::getTable('media_category')` for table name retrieval

### REDAXO Class Dependencies (cannot mock)
- `rex_sql`: Database operations
- `rex_media_category`: Category object handling
- `rex_media_cache`: Cache management
- `rex_i18n`: Internationalization
- `rex_view`: Admin interface rendering
- `rex_fragment`: Template rendering

Remember: This AddOn extends REDAXO CMS and cannot function independently. Focus validation on PHP syntax and code structure rather than functional testing when working without a REDAXO installation.

## Common Error Patterns and Solutions

### PHP Syntax Issues
- **Missing semicolons**: Always check PHP syntax with `php -l` after editing
- **Namespace issues**: Ensure proper `use` statements for REDAXO classes
- **Array syntax**: Use modern PHP array syntax `[]` not `array()`

### REDAXO-Specific Issues  
- **Table names**: Always use `rex::getTable('media_category')` not direct table names
- **Path handling**: Use `rex_path::addonData()` for data directory paths
- **Permissions**: Use `rex::getDirPerm()` for directory permissions
- **Escaping**: Use `rex_escape()` for output, `rex_post()` for input validation

### Common Development Mistakes
- **Missing CSRF tokens**: All forms must include `$csrfToken->getHiddenField()`
- **Hardcoded strings**: All user-facing text must use `rex_i18n::msg()`
- **Direct database access**: Use `rex_sql` factory, never direct PDO
- **Cache invalidation**: Always call appropriate cache deletion after data changes

### Debugging Without REDAXO
- **Use var_dump/error_log**: Cannot use REDAXO's debug tools without framework
- **Check PHP error logs**: Standard PHP error logging works for syntax issues
- **Validate against existing patterns**: Compare new code to existing working code
- **Test in isolation**: Create minimal PHP scripts to test logic without REDAXO dependencies