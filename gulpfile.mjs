import browserSyncLib from "browser-sync";
import dotenv from "dotenv";
import gulp from "gulp";
import dartSass from "gulp-dart-sass";
import rev from "gulp-rev";
import revFormat from "gulp-rev-format";
import webpack from "webpack";
import webpackStream from "webpack-stream";
import { deleteAsync } from "del";

const browserSync = browserSyncLib.create();

dotenv.config();

function clean() {
    return deleteAsync(["./dist"]);
}

const reload = (done) => {
    browserSync.reload();
    done();
};

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

function devScripts() {
    return gulp
        .src("./src/js/main.js")
        .pipe(
            webpackStream(
                {
                    mode: "development",
                    output: {
                        filename: "main.js",
                    },
                    externals: {
                        jquery: 'jQuery'
                    },
                    module: {
                        rules: [
                            {
                                test: /\.m?js$/,
                                exclude: /node_modules/,
                                use: {
                                    loader: "babel-loader",
                                    options: {
                                        presets: ["@babel/preset-env"],
                                    },
                                },
                            },
                        ],
                    },
                },
                webpack
            )
        )
        .pipe(gulp.dest("./dist/js"));
}

function prodScripts() {
    return gulp
        .src("./src/js/main.js")
        .pipe(
            webpackStream(
                {
                    mode: "production",
                    output: {
                        filename: "main.js",
                    },
                    externals: {
                        jquery: 'jQuery'
                    },
                },
                webpack
            )
        )
        .pipe(rev())
        .pipe(revFormat({ prefix: ".", suffix: "" }))
        .pipe(gulp.dest("./dist/js"))
        .pipe(rev.manifest({ path: "manifest.json", merge: true }))
        .pipe(gulp.dest("./dist/js"));
}

function watch() {
    browserSync.init({
        proxy: process.env.PROXY_URL || "http://localhost:8888",
        open: false,
    });

    gulp.watch("./src/scss/**/*.scss", devStyle);
    gulp.watch(["./src/js/*.js", "./src/js/**/*.js"], gulp.series(devScripts, reload));
}

export const build = gulp.series(clean, prodStyle, prodScripts);
export const dev = gulp.series(clean, devStyle, devScripts, watch);
