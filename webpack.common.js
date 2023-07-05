const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');

module.exports = {
	entry: {
		form: './resources/js/form/index.js',
	},
	plugins: [
		new HtmlWebpackPlugin({
			title: 'Production',
		}),
	],
	output: {
		filename: '[name].js',
		path: path.resolve(__dirname, 'assets/js'),
	}
};