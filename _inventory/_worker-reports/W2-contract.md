# W2 CONTRACT

## files-read
- cpts.php: 22360 bytes
- taxonomies.php: 21600 bytes
- meta.php: 17160 bytes
- tables.php: 7106 bytes
- routes.php: 32057 bytes

## core config symbol counts
- cpts keys/symbols: 37
- tax symbols: 75
- meta symbols (capped extract): 137
- tables symbols: 52
- routes symbols: 187

## core CPT-like symbols (sample/full)
```
add_new
add_new_item
apollo_agent_log
apollo_sheet
appointment
archive
classified
doc
edit_item
email_aprio
event
has_archive
hostel
hub
local
map_meta_cap
menu_icon
menu_name
name
new_item
not_found
not_found_in_trash
owner
public
resource
rest_base
rewrite
search_items
service
show_in_rest
show_ui
singular_name
slug
supplier
supports
track
view_item
```

## REG plugin CPT keys collected
```
apollo_sheet
appointment
classified
dj
doc
email_aprio
event
hostel
hub
journal_news
journal_nota
local
resource
service
supplier
track
```

## declared-not-on-disk (REG plugins missing folders)
- apollo-cena
- apollo-classifieds
- apollo-pwa
- apollo-runtime
- apollo-shortcodes
- apollo-suppliers

## on-disk-not-declared (apollo-* dirs absent from REG.plugins)
- apollo-ui

## notes
- Heuristic PHP parse only; Coordinator should treat ambiguous keys as DRIFT not patches.
- No PHP edits made.