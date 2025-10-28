<?php

# adjusted from https://github.com/matomo-org/matomo/blob/9db7402e92ce0f35124793d1415475b7f3355aa0/plugins/Login/config/config.php to construct disabled PasswordStrength

use Piwik\Auth\PasswordStrength;

return [
    'Piwik\Auth\PasswordStrength' => Piwik\DI::factory(function () {
        return new PasswordStrength(false);
    }),
];
