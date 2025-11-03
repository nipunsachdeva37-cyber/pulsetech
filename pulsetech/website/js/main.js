// Main JavaScript for PulseTech Portal

// Search functionality
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');

if (searchInput) {
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
}

if (categoryFilter) {
    categoryFilter.addEventListener('change', function() {
        performSearch();
    });
}

function performSearch() {
    const search = searchInput ? searchInput.value : '';
    const category = categoryFilter ? categoryFilter.value : 'All';
    
    let url = 'index.php?';
    
    if (category && category !== 'All') {
        url += 'category=' + encodeURIComponent(category) + '&';
    }
    
    if (search) {
        url += 'search=' + encodeURIComponent(search);
    }
    
    window.location.href = url;
}

function clearFilters() {
    window.location.href = 'index.php';
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Lazy loading images
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}