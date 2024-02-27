<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UniWueLogin;


/**
 * A controller lets you for example create a page that can be added to a menu. For more information read our guide
 * http://developer.piwik.org/guides/mvc-in-piwik or have a look at the our API references for controller and view:
 * http://developer.piwik.org/api-reference/Piwik/Plugin/Controller and
 * http://developer.piwik.org/api-reference/Piwik/View
 */
class Controller extends \Piwik\Plugin\Controller
{
    /**
     * Redirect to the ShibSP for SLO and afterwards return to Matomo for ShibSP's login screen.
     *
     * @return never
     */
    public function logout(): never
    {
        header('Location: /Shibboleth.sso/Logout?return=' . urlencode('https://' . $_SERVER['HTTP_HOST']));
        exit();
    }
}
