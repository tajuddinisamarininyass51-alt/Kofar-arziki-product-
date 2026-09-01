<?php
/**
 * Main configuration adjustments: remove deposit/withdrawal constants
 * as Deposit/Withdrawal features are intentionally removed from the project.
 */

require_once __DIR__ . '/config.php';

// Remove deposit/withdrawal related defines if present to avoid accidental use
if (defined('MIN_DEPOSIT')) {
    // intentionally left blank - constants retained for backward compatibility only
}

// If code still references these constants, define safe defaults (unused)
if (!defined('MIN_DEPOSIT')) define('MIN_DEPOSIT', 0);
if (!defined('MAX_DEPOSIT')) define('MAX_DEPOSIT', 0);
if (!defined('MIN_WITHDRAWAL')) define('MIN_WITHDRAWAL', 0);
if (!defined('MAX_WITHDRAWAL')) define('MAX_WITHDRAWAL', 0);
if (!defined('WITHDRAWAL_FEE_PERCENT')) define('WITHDRAWAL_FEE_PERCENT', 0);

?>