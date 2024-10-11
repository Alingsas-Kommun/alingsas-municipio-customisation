import gulp from "gulp";
import dartSass from "gulp-dart-sass";
import browserSyncLib from "browser-sync";
import rev from "gulp-rev";
import revFormat from "gulp-rev-format";
import dotenv from "dotenv";
import { deleteAsync } from "del";

const browserSync = browserSyncLib.create();

dotenv.config();

function clean() {
    return deleteAsync(["./dist"]);
}

function devStyle() {
    return gulp
        .src("./src/scss/main.scss")
        .pipe(dartSass().on("error", dartSass.logError))
        .pipe(gulp.dest("./dist/css"))
        .pipe(browserSync.stream());
}

function prodStyle() {
    return gulp
        .src("./src/scss/main.scss")
        .pipe(dartSass({ outputStyle: "compressed" }).on("error", dartSass.logError))
        .pipe(rev())
        .pipe(revFormat({ prefix: '.', suffix: '' }))
        .pipe(gulp.dest("./dist/css"))
        .pipe(rev.manifest({ path: 'manifest.json' }))
        .pipe(gulp.dest("./dist/css"));
}

function watch() {
    browserSync.init({
        proxy: process.env.PROXY_URL || "http://localhost:8888",
        open: false,
    });

    gulp.watch("./src/scss/**/*.scss", devStyle);
}

export const build = gulp.series(clean, prodStyle);
export const dev = gulp.series(clean, devStyle, watch);
