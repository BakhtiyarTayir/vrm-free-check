# VRM Check Plugin

A WordPress plugin for checking UK vehicle registration numbers using the Vehicle Data Global API. The plugin provides a user-friendly interface similar to cartaxcheck.co.uk for displaying comprehensive vehicle information.

## Features

- **Vehicle Identity Information**: Registration, make, model, year, color, etc.
- **Legal Checks**: MOT status, tax information, stolen status checks
- **Vehicle Specifications**: Engine details, fuel type, emissions, mileage estimates
- **Responsive Design**: Mobile-friendly interface
- **Caching System**: Reduces API calls and improves performance
- **Admin Settings**: Easy configuration through WordPress admin
- **Shortcode Support**: Easy integration into pages and posts
- **AJAX-powered**: Smooth user experience without page reloads

## Installation

1. Upload the plugin files to `/wp-content/plugins/vrm-check-plugin/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your API settings in the admin panel

## Configuration

### API Setup

1. Go to **Settings > VRM Check** in your WordPress admin
2. Enter your Vehicle Data Global API key
3. Configure the API URL (default is provided)
4. Set cache duration (recommended: 24 hours)

### Getting an API Key

1. Visit [Vehicle Data Global Portal](https://portal.vehicledataglobal.com/)
2. Create an account or log in
3. Navigate to your account dashboard
4. Generate an API key for your application

## Usage

### Shortcode

Use the `[vrm_check]` shortcode to display the VRM check form:

```
[vrm_check]
```

#### Shortcode Parameters

- `title`: Custom title for the form (default: "Free Car Check")
- `placeholder`: Input placeholder text (default: "Enter registration number")
- `button_text`: Submit button text (default: "Check Vehicle")
- `show_example`: Show example VRM button (default: "yes")

Example with custom parameters:
```
[vrm_check title="Vehicle Lookup" button_text="Search Now" show_example="no"]
```

### PHP Integration

You can also use the plugin classes directly in your theme:

```php
use VrmCheckPlugin\ApiClient;

$api_client = new ApiClient();
$result = $api_client->check_vrm('BL66VPO');

if ($result['success']) {
    $vehicle_data = $result['data'];
    // Process vehicle data
}
```

## API Response Structure

The plugin processes and displays the following vehicle information:

### Vehicle Identity
- Registration number
- Make and model
- Year of manufacture
- V5C issue date
- Vehicle color
- Registration date

### Legal Checks
- MOT status and due date
- Tax status and due date
- Stolen status check
- Insurance information

### Vehicle Specifications
- Engine capacity
- Fuel type
- Vehicle type/category
- CO2 emissions
- Euro emissions standard
- Estimated current mileage

## Styling

The plugin includes comprehensive CSS styling that mimics the design of cartaxcheck.co.uk. You can customize the appearance by:

1. **Override CSS**: Add custom styles to your theme's CSS file
2. **Modify Plugin CSS**: Edit `/assets/css/vrm-check-style.css`
3. **Use CSS Classes**: Target specific elements with the provided CSS classes

### Key CSS Classes

- `.vrm-check-container`: Main container
- `.vrm-check-form`: Form wrapper
- `.vrm-input`: VRM input field
- `.vrm-submit-btn`: Submit button
- `.vrm-check-results`: Results container
- `.vrm-section`: Individual result sections

## Caching

The plugin implements intelligent caching to:

- Reduce API calls and costs
- Improve response times
- Handle rate limiting gracefully

Cache duration can be configured in the admin settings (default: 24 hours).

## Error Handling

The plugin includes comprehensive error handling for:

- Invalid VRM formats
- API connection issues
- Rate limiting
- Network timeouts
- Invalid responses

## Security

- **Nonce Verification**: All AJAX requests are protected
- **Input Sanitization**: All user inputs are sanitized
- **SQL Injection Protection**: Uses WordPress prepared statements
- **XSS Prevention**: All outputs are escaped

## Database

The plugin creates a `vrm_check_logs` table to store:

- Search history
- API response caching
- Error logging
- Usage statistics

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Vehicle Data Global API account

## Support

For support and documentation:

1. Check the plugin settings page for configuration help
2. Review the Vehicle Data Global API documentation
3. Contact your system administrator for server-related issues

## Changelog

### Version 1.0.0
- Initial release
- Basic VRM checking functionality
- Admin settings panel
- Shortcode support
- Responsive design
- Caching system

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Vehicle data provided by [Vehicle Data Global](https://vehicledataglobal.com/)
- Design inspired by cartaxcheck.co.uk
- Built for WordPress