import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'

let mytimer = 0
export function delay(callback, ms) {
	return function() {
		const context = this
		const args = arguments
		clearTimeout(mytimer)
		mytimer = setTimeout(function() {
			callback.apply(context, args)
		}, ms || 0)
	}
}

export function truncateString(s, len) {
	return s.length > len
		? s.substring(0, len) + '…'
		: s
}

function getCleanMastodonUrl(url) {
	const cleanUrl = url.startsWith('http')
		? url
		: 'https://' + url
	return cleanUrl
		.trim()
		.replace(/\/+$/, '')
}

export function oauthConnect(mastodonUrl, oauthOrigin, usePopup = false) {
	const targetMastodonUrl = getCleanMastodonUrl(mastodonUrl)
	const redirectUri = window.location.protocol + '//' + window.location.host + generateUrl('/apps/integration_mastodon/oauth-redirect')

	const req = {
		redirect_uri: redirectUri,
		oauth_origin: usePopup ? undefined : oauthOrigin,
	}
	const url = generateUrl('/apps/integration_mastodon/oauth-app')
	return new Promise((resolve, reject) => {
		axios.post(url, req).then((response) => {
			const clientId = response.data.client_id
			const requestUrl = targetMastodonUrl + '/oauth/authorize?client_id=' + encodeURIComponent(clientId)
				+ '&redirect_uri=' + encodeURIComponent(redirectUri)
				+ '&response_type=code'
				+ '&scope=' + encodeURIComponent('read write follow')

			if (usePopup) {
				const ssoWindow = window.open(
					requestUrl,
					t('integration_mastodon', 'Connect to Mastodon'),
					'toolbar=no, menubar=no, width=600, height=700')
				ssoWindow.focus()
				window.addEventListener('message', (event) => {
					console.debug('Child window message received', event)
					resolve(event.data)
				})
			} else {
				window.location.replace(requestUrl)
			}
		}).catch((error) => {
			showError(
				t('integration_mastodon', 'Failed to create Mastodon OAuth app')
				+ ': ' + (error.response?.request?.responseText ?? '')
			)
			console.error(error)
		})
	})
}

export function oauthConnectConfirmDialog(mastodonUrl) {
	const targetMastodonUrl = getCleanMastodonUrl(mastodonUrl)
	return new Promise((resolve, reject) => {
		const settingsLink = generateUrl('/settings/user/connected-accounts')
		const linkText = t('integration_mastodon', 'Connected accounts')
		const settingsHtmlLink = `<a href="${settingsLink}" class="external">${linkText}</a>`
		OC.dialogs.message(
			t('integration_mastodon', 'You need to connect before using the Mastodon integration.')
			+ '<br><br>'
			+ t('integration_mastodon', 'Do you want to connect to {mastodonUrl}?', { mastodonUrl: targetMastodonUrl })
			+ '<br><br>'
			+ t(
				'integration_mastodon',
				'You can choose another Mastodon server in the {settingsHtmlLink} section of your personal settings.',
				{ settingsHtmlLink },
				null,
				{ escape: false }
			),
			t('integration_mastodon', 'Connect to Mastodon'),
			'none',
			{
				type: OC.dialogs.YES_NO_BUTTONS,
				confirm: t('integration_mastodon', 'Connect'),
				confirmClasses: 'success',
				cancel: t('integration_mastodon', 'Cancel'),
			},
			(result) => {
				resolve(result)
			},
			true,
			true,
		)
	})
}
