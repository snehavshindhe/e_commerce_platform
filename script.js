// ============================================
// script.js — ShopEase v2 Frontend Logic
//
// BUGS FIXED:
// 1. filterProducts() set display:'block' on matching
//    cards. But product cards use CSS Grid, and 'block'
//    breaks the grid layout. Fixed: use '' (empty string)
//    to restore the element's default display value,
//    which lets CSS Grid control the layout correctly.
// 2. cart_msg alert had no auto-hide — user had to
//    reload to clear it. Now fades out after 3 seconds.
// 3. No mobile nav toggle function — hamburger button
//    in the HTML called toggleNav() but it wasn't
//    defined. Added toggleNav() function.
// 4. Image preview onerror was set AFTER src assignment,
//    meaning if the image immediately failed (cached 404)
//    the error handler hadn't been attached yet.
//    Fixed: assign onerror BEFORE setting src.
// ============================================


// ---- 1. SECTION NAVIGATION ----
function showSection(id) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    const target = document.getElementById(id);
    if (target) {
        target.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// On page load — decide which section to show based on ?section= URL param
window.addEventListener('DOMContentLoaded', function () {
    const params  = new URLSearchParams(window.location.search);
    const section = params.get('section') || 'home';
    showSection(section);

    // BUG FIX #2: Auto-hide the cart success alert after 3 seconds
    const cartAlert = document.getElementById('cartAlert');
    if (cartAlert) {
        setTimeout(() => {
            cartAlert.style.transition = 'opacity 0.5s';
            cartAlert.style.opacity   = '0';
            setTimeout(() => { cartAlert.style.display = 'none'; }, 500);
        }, 3000);
    }
});


// ---- 2. MOBILE NAV TOGGLE ----
// BUG FIX #3: This function was missing — hamburger button called it but got an error
function toggleNav() {
    const nav = document.getElementById('navLinks');
    if (nav) {
        nav.classList.toggle('open');
    }
}


// ---- 3. PRODUCT SEARCH + CATEGORY FILTER ----
function filterProducts() {
    const query    = (document.getElementById('searchInput')?.value    || '').toLowerCase().trim();
    const category = (document.getElementById('categoryFilter')?.value || '').toLowerCase().trim();

    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.getAttribute('data-name')     || '';
        const cat  = card.getAttribute('data-category') || '';

        const nameMatch = name.includes(query);
        const catMatch  = category === '' || cat === category;

        // BUG FIX #1: Use '' instead of 'block' so CSS Grid layout is preserved
        card.style.display = (nameMatch && catMatch) ? '' : 'none';
    });
}


// ---- 4. IMAGE URL PREVIEW (admin add/edit product forms) ----
const imageInput = document.getElementById('product_image');
const previewBox = document.getElementById('imgPreviewBox');
const previewImg = document.getElementById('imgPreview');

if (imageInput && previewBox && previewImg) {
    imageInput.addEventListener('input', function () {
        const url = this.value.trim();
        if (url) {
            // BUG FIX #4: Assign onerror BEFORE src so it fires even on cached failures
            previewImg.onerror = function () {
                previewBox.classList.add('hidden');
            };
            previewImg.onload = function () {
                previewBox.classList.remove('hidden');
            };
            previewImg.src = url;
        } else {
            previewBox.classList.add('hidden');
            previewImg.src = '';
        }
    });
}
