<?php
/**
 * Nextcloud - Mastodon
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2020
 */

return [
    'routes' => [
        ['name' => 'config#setConfig', 'url' => '/config', 'verb' => 'PUT'],
        ['name' => 'config#oauthRedirect', 'url' => '/oauth-redirect', 'verb' => 'GET'],
        ['name' => 'mastodonAPI#getNotifications', 'url' => '/notifications', 'verb' => 'GET'],
        ['name' => 'mastodonAPI#getMastodonUrl', 'url' => '/url', 'verb' => 'GET'],
        ['name' => 'mastodonAPI#getMastodonAvatar', 'url' => '/avatar', 'verb' => 'GET'],
        ['name' => 'mastodonAPI#declareApp', 'url' => '/app', 'verb' => 'POST'],
    ]
];
