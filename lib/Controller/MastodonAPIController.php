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

use Psr\Log\LoggerInterface;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

use OCA\Mastodon\Service\MastodonAPIService;
use OCA\Mastodon\AppInfo\Application;

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
								LoggerInterface $logger,
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
		$this->accessToken = $this->config->getUserValue($this->userId, Application::APP_ID, 'token', '');
		$this->mastodonUrl = $this->config->getUserValue($this->userId, Application::APP_ID, 'url', '');
	}

	/**
	 * get notification list
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function getMastodonUrl(): DataResponse {
		return new DataResponse($this->mastodonUrl);
	}

	/**
	 * get notification list
	 * @NoAdminRequired
	 *
	 * @param string $redirect_uris
	 * @return DataResponse
	 */
	public function declareApp(string $redirect_uris = ''): DataResponse {
		$result = $this->mastodonAPIService->declareApp($this->mastodonUrl, $redirect_uris);
		if (is_array($result)) {
			// we save the client ID and secret and give the client ID back to the UI
			$this->config->setUserValue($this->userId, Application::APP_ID, 'client_id', $result['client_id']);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'client_secret', $result['client_secret']);
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
	 *
	 * @param string $url
	 * @return DataDisplayResponse
	 */
	public function getMastodonAvatar(string $url): DataDisplayResponse {
		$response = new DataDisplayResponse($this->mastodonAPIService->getMastodonAvatar($url));
		$response->cacheFor(60*60*24);
		return $response;
	}

	/**
	 * get home timeline
	 * @NoAdminRequired
	 *
	 * @param ?int $since
	 * @return DataResponse
	 */
	public function getHomeTimeline(?int $since = null): DataResponse {
		if ($this->accessToken === '') {
			return new DataResponse(null, 400);
		}
		$result = $this->mastodonAPIService->getHomeTimeline($this->mastodonUrl, $this->accessToken, $since);
		if (!isset($result['error'])) {
			$response = new DataResponse($result);
		} else {
			$response = new DataResponse($result, 401);
		}
		return $response;
	}

	/**
	 * get notification list
	 * @NoAdminRequired
	 *
	 * @param ?int $since
	 * @return DataResponse
	 */
	public function getNotifications(?int $since = null): DataResponse {
		if ($this->accessToken === '') {
			return new DataResponse(null, 400);
		}
		$result = $this->mastodonAPIService->getNotifications($this->mastodonUrl, $this->accessToken, $since);
		if (!isset($result['error'])) {
			$response = new DataResponse($result);
		} else {
			$response = new DataResponse($result, 401);
		}
		return $response;
	}

}
