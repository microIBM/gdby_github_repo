'use strict';

var gulp = require('gulp');
var imagemin = require('gulp-imagemin');
var pngquant = require('imagemin-pngquant');
var paths = gulp.paths;

// 图片压缩
gulp.task('imagemin', function () {
  return gulp.src(paths.src + '/assets/images/**/*')
  .pipe(imagemin({
    progressive: true,
    svgoPlugins: [{removeViewBox: false}],
    use: [pngquant()]
  }))
  .pipe(gulp.dest(paths.src + '/assets/imagemin/'));
});
