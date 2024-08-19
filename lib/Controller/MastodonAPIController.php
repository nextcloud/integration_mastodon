<?php
/**
 * Nextcloud - mastodon
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Mastodon\Controller;

use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IConfig;
use OCP\PreConditionNotMetException;
use Psr\Log\LoggerInterface;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

use OCA\Mastodon\Service\MastodonAPIService;
use OCA\Mastodon\AppInfo\Application;

class MastodonAPIController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig $config,
		private LoggerInterface $logger,
		private MastodonAPIService $mastodonAPIService,
		private ?string $userId
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function getMastodonUrl(): DataResponse {
		return new DataResponse($this->mastodonAPIService->getMastodonUrl($this->userId));
	}

	/**
	 * get notification list
	 *
	 * @param string $redirect_uri
	 * @param string|null $oauth_origin
	 * @return DataResponse
	 * @throws PreConditionNotMetException
	 */
	#[NoAdminRequired]
	public function declareApp(string $redirect_uri, ?string $oauth_origin = null): DataResponse {
		$result = $this->mastodonAPIService->declareApp($this->userId, $redirect_uri);
		if (isset($result['client_id'], $result['client_secret'])) {
			// we save the client ID and secret and give the client ID back to the UI
			$this->config->setUserValue($this->userId, Application::APP_ID, 'redirect_uri', $redirect_uri);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'client_id', $result['client_id']);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'client_secret', $result['client_secret']);
			if ($oauth_origin !== null) {
				$this->config->setUserValue($this->userId, Application::APP_ID, 'oauth_origin', $oauth_origin);
			}
			$data = [
				'client_id' => $result['client_id']
			];
			$response = new DataResponse($data);
		} else {
			$warning = 'Mastodon app declaration error : ' . ($result['error'] ?? '');
			$this->logger->warning($warning, ['app' => Application::APP_ID]);
			$response = new DataResponse($result, 401);
		}
		return $response;
	}

	/**
	 * get mastodon user avatar
	 *
	 * @param string $imageUrl
	 * @return DataDisplayResponse
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getMastodonAvatar(string $imageUrl): DataDisplayResponse {
		$avatar = $this->mastodonAPIService->getMastodonAvatar($this->userId, $imageUrl);
		if (is_null($avatar)) {
			return new DataDisplayResponse('', 401);
		} else {
			$response = new DataDisplayResponse($avatar);
			$response->cacheFor(60*60*24);
			return $response;
		}
	}

	/**
	 * get home timeline
	 *
	 * @param ?int $since
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function getHomeTimeline(?int $since = null): DataResponse {
		$result = $this->mastodonAPIService->getHomeTimeline($this->userId, $since);
		if (!isset($result['error'])) {
			$response = new DataResponse($result);
		} else {
			$response = new DataResponse($result, 401);
		}
		return $response;
	}

	/**
	 * get notification list
	 *
	 * @param ?int $since
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function getNotifications(?int $since = null): DataResponse {
		$result = $this->mastodonAPIService->getNotifications($this->userId, $since);
		if (!isset($result['error'])) {
			$response = new DataResponse($result);
		} else {
			$response = new DataResponse($result, 401);
		}
		return $response;
	}

}
