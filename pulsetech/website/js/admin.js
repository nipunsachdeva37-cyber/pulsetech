// This file matches the IDs in your admin.php
document.addEventListener('DOMContentLoaded', function() {
    
    // Get elements using the IDs from your HTML
    const scrapeButton = document.getElementById('scrapeBtn');
    const scrapeBtnText = scrapeButton.querySelector('.btn-text');
    const scrapeBtnLoader = scrapeButton.querySelector('.btn-loader');
    const articleUrlInput = document.getElementById('articleUrl');
    const scrapeResult = document.getElementById('scrapeResult');

    scrapeButton.addEventListener('click', function() {
        const url = articleUrlInput.value;
        
        if (!url) {
            showMessage('Please enter a URL.', 'error');
            return;
        }

        // --- Show loading state ---
        scrapeButton.disabled = true;
        scrapeBtnText.style.display = 'none';
        scrapeBtnLoader.style.display = 'inline';
        showMessage('Scraping in progress, please wait...', 'loading');

        // Send the URL to the Flask API
        fetch('http://127.0.0.1:5000/scrape', { // This is the Flask server URL
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ url: url })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showMessage(`Success: ${data.message}. Reloading page...`, 'success');
                // Reload the page after 2 seconds to see the new article
                setTimeout(() => {
                    location.reload(); 
                }, 2000);
            } else {
                // Show the error message (e.g., 'Article already exists' or 'Scraping failed')
                showMessage(`Error: ${data.message}`, 'error');
                resetButton();
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            // This error happens if the Flask server is not running
            showMessage('Error: Could not connect to the scraper service. Is the Python server running?', 'error');
            resetButton();
        });
    });

    // Helper function to show messages
    function showMessage(message, type) {
        scrapeResult.textContent = message;
        // Remove old classes and add new one
        scrapeResult.className = 'result-message'; // Reset
        scrapeResult.classList.add(type); // Add 'error', 'success', or 'loading'
    }

    // Helper function to reset the button
    function resetButton() {
        scrapeButton.disabled = false;
        scrapeBtnText.style.display = 'inline';
        scrapeBtnLoader.style.display = 'none';
    }

    // --- Delete Article Function ---
    // This function is called by the "Delete" button
    window.deleteArticle = function(articleId) {
        if (!confirm('Are you sure you want to delete this article? This cannot be undone.')) {
            return;
        }

        // Send request to your Flask API to delete
        fetch(`http://127.0.0.1:5000/article/${articleId}`, {
            method: 'DELETE',
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Find the table row and remove it from the page
                const row = document.querySelector(`tr[data-article-id="${articleId}"]`);
                if (row) {
                    row.remove();
                }
                alert(data.message);
                location.reload(); // Reload to update stats
            } else {
                alert(`Error: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Delete Error:', error);
            alert('Error: Could not connect to the server to delete the article.');
        });
    }
});