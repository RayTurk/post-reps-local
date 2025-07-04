const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.sass('resources/sass/app.scss', 'public/css')
    .js('resources/js/app.js', 'public/js')
    .js('resources/js/location.js', 'public/js')
    .js('resources/js/dashboard.js', 'public/js')
    .js('resources/js/public.js', 'public/js')
    .version()
    .browserSync({
      proxy: 'postreps.local',
      files: [
        'app/**/*',
        'routes/**/*',
        'resources/**/*',
      ]
    })
    .extract();
