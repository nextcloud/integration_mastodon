<?php

declare(strict_types=1);

namespace OCA\Mastodon\Listener;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Mastodon\AppInfo\Application;
use OCA\Mastodon\Service\MastodonAPIService;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/**
 * @template-implements IEventListener<LoadAdditionalScriptsEvent>
 */
class LoadAdditionalScriptsListener implements IEventListener {

	public function __construct(
		private IInitialState $initialStateService,
		private MastodonAPIService $mastodonAPIService,
		private ?string $userId,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof LoadAdditionalScriptsEvent) {
			return;
		}

		$mastodonUrl = $this->mastodonAPIService->getMastodonUrl($this->userId);
		$this->initialStateService->provideInitialState('mastodon-url', $mastodonUrl);

		Util::addScript(Application::APP_ID, Application::APP_ID . '-socialsharing');
		Util::addStyle(Application::APP_ID, 'socialsharing');
	}
}
