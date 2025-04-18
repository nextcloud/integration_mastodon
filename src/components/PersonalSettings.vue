<template>
	<div id="mastodon_prefs" class="section">
		<h2>
			<MastodonIcon :size="20" class="icon" />
			{{ t('integration_mastodon', 'Mastodon integration') }}
		</h2>
		<div id="mastodon-content">
			<NcCheckboxRadioSwitch
				:checked="state.navigation_enabled"
				@update:checked="onCheckboxChanged($event, 'navigation_enabled')">
				{{ t('integration_mastodon', 'Enable navigation link') }}
			</NcCheckboxRadioSwitch>
			<div class="line">
				<label for="mastodon-url">
					<EarthIcon :size="20" class="icon" />
					{{ t('integration_mastodon', 'Mastodon instance address') }}
				</label>
				<input id="mastodon-url"
					v-model="state.url"
					type="text"
					:disabled="connected === true"
					placeholder="https://example.social"
					@input="onInput">
			</div>
			<NcButton v-if="!connected"
				id="mastodon-oauth"
				:disabled="loading === true || !state.url"
				:class="{ loading }"
				@click="onConnectClick">
				<template #icon>
					<OpenInNewIcon :size="20" />
				</template>
				{{ t('integration_mastodon', 'Connect to Mastodon') }}
			</NcButton>
			<div v-if="connected" class="line">
				<label class="mastodon-connected">
					<CheckIcon :size="20" class="icon" />
					{{ t('integration_mastodon', 'Connected as {user}', { user: state.user_name }) }}
				</label>
				<NcButton @click="onLogoutClick">
					<template #icon>
						<CloseIcon :size="20" />
					</template>
					{{ t('integration_mastodon', 'Disconnect from Mastodon') }}
				</NcButton>
			</div>
			<div v-if="connected" class="connected-settings">
				<NcCheckboxRadioSwitch
					:checked="state.search_statuses_enabled"
					@update:checked="onCheckboxChanged($event, 'search_statuses_enabled')">
					{{ t('integration_mastodon', 'Enable statuses search') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch
					:checked="state.search_accounts_enabled"
					@update:checked="onCheckboxChanged($event, 'search_accounts_enabled')">
					{{ t('integration_mastodon', 'Enable accounts search') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch
					:checked="state.search_hashtags_enabled"
					@update:checked="onCheckboxChanged($event, 'search_hashtags_enabled')">
					{{ t('integration_mastodon', 'Enable hashtags search') }}
				</NcCheckboxRadioSwitch>
			</div>
		</div>
	</div>
</template>

<script>
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import EarthIcon from 'vue-material-design-icons/Earth.vue'

import MastodonIcon from './icons/MastodonIcon.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

import { delay, oauthConnect } from '../utils.js'

export default {
	name: 'PersonalSettings',

	components: {
		NcCheckboxRadioSwitch,
		MastodonIcon,
		NcButton,
		CloseIcon,
		CheckIcon,
		OpenInNewIcon,
		EarthIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_mastodon', 'user-config'),
			loading: false,
			redirect_uri: window.location.protocol + '//' + window.location.host + generateUrl('/apps/integration_mastodon/oauth-redirect'),
		}
	},

	computed: {
		connected() {
			return this.state.url && this.state.url !== ''
				&& this.state.token && this.state.token !== ''
				&& this.state.user_name && this.state.user_name !== ''
		},
	},

	mounted() {
		const paramString = window.location.search.slice(1)
		// eslint-disable-next-line
		const urlParams = new URLSearchParams(paramString)
		const mToken = urlParams.get('mastodonToken')
		if (mToken === 'success') {
			showSuccess(t('integration_mastodon', 'Successfully connected to Mastodon!'))
		} else if (mToken === 'error') {
			showError(t('integration_mastodon', 'Mastodon OAuth access token could not be obtained:') + ' ' + urlParams.get('message'))
		}
	},

	methods: {
		onLogoutClick() {
			this.state.token = ''
			this.saveOptions({ token: this.state.token })
		},
		onInput() {
			this.loading = true
			delay(() => {
				this.saveOptions({ url: this.state.url })
			}, 2000)()
		},
		onCheckboxChanged(newValue, key) {
			this.state[key] = newValue
			this.saveOptions({ [key]: this.state[key] ? '1' : '0' }, false)
		},
		async saveOptions(values, sensitive = true) {
			if (sensitive) {
				await confirmPassword()
			}
			const req = {
				values,
			}
			const url = sensitive
				? generateUrl('/apps/integration_mastodon/sensitive-config')
				: generateUrl('/apps/integration_mastodon/config')
			axios.put(url, req)
				.then((response) => {
					showSuccess(t('integration_mastodon', 'Mastodon options saved'))
					if (response.data.user_name !== undefined) {
						this.state.user_name = response.data.user_name
						if (response.data.user_name === '') {
							showError(t('integration_mastodon', 'Incorrect access token'))
						}
					}
				})
				.catch((error) => {
					showError(t('integration_mastodon', 'Failed to save Mastodon options')
						+ ': ' + error.response?.request?.responseText)
				})
				.then(() => {
					this.loading = false
				})
		},
		onConnectClick() {
			if (this.state.use_popup) {
				oauthConnect(this.state.url, null, true)
					.then((data) => {
						this.state.token = 'dummyToken'
						this.state.user_name = data.userName
						this.state.user_displayname = data.userDisplayName
					})
			} else {
				oauthConnect(this.state.url, 'settings')
			}
		},
	},
}
</script>

<style scoped lang="scss">
#mastodon_prefs {
	#mastodon-content {
		margin-left: 40px;
	}
	h2,
	.line,
	.settings-hint {
		display: flex;
		align-items: center;
		.icon {
			margin-right: 4px;
		}
	}

	h2 .icon {
		margin-right: 8px;
	}

	.line {
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}
		> input {
			width: 250px;
		}
	}
}
</style>
