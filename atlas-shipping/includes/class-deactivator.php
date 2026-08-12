<?php
namespace AtlasShipping;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Deactivator {
    public static function deactivate() {
        // Data, options, activity records, and the application page are intentionally preserved.
    }
}
