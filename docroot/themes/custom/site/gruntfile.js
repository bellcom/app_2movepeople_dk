// Configurations
var pkgjson = require('./package.json');
var config = {
    pkg: pkgjson,
    directory: {
        vendor: './src/vendor',
        src: './src',
        dist: './dist'
    }
};
var module;

// Grunt
module.exports = function (grunt) {
    'use strict';

    // Configurations
    var gruntConfig = grunt.file.readJSON('./src/compile-settings.json', {encoding: 'utf8'});

    // Setup
    grunt.initConfig({
        config: config,
        pkg: config.pkg,

        clean: {
            css: '<%= config.directory.dist %>/css',
            js: '<%= config.directory.dist %>/js'
        },

        sass: {
            core: {
                options: {
                    sourceMap: true
                },
                files: {
                    '<%= config.directory.dist %>/css/stylesheet.css': '<%= config.directory.src %>/scss/stylesheet.scss'
                }
            }
        },

        concat: {
            options: {
                sourceMap: true,
                stripBanners: false
            },
            core: {
                src: gruntConfig.concat.core,
                dest: '<%= config.directory.dist %>/js/core.js'
            }
        },

        autoprefixer: {
            core: {
                options: {
                    map: true,
                    browsers: gruntConfig.autoprefixer.browsers.other
                },
                src: '<%= config.directory.dist %>/css/stylesheet.css'
            }
        },

        modernizr: {
            core: {
                "cache": true,
                "devFile": false,
                "parseFiles": true,
                "uglify": false,
                "customTests": [],
                "tests": [],
                "options": [
                    "setClasses"
                ],
                "excludeTests": [
                    'hidden',
                ],
                "files": {
                    "src": ['<%= config.directory.dist %>/js/core.js', '<%= config.directory.dist %>/css/stylesheet.css']
                },
                "dest": '<%= config.directory.dist %>/js/modernizr.js'
            },
        },

        watch: {
            options: {
                dateFormat: function (time) {
                    grunt.log.writeln('The watch finished in ' + time + 'ms at' + (new Date()).toString());
                    grunt.log.writeln('Waiting for more changes...');
                },
                livereload: true
            },
            sass: {
                files: ['<%= config.directory.src %>/scss/**/*.scss'],
                tasks: ['build']
            },
            js: {
                files: '<%= config.directory.src %>/js/**/*.js',
                tasks: ['build']
            }
        }
    });

    // Load
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-modernizr');
    grunt.loadNpmTasks('grunt-autoprefixer');

    // Register
    grunt.registerTask('default', ['watch']);
    grunt.registerTask('build', ['clean', 'concat', 'sass', 'modernizr', 'autoprefixer']);
};
