<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\UniWueLogin;

use Exception;
use Override;
use Piwik\Access;
use Piwik\Auth;
use Piwik\AuthResult;
use Piwik\Piwik;
use Piwik\Plugins\UsersManager\API;
use Random\Randomizer;

/**
 * Contains the authentication and authorization logic for our Shibboleth-based login.
 */
class UniWueAuth implements Auth
{
    public const int PASSWORD_BYTES_LENGTH = 50;
    
    private const string GROUP_USERS = 'cn=42-Webstats-User,ou=groups,o=uni-wuerzburg';
    private const string GROUP_ADMINS = 'cn=42-Webstats-Admin,ou=groups,o=uni-wuerzburg';

    private ?string $login = null;
    private ?string $tokenAuth = null;

    private API $api;

    public function __construct() {
        $this->api = API::getInstance();
    }

    /* auth logic */

    /**
     * Performs the authentication and authorization of the current user by:
     * - checking the general matomo access
     * - synchronizing the user info from the provided CGI Env Vars from Shibboleth
     * - sets the super user access if eligible
     * - synchronizes the specific site accesses
     *
     * @return AuthResult
     */
    public function authenticate(): AuthResult {
        if (!isset($_SERVER['uid'])) {
            return new AuthResult(AuthResult::FAILURE, '', '');
        }
        
        $this->login = $_SERVER['uid'];
        $userGroups = explode(';', $_SERVER['groupMembership']);

        $this->checkMatomoAccess($userGroups);

        $this->synchronizeUserInfo();
        $hasSuperUserAccess = $this->synchronizeSuperUserAccess($userGroups);
        $this->synchronizeSiteAccess($userGroups);

        $successCode = $hasSuperUserAccess ? AuthResult::SUCCESS_SUPERUSER_AUTH_CODE : AuthResult::SUCCESS;
        return new AuthResult($successCode, $this->login, $this->tokenAuth);
    }

    /**
     * Throws an exception if the user is not allowed to 
     *
     * @param array $userGroups
     * @return void
     */
    private function checkMatomoAccess(array $userGroups): void {
        if (!in_array(self::GROUP_USERS, $userGroups) && !in_array(self::GROUP_ADMINS, $userGroups)) {
            throw new Exception(Piwik::translate('UniWueLogin_NotAuthorized'));
        }
    }

    /**
     * Synchronizes the user info by creating/updating the Matomo user object with the provided
     * CGI Env Variables from Shibboleth.
     * 
     * The user password is filled with a randomly generated password, because we do not have
     * access to the user's password, but it is required by Matomo to have a password set.
     *
     * @return void
     */
    private function synchronizeUserInfo(): void {
        Access::doAsSuperUser(function() {
            try {
                $this->api->updateUser($this->login, null, $_SERVER['mail']);
            } catch (Exception $exc) {
                if ($exc->getMessage() === Piwik::translate("UsersManager_ExceptionUserDoesNotExist", $this->login)) {
                    $password = bin2hex((new Randomizer())->getBytes(self::PASSWORD_BYTES_LENGTH));
                    $this->api->addUser($this->login, $password, $_SERVER['mail']);
                } else {
                    throw $exc;
                }
            }
        });
    }

    /**
     * Synchronizes the super user access by checking whether the user is part
     * of the super user ldap group.
     *
     * @param array $userGroups
     * @return boolean
     */
    private function synchronizeSuperUserAccess(array $userGroups): bool {
        $hasSuperUserAccess = in_array(self::GROUP_ADMINS, $userGroups);

        Access::doAsSuperUser(function() use ($hasSuperUserAccess) {
            $hadSuperUserAccess = (bool)($this->api->getUser($this->login)['superuser_access'] ?? 0);

            if ($hadSuperUserAccess !== $hasSuperUserAccess) {
                $this->api->setSuperUserAccess($this->login, $hasSuperUserAccess);
            }
        });

        return $hasSuperUserAccess;
    }

    /**
     * Synchronizes the specific site access.
     *
     * @param array $userGroups
     * @return void
     */
    private function synchronizeSiteAccess(array $userGroups): void {
        // TODO: implement ldap group site mapping
    }

    /* getters & setters */

    /**
     * @inheritDoc
     *
     * @return string
     */
    #[Override]
    public function getName(): string {
        return 'UniWueLogin';
    }

    /**
     * @inheritDoc
     *
     * @return string|null
     */
    #[Override]
    public function getLogin(): ?string {
        return $this->login;
    }

    /**
     * @inheritDoc
     *
     * @param string|null $login
     * @return void
     */
    #[Override]
    public function setLogin($login): void {
        $this->login = $login;
    }

    /**
     * @inheritDoc
     *
     * @param string|null $tokenAuth
     * @return void
     */
    #[Override]
    public function setTokenAuth($tokenAuth): void {
        $this->tokenAuth = $tokenAuth;
    }

    /**
     * No-OPs
     * - SSO takes place on webserver level before matomo is executed
     * - we don't have access to the user's password
     */

    /**
     * @inheritDoc
     *
     * @param string|null $password
     * @return void
     */
    #[Override]
    public function setPassword($password): void { }

    /**
     * @inheritDoc
     *
     * @param string|null $passwordHash
     * @return void
     */
    #[Override]
    public function setPasswordHash($passwordHash): void { }

    /**
     * @inheritDoc
     *
     * @return string|null
     */
    #[Override]
    public function getTokenAuthSecret(): ?string {
        return null;
    }

}
