# Cinderwell Popups

Cinderwell Popups creates accessible modal content with the same blocks used throughout a Cinderwell site.

## Create a popup

1. Open **Cinderwell → Popups → Add New Popup**.
2. Use the Popup Content area to add any Cinderwell blocks.
3. Select a manual or automatic trigger.
4. For automatic popups, choose all pages, URL patterns, post types, or specific content. Exclusions take priority.
5. Choose frequency, animation, and width, then publish.

## Trigger a popup

- Select a Cinderwell Button and use its **Popup action** panel.
- Insert a standalone **Popup Trigger** block.
- Call `window.CinderwellPopups.open(popupId)` from custom JavaScript.

Frequency is stored in the visitor's browser and applies to both automatic and manual triggers. Choose **Always** when a visitor should be able to reopen a manual popup without a session limit.

## Admin bar

On the frontend, open **Cinderwell → Popups** to see and edit the popups active
on the current page. Automatic rule matches and manual popup triggers present
in page content are both detected.
