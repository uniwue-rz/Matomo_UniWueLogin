<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UniWueLogin;

use Exception;
use Piwik\Auth;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Request;
use Piwik\View;

class UniWueLogin extends Plugin
{
    public function registerEvents()
    {
        return [
            'Request.initAuthenticationObject' => 'authenticateFE',
            'API.Request.authenticate' => 'authenticateAPI',
            'User.isNotAuthorized' => 'isNotAuthorized',
            'Controller.Login.resetPassword' => 'disablePasswordReset',
            'Controller.Login.confirmResetPassword' => 'disablePasswordReset',
            'UniWueLogin.logout' => 'logout',
            'Login.userRequiresPasswordConfirmation' => 'disablePasswordConfirmation',
            'UsersManager.checkPassword' => 'disablePasswordCheck',
        ];
    }

    /* hooks */

    public function authenticateFE(): void {
        $this->setAuth(new UniWueAuth());
    }

    public function authenticateAPI($tokenAuth): void {
        $auth = $this->getAuth();
        $auth->setLogin(null);
        $auth->setTokenAuth($tokenAuth);
    }

    public function isNotAuthorized(): never {
        $view = new View('@Login/login');
        $view->infoMessage = null;
        $view->formErrors = [Piwik::translate('UniWueLogin_NotAuthorized')];
        echo($view->render());
        exit;
    }

    public function disablePasswordReset(&$parameters): void {
        $login = Request::fromRequest()->getParameter('form_login', false);
        if (empty($login)) {
            return;
        }

        $view = new View('@Login/resetPassword');
        $view->infoMessage = null;
        $view->formErrors = [Piwik::translate('UniWueLogin_DisablePasswordReset')];
        echo($view->render());
        exit;
    }

    public function disablePasswordCheck($password): void {
        // this is a bit hacky, but we need to allow our randomly generated passwords to pass through
        if (strlen($password) !== 2*UniWueAuth::PASSWORD_BYTES_LENGTH) {
            throw new Exception(Piwik::translate('UniWueLogin_DisablePasswordCheck'));
        }
    }

    public function disablePasswordConfirmation(&$requiresPasswordConfirmation, $login): void {
        $requiresPasswordConfirmation = false;
    }

    public function logout(): never {
        header('/Shibboleth.sso/Logout?return=' . urlencode($_SERVER['SERVER_NAME']));
        exit();
    }

    /* utility */

    private function getAuth(): Auth {
        return StaticContainer::get('Piwik\Auth');
    }

    private function setAuth(Auth $auth): void {
        Staticcontainer::getContainer()->set('Piwik\Auth', $auth);
    }
}
