import '../scss/main.scss';
import faqSearch from './lib/manualinput-search';
import anchorScroll from './lib/anchor-scroll';

const $ = jQuery;

$(() => {
    faqSearch();
    anchorScroll();
});