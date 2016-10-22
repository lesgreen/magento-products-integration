var gulp = require('gulp'),
    jshint = require('gulp-jshint'),
    sass = require('gulp-sass'),
    rename = require('gulp-rename'),
    sourcemaps = require('gulp-sourcemaps'),
    uglify = require('gulp-uglify'),
    autoprefixer = require('gulp-autoprefixer');

gulp.task('default', ['scss', 'jshint', 'uglify', 'watch']);

gulp.task('scss', function () {
    return gulp.src(['assets/css/*.scss'])
        .pipe(autoprefixer('last 2 version'))
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(sass({includePaths: ['assets/css/'], outputStyle: 'compressed'}))
        .pipe(gulp.dest('assets/css'));
});

gulp.task('uglify', function () {
    return gulp.src(['assets/js/**/*.js', '!assets/js/**/*.min.js'])
        .pipe(uglify())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(gulp.dest('assets/js'));
});

gulp.task('jshint', function () {
    return gulp.src(['assets/js/**/*.js', '!assets/js/**/*.min.js'])
        .pipe(jshint())
        .pipe(jshint.reporter('jshint-stylish'));
});

gulp.task('watch', function () {
    gulp.watch(['assets/css/*.scss'], ['scss']);
    gulp.watch(['assets/js/**/*.js', '!assets/js/**/*.min.js'], ['jshint', 'uglify']);
});