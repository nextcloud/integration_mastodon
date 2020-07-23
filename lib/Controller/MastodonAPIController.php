<?php
/**
 * Nextcloud - mastodon
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Mastodon\Controller;

use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\AppFramework\Http\DataDisplayResponse;

use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\IL10N;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;

use OCP\AppFramework\Http\ContentSecurityPolicy;

use OCP\ILogger;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

use OCA\Mastodon\Service\MastodonAPIService;

class MastodonAPIController extends Controller {


    private $userId;
    private $config;
    private $dbconnection;
    private $dbtype;

    public function __construct($AppName,
                                IRequest $request,
                                IServerContainer $serverContainer,
                                IConfig $config,
                                IL10N $l10n,
                                IAppManager $appManager,
                                IAppData $appData,
                                ILogger $logger,
                                MastodonAPIService $mastodonAPIService,
                                $userId) {
        parent::__construct($AppName, $request);
        $this->userId = $userId;
        $this->l10n = $l10n;
        $this->appData = $appData;
        $this->serverContainer = $serverContainer;
        $this->config = $config;
        $this->logger = $logger;
        $this->mastodonAPIService = $mastodonAPIService;
        $this->accessToken = $this->config->getUserValue($this->userId, 'mastodon', 'token', '');
        $this->mastodonUrl = $this->config->getUserValue($this->userId, 'mastodon', 'url', '');
    }

    /**
     * get notification list
     * @NoAdminRequired
     */
    public function getMastodonUrl() {
        return new DataResponse($this->mastodonUrl);
    }

    /**
     * get notification list
     * @NoAdminRequired
     */
    public function declareApp($redirect_uris = '') {
        $result = $this->mastodonAPIService->declareApp($this->mastodonUrl, $redirect_uris);
        if (is_array($result)) {
            // we save the client ID and secret and give the client ID back to the UI
            $this->config->setUserValue($this->userId, 'mastodon', 'client_id', $result['client_id']);
            $this->config->setUserValue($this->userId, 'mastodon', 'client_secret', $result['client_secret']);
            $data = [
                'client_id' => $result['client_id']
            ];
            $response = new DataResponse($data);
        } else {
            $response = new DataResponse($result, 401);
        }
        return $response;
    }

    /**
     * get mastodon user avatar
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getMastodonAvatar($url) {
        return new DataDisplayResponse($this->mastodonAPIService->getMastodonAvatar($url));
    }

    /**
     * get notification list
     * @NoAdminRequired
     */
    public function getNotifications($since = null) {
        if ($this->accessToken === '') {
            return new DataResponse($result, 400);
        }
        $result = $this->mastodonAPIService->getNotifications($this->mastodonUrl, $this->accessToken, $since);
        if (is_array($result)) {
            $response = new DataResponse($result);
        } else {
            $response = new DataResponse($result, 401);
        }
        return $response;
    }

}
