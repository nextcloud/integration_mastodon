const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'
webpackConfig.devtool = isDev ? 'cheap-source-map' : 'source-map'

webpackConfig.entry = {
	personalSettings: { import: path.join(__dirname, 'src', 'personalSettings.js'), filename: 'integration_mastodon-personalSettings.js' },
	dashboardHome: { import: path.join(__dirname, 'src', 'dashboardHome.js'), filename: 'integration_mastodon-dashboardHome.js' },
	dashboard: { import: path.join(__dirname, 'src', 'dashboard.js'), filename: 'integration_mastodon-dashboard.js' },
}

module.exports = webpackConfig
