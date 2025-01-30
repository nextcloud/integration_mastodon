<?php
/**
 * Nextcloud - Mastodon
 *
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Mastodon\AppInfo;

use Closure;
use OCA\Mastodon\Reference\MastodonReferenceProvider;
use OCA\Mastodon\Search\SearchAccountsProvider;
use OCA\Mastodon\Search\SearchHashtagsProvider;
use OCA\Mastodon\Search\SearchStatusesProvider;
use OCA\Mastodon\Service\MastodonAPIService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

use OCA\Mastodon\Dashboard\MastodonWidget;
use OCA\Mastodon\Dashboard\MastodonHomeWidget;

class Application extends App implements IBootstrap, IEventListener {

	public const APP_ID = 'integration_mastodon';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerDashboardWidget(MastodonWidget::class);
		$context->registerDashboardWidget(MastodonHomeWidget::class);

		$context->registerSearchProvider(SearchStatusesProvider::class);
		$context->registerSearchProvider(SearchAccountsProvider::class);
		$context->registerSearchProvider(SearchHashtagsProvider::class);

		$context->registerReferenceProvider(MastodonReferenceProvider::class);

		// for socialsharing
		// $context->registerEventListener(\OCA\Files\Event\LoadSidebar::class, self::class);
		$context->registerEventListener(\OCA\Files\Event\LoadAdditionalScriptsEvent::class, self::class);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerNavigation']));
	}

	public function handle(Event $event): void {
		Util::addScript(self::APP_ID, self::APP_ID . '-socialsharing');
		Util::addStyle(self::APP_ID, 'socialsharing');
	}

	public function registerNavigation(IUserSession $userSession, IConfig $config, MastodonAPIService $mastodonAPIService): void {
		$user = $userSession->getUser();
		if ($user !== null) {
			$userId = $user->getUID();
			$container = $this->getContainer();

			if ($config->getUserValue($userId, self::APP_ID, 'navigation_enabled', '0') === '1') {
				$mastodonUrl = $mastodonAPIService->getMastodonUrl($userId);
				if ($mastodonUrl !== '') {
					$container->get(INavigationManager::class)->add(function () use ($container, $mastodonUrl) {
						$urlGenerator = $container->get(IURLGenerator::class);
						$l10n = $container->get(IL10N::class);
						return [
							'id' => self::APP_ID,
							'order' => 10,
							'href' => $mastodonUrl,
							'target' => '_blank',
							'icon' => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
							'name' => $l10n->t('Mastodon'),
						];
					});
				}
			}
		}
	}
}

