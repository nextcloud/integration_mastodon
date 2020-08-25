<template>
	<div id="mastodon_prefs" class="section">
		<h2>
			<a class="icon icon-mastodon" />
			{{ t('integration_mastodon', 'Mastodon integration') }}
		</h2>
		<div class="mastodon-grid-form">
			<label for="mastodon-url">
				<a class="icon icon-link" />
				{{ t('integration_mastodon', 'Mastodon instance address') }}
			</label>
			<input id="mastodon-url"
				v-model="state.url"
				type="text"
				:placeholder="t('integration_mastodon', 'Mastodon instance URL')"
				@input="onInput">
			<button id="mastodon-oauth" @click="onOAuthClick">
				<span class="icon icon-external" />
				{{ t('integration_mastodon', 'Get access with OAuth') }}
			</button>
			<label for="mastodon-token">
				<a class="icon icon-category-auth" />
				{{ t('integration_mastodon', 'Mastodon access token') }}
			</label>
			<input id="mastodon-token"
				v-model="state.token"
				type="password"
				:placeholder="t('integration_mastodon', 'Authenticate with OAuth')"
				@input="onInput">
		</div>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'PersonalSettings',

	components: {
	},

	props: [],

	data() {
		return {
			state: loadState('integration_mastodon', 'user-config'),
		}
	},

	watch: {
	},

	mounted() {
		const paramString = window.location.search.substr(1)
		// eslint-disable-next-line
		const urlParams = new URLSearchParams(paramString)
		const mToken = urlParams.get('mastodonToken')
		if (mToken === 'success') {
			showSuccess(t('integration_mastodon', 'Mastodon OAuth access token successfully retrieved!'))
		} else if (mToken === 'error') {
			showError(t('integration_mastodon', 'Mastodon OAuth access token could not be obtained:') + ' ' + urlParams.get('message'))
		}
	},

	methods: {
		onInput() {
			const that = this
			delay(function() {
				that.saveOptions()
			}, 2000)()
		},
		saveOptions() {
			if (!this.state.url.startsWith('https://')) {
				if (this.state.url.startsWith('http://')) {
					this.state.url = this.state.url.replace('http://', 'https://')
				} else {
					this.state.url = 'https://' + this.state.url
				}
			}
			const req = {
				values: {
					token: this.state.token,
					url: this.state.url,
				},
			}
			const url = generateUrl('/apps/integration_mastodon/config')
			axios.put(url, req)
				.then((response) => {
					showSuccess(t('integration_mastodon', 'Mastodon options saved.'))
				})
				.catch((error) => {
					showError(
						t('integration_mastodon', 'Failed to save Mastodon options')
						+ ': ' + error.response.request.responseText
					)
				})
				.then(() => {
				})
		},
		onOAuthClick() {
			// first we need to add an app to the target instance
			// so we get client_id and client_secret
			const redirectEndpoint = generateUrl('/apps/integration_mastodon/oauth-redirect')
			const redirectUri = OC.getProtocol() + '://' + OC.getHostName() + redirectEndpoint
			const req = {
				redirect_uris: redirectUri,
			}
			const url = generateUrl('/apps/integration_mastodon/oauth-app')
			axios.post(url, req)
				.then((response) => {
					this.oAuthStep1(response.data.client_id)
				})
				.catch((error) => {
					showError(
						t('integration_mastodon', 'Failed to add Mastodon OAuth app')
						+ ': ' + error.response.request.responseText
					)
				})
				.then(() => {
				})
		},
		oAuthStep1(clientId) {
			// redirect to '/oauth/auhorize' api endpoint to get a code
			const redirectEndpoint = generateUrl('/apps/integration_mastodon/oauth-redirect')
			const redirectUri = OC.getProtocol() + '://' + OC.getHostName() + redirectEndpoint
			const requestUrl = this.state.url + '/oauth/authorize?client_id=' + encodeURIComponent(clientId)
				+ '&redirect_uri=' + encodeURIComponent(redirectUri)
				+ '&response_type=code'
				+ '&scope=' + encodeURIComponent('read write follow')
			window.location.replace(requestUrl)
		},
	},
}
</script>

<style scoped lang="scss">
.mastodon-grid-form label {
	line-height: 38px;
}
.mastodon-grid-form input {
	width: 100%;
}
.mastodon-grid-form {
	max-width: 900px;
	display: grid;
	grid-template: 1fr / 1fr 1fr 1fr;
	margin-left: 30px;
	button .icon {
		margin-bottom: -1px;
	}
}
#mastodon_prefs .icon {
	display: inline-block;
	width: 32px;
}
#mastodon_prefs .grid-form .icon {
	margin-bottom: -3px;
}
.icon-mastodon {
	background-image: url(./../../img/app-dark.svg);
	background-size: 23px 23px;
	height: 23px;
	margin-bottom: -4px;
}
body.dark .icon-mastodon {
	background-image: url(./../../img/app.svg);
}
</style>
