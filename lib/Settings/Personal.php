<?php
namespace OCA\Mastodon\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

use OCA\Mastodon\AppInfo\Application;

class Personal implements ISettings {

	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IInitialState
	 */
	private $initialStateService;
	/**
	 * @var string|null
	 */
	private $userId;

	public function __construct(IConfig $config,
								IInitialState $initialStateService,
								?string $userId) {
		$this->config = $config;
		$this->initialStateService = $initialStateService;
		$this->userId = $userId;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$token = $this->config->getUserValue($this->userId, Application::APP_ID, 'token');
		$url = $this->config->getUserValue($this->userId, Application::APP_ID, 'url');
		$userName = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_name');
		$navigationEnabled = $this->config->getUserValue($this->userId, Application::APP_ID, 'navigation_enabled', '0');

		$userConfig = [
			'token' => $token,
			'url' => $url,
			'user_name' => $userName,
			'navigation_enabled' => ($navigationEnabled === '1'),
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
