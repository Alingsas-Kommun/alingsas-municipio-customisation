const gulp = require("gulp");
const sass = require("gulp-dart-sass");
const browserSync = require("browser-sync").create();
const config = require('dotenv').config();

function style() {
    return gulp
        .src("./src/scss/main.scss")
        .pipe(sass().on("error", sass.logError))
        .pipe(gulp.dest("./dist/css"))
        .pipe(browserSync.stream());
}

function build() {
    return gulp
        .src("./src/scss/main.scss")
        .pipe(sass({ outputStyle: "compressed" }).on("error", sass.logError))
        .pipe(gulp.dest("./dist/css"));
}

function watch() {
    browserSync.init({
        proxy: process.env.PROXY_URL || "http://localhost:8888",
        open: false,
    });

    gulp.watch("./src/scss/**/*.scss", style);
}

exports.build = build;
exports.dev = watch;
