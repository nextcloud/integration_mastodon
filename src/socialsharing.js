/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

let mastodonUrl = ''

const url = generateUrl('/apps/integration_mastodon/url')
axios.get(url).then((response) => {
	mastodonUrl = response.data
}).catch((error) => {
	console.error(error)
})

window.addEventListener('DOMContentLoaded', () => {
	if (OCA.Sharing && OCA.Sharing.ExternalLinkActions) {
		OCA.Sharing.ExternalLinkActions.registerAction({
			url: link => `${mastodonUrl}/share?text=${t('socialsharing_mastodon', 'I shared a file with you')}:%0A%0A${link}`,
			name: t('socialsharing_mastodon', 'Share via Mastodon'),
			icon: 'icon-social-mastodon',
		})
	}
})
