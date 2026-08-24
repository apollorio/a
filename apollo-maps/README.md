# Apollo Maps

# Leaflet-powered explorer for Apollo events + locs

## Dependencies

- Apollo Core (required)
- Apollo Events (for event geo via _event_loc_id)
- Apollo Loc (for _local_lat/_local_lng)

## Installation

1. Ensure Apollo Core is installed and activated
2. Upload `apollo-maps` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress

## File Structure

```
apollo-maps/
├── apollo-maps.php        # Main plugin file
├── composer.json          # Composer configuration
├── uninstall.php          # Cleanup on deletion
├── assets/
│   ├── css/
│   │   └── apollo-maps.css
│   └── js/
│       └── apollo-maps.js
├── includes/
│   ├── constants.php      # Plugin constants
│   └── functions.php      # Helper functions
├── src/
│   ├── Plugin.php         # Main plugin class
│   ├── Activation.php     # Activation handler
│   ├── Deactivation.php   # Deactivation handler
│   ├── API/               # REST controllers
│   └── Shortcodes/        # Shortcodes
├── templates/             # Template files
└── languages/             # Translation files
```

## Hooks

### Actions

- `apollo_maps_init` - Fires after plugin initialization

### Filters

- `apollo_maps_config` - Filter plugin configuration

## REST API

Namespace: `apollo/v1`

- `GET /map/explorer` — Geo feed of events (with loc coordinates)

## License

Proprietary - Apollo::Rio
