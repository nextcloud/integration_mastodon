<?php
namespace OCA\Mastodon\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

use OCA\Mastodon\AppInfo\Application;

class Personal implements ISettings {

	public function __construct(
		private IConfig $config,
		private IInitialState $initialStateService,
		private ?string $userId
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$token = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');
		$userName = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_name');
		$navigationEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'navigation_enabled', '0') === '1';
		$searchStatusesEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_statuses_enabled', '1') === '1';
		$searchAccountsEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_accounts_enabled', '1') === '1';
		$searchHashtagsEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'search_hashtags_enabled', '1') === '1';

		$adminOauthUrl = $this->config->getAppValue(Application::APP_ID, 'oauth_instance_url');
		$url = $this->config->getUserValue($this->userId, Application::APP_ID, 'url', $adminOauthUrl) ?: $adminOauthUrl;
		$usePopup = $this->config->getAppValue(Application::APP_ID, 'use_popup', '0');

		$userConfig = [
			'token' => $token,
			'url' => $url,
			'use_popup' => ($usePopup === '1'),
			'user_name' => $userName,
			'navigation_enabled' => $navigationEnabled,
			'search_statuses_enabled' => $searchStatusesEnabled,
			'search_accounts_enabled' => $searchAccountsEnabled,
			'search_hashtags_enabled' => $searchHashtagsEnabled,
		];
		$this->initialStateService->provideInitialState('user-config', $userConfig);
		return new TemplateResponse(Application::APP_ID, 'personalSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 15;
	}
}
