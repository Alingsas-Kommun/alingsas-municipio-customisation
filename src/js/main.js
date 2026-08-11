import '../scss/main.scss';
import faqSearch from './lib/manualinput-search';
import anchorScroll from './lib/anchor-scroll';
import recommendConsent from './lib/recommend-consent';
import scrollableTables from './lib/scrollable-tables';

const $ = jQuery;

$(() => {
    faqSearch();
    anchorScroll();
    recommendConsent();
    scrollableTables();
});