import '../scss/main.scss';
import faqSearch from './lib/manualinput-search';
import anchorScroll from './lib/anchor-scroll';
import recommendConsent from './lib/recommend-consent';
import scrollableTables from './lib/scrollable-tables';
import galleryLightbox from './lib/gallery-lightbox';

const $ = jQuery;

$(() => {
    faqSearch();
    anchorScroll();
    recommendConsent();
    scrollableTables();
    galleryLightbox();
});