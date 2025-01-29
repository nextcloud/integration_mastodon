<template>
	<div id="mastodon_prefs" class="section">
		<h2>
			<MastodonIcon class="icon" />
			{{ t('integration_mastodon', 'Mastodon integration') }}
		</h2>
		<div id="mastodon-content">
			<div class="line">
				<label for="mastodon-oauth-instance">
					<EarthIcon :size="20" class="icon" />
					{{ t('integration_mastodon', 'Default Mastodon instance address') }}
				</label>
				<input id="mastodon-oauth-instance"
					v-model="state.oauth_instance_url"
					type="text"
					placeholder="https://example.social"
					@input="onInput">
			</div>
			<NcCheckboxRadioSwitch
				:checked.sync="state.use_popup"
				@update:checked="onUsePopupChanged">
				{{ t('integration_mastodon', 'Use a pop-up to authenticate') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>
import EarthIcon from 'vue-material-design-icons/Earth.vue'

import MastodonIcon from './icons/MastodonIcon.vue'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

import { delay } from '../utils.js'

export default {
	name: 'AdminSettings',

	components: {
		MastodonIcon,
		NcCheckboxRadioSwitch,
		EarthIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_mastodon', 'admin-config'),
		}
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onUsePopupChanged(newValue) {
			this.saveOptions({ use_popup: newValue ? '1' : '0' }, false)
		},
		onInput() {
			delay(() => {
				this.saveOptions({
					oauth_instance_url: this.state.oauth_instance_url,
				})
			}, 2000)()
		},
		async saveOptions(values, sensitive = true) {
			if (sensitive) {
				await confirmPassword()
			}
			const req = {
				values,
			}
			const url = sensitive
				? generateUrl('/apps/integration_mastodon/sensitive-admin-config')
				: generateUrl('/apps/integration_mastodon/admin-config')
			axios.put(url, req)
				.then((response) => {
					showSuccess(t('integration_mastodon', 'Mastodon administrator options saved'))
				})
				.catch((error) => {
					showError(t('integration_mastodon', 'Failed to save Mastodon administrator options'))
					console.error(error)
				})
		},
	},
}
</script>

<style scoped lang="scss">
#mastodon_prefs {
	#mastodon-content{
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
