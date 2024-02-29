<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UniWueLogin;

use Piwik\SettingsPiwik;

/**
 * Contains the SLO logic.
 */
class Controller extends \Piwik\Plugin\Controller
{
    /**
     * Redirect to the ShibSP for SLO and returns to Matomo for ShibSP's login screen afterwards.
     *
     * @return never
     */
    public function logout(): never
    {
        header('Location: /Shibboleth.sso/Logout?return=' . urlencode(SettingsPiwik::getPiwikUrl()));
        exit();
    }
}
