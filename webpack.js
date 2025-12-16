const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')
const ESLintPlugin = require('eslint-webpack-plugin')
const StyleLintPlugin = require('stylelint-webpack-plugin')

const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'
webpackConfig.devtool = isDev ? 'cheap-source-map' : 'source-map'

webpackConfig.stats = {
    colors: true,
    modules: false,
}

const appId = 'integration_mastodon'
webpackConfig.entry = {
    personalSettings: { import: path.join(__dirname, 'src', 'personalSettings.js'), filename: appId + '-personalSettings.js' },
	adminSettings: { import: path.join(__dirname, 'src', 'adminSettings.js'), filename: appId + '-adminSettings.js' },
	dashboardHome: { import: path.join(__dirname, 'src', 'dashboardHome.js'), filename: appId + '-dashboardHome.js' },
    dashboard: { import: path.join(__dirname, 'src', 'dashboard.js'), filename: appId + '-dashboard.js' },
	popupSuccess: { import: path.join(__dirname, 'src', 'popupSuccess.js'), filename: appId + '-popupSuccess.js' },
	socialsharing: { import: path.join(__dirname, 'src', 'socialsharing.js'), filename: appId + '-socialsharing.js' },
}

webpackConfig.plugins.push(
	new ESLintPlugin({
		extensions: ['js', 'vue'],
		files: 'src',
		failOnError: !isDev,
		configType: 'eslintrc',
	})
)
webpackConfig.plugins.push(
	new StyleLintPlugin({
		files: 'src/**/*.{css,scss,vue}',
		failOnError: !isDev,
	}),
)

module.exports = webpackConfig
