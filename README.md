# SimpleCloner

Simple Cloner is a module purpose built for Expression Engine 3 which allows users to duplicate channel entries from a tab in the publish layout. Suffixes can be appended to the title and URL title fields in EE to differentiate cloned entries.

## Bloqs

If you are working with Bloqs I have noted that you will receive a PHP warning on PHP 5.6.25.

    Invalid argument supplied for foreach()

A simple fix for this if this issue happens to you:

Navigate to system/user/addons/bloqs/libraries/EEBlocks/Controller/PublishController.php
Line: 502

Change:

    foreach ($data as $row_id => $blockdata)

To:

    foreach ((array) $data as $row_id => $blockdata)
