import { createViteConfig } from 'vite-config-factory';

const entries = {
	'js/acf-phone-number-field': './source/js/acf-phone-number-field.ts',
	'css/acf-phone-number-field': './source/sass/acf-phone-number-field.scss',
};

export default createViteConfig(entries, {
	outDir: 'assets/dist',
	manifestFile: 'manifest.json',
});
