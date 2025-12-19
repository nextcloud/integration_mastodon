<template>
	<div id="mastodon_prefs" class="section">
		<h2>
			<MastodonIcon class="icon" />
			{{ t('integration_mastodon', 'Mastodon integration') }}
		</h2>
		<div id="mastodon-content">
			<NcTextField
				v-model="state.oauth_instance_url"
				:label="t('integration_mastodon', 'Default Mastodon instance address')"
				placeholder="https://example.social"
				@update:model-value="onInput">
				<template #icon>
					<EarthIcon :size="20" />
				</template>
			</NcTextField>
			<NcFormBoxSwitch
				v-model="state.use_popup"
				@update:model-value="onUsePopupChanged">
				{{ t('integration_mastodon', 'Use a pop-up to authenticate') }}
			</NcFormBoxSwitch>
		</div>
	</div>
</template>

<script>
import EarthIcon from 'vue-material-design-icons/Earth.vue'

import MastodonIcon from './icons/MastodonIcon.vue'

import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import NcTextField from '@nextcloud/vue/components/NcInputField'

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
		NcFormBoxSwitch,
		NcTextField,
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
		display: flex;
		flex-direction: column;
		gap: 8px;
		max-width: 800px;
	}

	h2 {
		display: flex;
		justify-content: start;
		gap: 8px;
	}
}
</style>
