<?php
/**
 * Nextcloud - Mastodon
 *
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Mastodon\AppInfo;

use OCP\IContainer;

use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

use OCA\Mastodon\Controller\PageController;
use OCA\Mastodon\Dashboard\MastodonWidget;

/**
 * Class Application
 *
 * @package OCA\Mastodon\AppInfo
 */
class Application extends App implements IBootstrap {

    /**
     * Constructor
     *
     * @param array $urlParams
     */
    public function __construct(array $urlParams = []) {
        parent::__construct('mastodon', $urlParams);

        $container = $this->getContainer();
    }

    public function register(IRegistrationContext $context): void {
        $context->registerDashboardWidget(MastodonWidget::class);
    }

    public function boot(IBootContext $context): void {
    }
}

