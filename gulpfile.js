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
  return gulp.src('./src/styles/**/*.less')
    .pipe(less())
    .pipe(gulp.dest("./build/styles"));
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


gulp.task('default', ['html', 'php', 'javascript', 'less', 'typescript']);