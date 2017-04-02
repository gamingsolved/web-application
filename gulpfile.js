'use strict';

var gulp = require('gulp');

//COPY STUFF
gulp.task('copy-jquery', function() {
  return gulp.src('./node_modules/jquery/dist/jquery.min.*')
    .pipe(gulp.dest('./src/AppBundle/Resources/public/vendor/js'));
});

gulp.task('copy-bootstrap', function() {
  return gulp.src('./node_modules/bootstrap/dist/css/bootstrap.min.*')
    .pipe(gulp.dest('./src/AppBundle/Resources/public/vendor/css'));
});

gulp.task('copy-bootstrap-js', function() {
  return gulp.src('./node_modules/bootstrap/dist/js/bootstrap.min.*')
    .pipe(gulp.dest('./src/AppBundle/Resources/public/vendor/js'));
});

gulp.task('copy-bootstrap-fonts', function() {
  return gulp.src('./node_modules/bootstrap/dist/fonts/**/*')
    .pipe(gulp.dest('./src/AppBundle/Resources/public/vendor/fonts'));
});

gulp.task('copy-bootstrap-material', function() {
  return gulp.src('./node_modules/bootstrap-material-design/dist/css/*.min.*')
    .pipe(gulp.dest('./src/AppBundle/Resources/public/vendor/css'));
});

gulp.task('copy-bootstrap-material-js', function() {
  return gulp.src('./node_modules/bootstrap-material-design/dist/js/*.min.*')
    .pipe(gulp.dest('./src/AppBundle/Resources/public/vendor/js'));
});

//SASS LINTING
var sassLint = require('gulp-sass-lint');
gulp.task('sass-lint', function () {
  return gulp.src(['./sass/**/*.s+(a|c)ss', '!./sass/bootstrap/**/*', '!./sass/bootstrap-material/**/*'])
    .pipe(sassLint())
    .pipe(sassLint.format())
    .pipe(sassLint.failOnError())
});

//SASS COMPILATION
var autoprefixer = require('gulp-autoprefixer');
var date = new Date();
var gcmq = require('gulp-group-css-media-queries');
var cssnano = require('gulp-cssnano');
var insert = require('gulp-insert');
var pjson = require('./package.json');
var sass = require('gulp-sass');
gulp.task('sass', ['sass-lint'], function () {
  return gulp.src('./sass/**/*.s+(a|c)ss')
    .pipe(sass().on('error', sass.logError))
    .pipe(gcmq())
    .pipe(autoprefixer({browsers: ['last 2 versions'], cascade: false}))
    .pipe(cssnano({zindex: false}))
    .pipe(insert.append('/*! v' + pjson.version + ' built on ' + date + ' */'))
    .pipe(gulp.dest('./src/AppBundle/Resources/public/'))
});

//SASS DOCUMENTATION
//var shell = require('gulp-shell');
//gulp.task('sass-doc', shell.task(['sassdoc sass/']));

//WATCHER
gulp.task('watch', ['sass'], function (cb) {
  gulp.watch(['./sass/**/*.s+(a|c)ss'], ['sass']);
  cb();
});

//RUN
var runSequence = require('run-sequence');
gulp.task('default', function (cb) {
  runSequence(['copy-jquery', 'copy-bootstrap', 'copy-bootstrap-js', 'copy-bootstrap-material', 'copy-bootstrap-material-js', 'copy-bootstrap-fonts'], ['watch'], cb);
});
