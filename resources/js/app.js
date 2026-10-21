import './bootstrap';
import './header-search';
import './product-category-picker';
import './product-editor';
import './product-pdp';
import './product-commerce';
import './shop-catalog';
import './media-picker';
import { fitProductCutoutsIn } from './animations/fit-product-cutout';

/*
| GSAP is installed for homepage motion (Module 06).
| Import animation modules only on pages that need them to protect performance.
|
| Example (homepage only):
|   import { initHomeAnimations } from './animations/home';
|   initHomeAnimations();
*/

document.documentElement.classList.add('js-enabled');

fitProductCutoutsIn(document, '[data-product-media]', '[data-product-cutout]', { fill: 0.7 });
