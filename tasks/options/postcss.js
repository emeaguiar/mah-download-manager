module.exports = {
	dist: {
		options: {
			processors: [
				require('autoprefixer')({browsers: 'last 2 versions'})
			]
		},
		files: { 
			'assets/css/mah-download-manager.css': [ 'assets/css/mah-download-manager.css' ]
		}
	}
};