# shopify_sync_theme

This is a script that is intended to live in the root directory of a local Shopify theme
Run this script from a browser to update your local theme with any edits or new files
that were uploaded/modified from the Shopify control panel. It is not intended to replace
the Theme-Kit app, but rather run alongside, as this script, when compared with the Theme-
Kit `download` function checks against a timestamp and only downloads those files changed 
since the last execution rather than the *entire fucking theme*.

The only requirement is you add a file called `last_sync.html` with a value of 0 in your
Shopify assets folder. The first run of the script will take a while, so you could mess
with the initial timestamp to not update the entire theme.

