<?php
namespace OCA\Mastodon\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;
use OCP\IURLGenerator;
use OCP\IInitialStateService;

use OCA\Mastodon\AppInfo\Application;

class Personal implements ISettings {

	private $request;
	private $config;
	private $dataDirPath;
	private $urlGenerator;
	private $l;

	public function __construct(
						string $appName,
						IL10N $l,
						IRequest $request,
						IConfig $config,
						IURLGenerator $urlGenerator,
						IInitialStateService $initialStateService,
						$userId) {
		$this->appName = $appName;
		$this->urlGenerator = $urlGenerator;
		$this->request = $request;
		$this->l = $l;
		$this->config = $config;
		$this->initialStateService = $initialStateService;
		$this->userId = $userId;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$token = $this->config->getUserValue($this->userId, Application::APP_ID, 'token', '');
		$url = $this->config->getUserValue($this->userId, Application::APP_ID, 'url', '');
		$userName = $this->config->getUserValue($this->userId, Application::APP_ID, 'user_name', '');

		$userConfig = [
			'token' => $token,
			'url' => $url,
			'user_name' => $userName,
		];
		$this->initialStateService->provideInitialState($this->appName, 'user-config', $userConfig);
		return new TemplateResponse(Application::APP_ID, 'personalSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 15;
	}
}
