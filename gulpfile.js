var gulp = require('gulp');
var less = require('gulp-less');
var ts = require('gulp-typescript');


/**
 * Handle HTML
 */
gulp.task('html', function() {
  return gulp.src('./src/**/*.html')
    .pipe(gulp.dest("./build"));
});


/**
 * Handle PHP
 */
gulp.task('php', function() {
  return gulp.src('./src/api/**/*.php')
    .pipe(gulp.dest("./build/api"));
});


/**
 * Handle Javascript
 */
gulp.task('javascript', function() {
  return gulp.src('./src/scripts/lib/**/*.js')
    .pipe(gulp.dest("./build/scripts/lib"));
});


/**
 * Compile less
 */
gulp.task('less', function() {
  return gulp.src('./src/styles/main.less')
    .pipe(less())
    .pipe(gulp.dest("./build/styles"));
});


/**
 * Copies CSS
 */
gulp.task('css', function() { 
  return gulp.src('./src/styles/**/*.css')
    .pipe(gulp.dest("./build/styles"));
});


/**
 * Copies fonts
 */
gulp.task('fonts', function() {
  return gulp.src('./src/styles/fonts/*.*')
    .pipe(gulp.dest("./build/styles/fonts"));
});


/**
 * Compile TypeScript
 */
gulp.task('typescript', function() {
  var project = ts.createProject('tsconfig.json');

  var result = gulp.src(['./src/scripts/**/*.ts', './typings/**/*.d.ts'])
    .pipe(project());

  return result.js.pipe(gulp.dest("./build/scripts"));
});


gulp.task('default', ['html', 'php', 'javascript', 'less', 'css', 'fonts', 'typescript']);