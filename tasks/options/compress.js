module.exports = {
	main: {
		options: {
			mode: 'zip',
			archive: './release/mah_download.<%= pkg.version %>.zip'
		},
		expand: true,
		cwd: 'release/<%= pkg.version %>/',
		src: ['**/*'],
		dest: 'mah_download/'
	}
};