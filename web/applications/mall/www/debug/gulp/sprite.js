'use strict';

var gulp = require('gulp');
var spriter = require("gulp-spriter");
var paths = gulp.paths;

gulp.task('sprite', ['inject'], function () {
  return gulp.src("src/stylus/modules/*.css").pipe(
    spriter({
     outname : "test.png",
     inpath : paths.src + "src/assets/images",
     outpath : paths.src +  "src/"
  })).pipe(gulp.dest(paths.src + "src/"))
});
