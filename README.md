# WP Bandit Equipment Plugin

A WordPress plugin that integrates with the Bandit Chippers API to import and display equipment data from [banditchippers.com](https://banditchippers.com).

## Description

The WP Bandit Equipment Plugin allows WordPress site owners to seamlessly import and display Bandit equipment data on their website. The plugin fetches data from the Bandit API, creates custom post types for equipment, and categorizes them by equipment families.

## Features

- Import Bandit equipment data directly into WordPress
- Custom post type for Bandit equipment
- Custom taxonomy for equipment families
- Admin interface for selecting which equipment to import
- Scheduling options for automated imports
- Support for displaying equipment images, specifications, and videos
- Customizable display options

## API Integration

This plugin integrates with the Bandit API available at [banditchippers.com/api/](https://banditchippers.com/api/). The API provides product information that can be requested by ID, listed by category, and paginated as needed.

### API Endpoints Used

- Base URL: `banditchippers.com/wp-json/wp/v2/`
- Individual items: `/project/{item_id}`
- List of items by category: `/project?category={category_id}`
- Available categories: `/project_category`
- Media items: `/media/{media_id}`

### Equipment Categories

The plugin supports the following equipment categories from Bandit:

| ID | Name |
|----|------|
| 7 | Hand-Fed Chippers |
| 8 | Stump Grinders |
| 9 | Skid Steer Attachments |
| 10 | Horizontal Grinders |
| 11 | Whole Tree Chippers |
| 12 | Track Carriers |
| 13 | Slow-Speed Shredders |

## Installation

1. Upload the `wp-bandit-plugin` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Bandit Importer' in the WordPress admin menu
4. Select the equipment you want to import
5. Run the importer

## Usage

### Equipment Selection

1. Navigate to the 'Bandit Importer' > 'Inventory Picklist' in the WordPress admin
2. Browse available equipment by category
3. Select the equipment you want to import
4. Save your selections

### Running the Importer

1. Navigate to the 'Bandit Importer' > 'Importer' tab
2. Click the 'Run Import' button to start the import process
3. The progress bar will display the current status of the import

### Displaying Equipment

The plugin creates a custom post type `bandit_equipment` and a custom taxonomy `bandit_equipment_family` that you can use to display the equipment on your site.

You can:
- Create archive pages for all equipment
- Filter equipment by family
- Display individual equipment details
- Customize the display using WordPress templates

## Customization

### Filters

The plugin offers several filters to customize its behavior:

- `wp_bandit_post_name` - Modify the post type slug
- `wp_bandit_tax_name` - Modify the taxonomy slug
- `wp_bandit_top_level_parent` - Set a parent term for all equipment families
- `wp_bandit_post_type_args` - Modify post type registration arguments
- `wp_bandit_tax_args` - Modify taxonomy registration arguments

### Templates

You can create custom templates for displaying Bandit equipment in your theme:

- `single-bandit_equipment.php` - Template for single equipment pages
- `archive-bandit_equipment.php` - Template for equipment archives
- `taxonomy-bandit_equipment_family.php` - Template for family archives

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Active connection to the internet

## Credits

- Developed by Jacob Stanaford
- Uses the Bandit Chippers API (banditchippers.com/api/)

## License

This plugin is licensed under the GPL v2 or later. 