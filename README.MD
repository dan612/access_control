# Access Control (Drupal 8)
## This module provides a mechanism to block anonymous users from accessing the website.
---
## Setup
1.) Enable the module
```
drush en access_control
```
2.) Go to admin page for control switch located at:
```
admin/access-control/killswitch
```
3.) Enabling this will lockdown the site against anonymous traffic.

---

## Features

#### Preview content on site
Choose one of your content types to display during lockdown mode. Only shows title of the node.

#### Show ESPN articles
Choose a sport to show headlines from ESPN. Available sports:
- MLB
- NHL
- NBA
- MMA
- Nascar
- Golf
- NFL

---

## Future dev
1. Cloudflare origin control
2. Event tracking during lockdown
3. Tests
 - are there available node types
 - if in lockdown, does the page have X content (content specific to lockdown mode) for anonymous users
 - on subsequent requests is the response delivered from cache



