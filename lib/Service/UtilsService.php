<?php
/**
 * Nextcloud - mastodon
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2020
 */

namespace OCA\Mastodon\Service;

require_once __DIR__ . '/../../vendor/autoload.php';
use Html2Text\Html2Text;

class UtilsService {

	public function __construct (string $appName) {
	}

	/**
	 * @param string $htmlText
	 * @return string
	 */
	public function html2text(string $htmlText): string {
		$html = new Html2Text($htmlText);
		return $html->getText();
	}
}
