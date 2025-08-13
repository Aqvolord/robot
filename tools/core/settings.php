<?php

// for static call of all TOOLS objects example:
// TOOLS::$domhelper->wait_on_element_by_text($tag, $text, $frme, $wait, $pause);
class SETTINGS
{
   public static $settings = null; 
};

// initialization
SETTINGS::$settings = $settingsHelper;
