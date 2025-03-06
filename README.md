# Alingsås Municipio Customisation

A WordPress plugin providing custom functionality and enhancements for Alingsås kommun's Municipio-based website. This plugin extends the Municipio theme with Alingsås-specific customizations, branding, and functionality.

## Overview

The Alingsås Municipio Customisation plugin is designed to tailor the WordPress Municipio theme specifically for Alingsås kommun's needs. It provides a comprehensive set of customizations that enhance the website's appearance, functionality, and user experience while maintaining consistent branding throughout the site.

## Key Features

### Theme and Appearance Management

- **Custom Color System**: Define and manage custom colors through an admin interface
- **Theme Management**: Create and apply themes with specific colors to different sections of the website
- **Path-based Theming**: Apply themes automatically based on URL paths
- **Page-specific Theming**: Set individual themes for specific pages
- **CSS Variables**: Automatically generates CSS variables for all custom colors and themes
- **Background Stripe Customization**: Control background stripe colors based on themes

### Enhanced Search Functionality

- **Content Type Filtering**: Filter search results by specific content types:
  - Pages
  - News
  - Jobs
  - Events
  - Operating information
- **Custom Search Results Page**: Tailored search results display with:
  - Content type tabs
  - Result counts by type
  - Breadcrumb integration
  - Pagination support

### Events Integration

- **Event Display Options**: Custom display options for event content
- **Event Calendar Integration**: Enhanced integration with event management
- **Event Sorting and Filtering**: Proper sorting and filtering of events
- **Custom Event Templates**: Specialized templates for event display
- **Event Archive Support**: Enhanced event archive pages

### Advanced Custom Fields Integration

- **Custom Field Groups**: Pre-configured ACF field groups for:
  - Appearance settings
  - Card settings
  - Page settings
  - Module settings
  - Noticeboard settings
  - Webcast settings
- **Custom Location Types**: Additional ACF location types for Modularity
- **Field Export Management**: Automated export and import of ACF fields

### Additional Functionality

- **Announcements Management**: Tools for creating and managing site announcements
- **Webcasts Support**: Integration with webcast functionality
- **Translation Enhancements**: Improved translation support
- **Custom Components**: Alingsås-specific components for the Municipio theme
- **Custom Scripts and Styles**: Additional scripts and styles for enhanced functionality
- **Cron Jobs**: Scheduled tasks for maintenance and updates
- **WP All Import Integration**: Enhanced import capabilities

## Technical Implementation

The plugin is structured with a modular approach:

- **Main Plugin Class**: Initializes all components and includes necessary files
- **Includes Directory**: Contains core functionality classes
- **Components Directory**: Houses custom components for the Municipio theme
- **Helpers Directory**: Provides utility functions for various plugin features
- **ACF Directory**: Stores Advanced Custom Fields configurations
- **Views Directory**: Contains Blade templates for custom views
- **Languages Directory**: Holds translation files

## Requirements

- WordPress 5.0+
- Municipio theme
- Advanced Custom Fields Pro (ACF)
- PHP 7.4+

## Version

Current version: 0.1.19

## Author

Developed by Consid (https://www.consid.se) for Alingsås kommun
