import requests
from bs4 import BeautifulSoup
from urllib.parse import urljoin
from datetime import datetime
import re

# Define keywords for auto-categorization
CATEGORY_KEYWORDS = {
    'AI': [
        # Original
        'ai', 'artificial intelligence', 'machine learning', 'neural network', 'gpt', 'openai',
        # Added 20 new
        'deep learning', 'large language model', 'llm', 'natural language processing', 'nlp',
        'computer vision', 'generative ai', 'robotics', 'algorithm', 'data science',
        'agi', 'deepmind', 'anthropic', 'claude', 'llama', 'midjourney',
        'tensorflow', 'pytorch', 'transformer', 'chatbot'
    ],
    'Mobile': [
        # Original
        'iphone', 'android', 'samsung', 'google pixel', 'mobile', 'smartphone',
        # Added 20 new
        'ios', '5g', 'app', 'tablet', 'foldable', 'wearable', 'smartwatch',
        'app store', 'google play', 'apple', 'google', 'qualcomm', 'snapdragon',
        'mediatek', 'huawei', 'xiaomi', 'oneplus', 'ipad', 'galaxy', 'mobile app'
    ],
    'Security': [
        # Original
        'cybersecurity', 'hack', 'breach', 'vulnerability', 'malware', 'phishing', 'ransomware', 'hacker',
        # Added 20 new
        'data breach', 'privacy', 'encryption', 'firewall', 'antivirus', 'virus',
        'trojan', 'spyware', 'zero-day', 'exploit', 'social engineering', 'ddos',
        'vpn', 'authentication', '2fa', 'infosec', 'cisa', 'dark web',
        'threat actor', 'data leak'
    ],
    'Hardware': [
        # Original
        'nvidia', 'amd', 'intel', 'cpu', 'gpu', 'graphics card', 'laptop', 'hardware',
        # Added 20 new
        'pc', 'processor', 'semiconductor', 'chip', 'motherboard', 'ram', 'memory',
        'ssd', 'hard drive', 'monitor', 'peripheral', 'keyboard', 'mouse',
        'chipset', 'fabrication', 'tsmc', 'server', 'data center',
        'quantum computing', 'arm'
    ],
    'Startups': [
        # Original
        'startup', 'funding', 'vc', 'venture capital', 'seed round',
        # Added 20 new
        'angel investor', 'accelerator', 'incubator', 'series a', 'series b', 'series c',
        'pre-seed', 'unicorn', 'valuation', 'acquisition', 'ipo', 'exit',
        'pitch deck', 'entrepreneur', 'founder', 'bootstrap', 'saas',
        'fintech', 'biotech', 'y combinator'
    ]
}

def auto_categorize(text):
    """Categorizes article based on keywords in its text."""
    text_lower = text.lower()
    for category, keywords in CATEGORY_KEYWORDS.items():
        if any(re.search(r'\b' + re.escape(keyword) + r'\b', text_lower) for keyword in keywords):
            return category
    return 'Tech' # Default category if no keywords match

def get_safe_text(element, default=''):
    """Helper function to safely get text from a BeautifulSoup element."""
    return element.get_text(strip=True) if element else default

def get_safe_attr(element, attr, default=''):
    """Helper function to safely get an attribute from a BeautifulSoup element."""
    return element[attr] if element and element.has_attr(attr) else default

def clean_content(content_html):
    """Extracts text from paragraphs and preserves line breaks."""
    paragraphs = content_html.find_all('p')
    if not paragraphs:
        # Fallback if no <p> tags are found
        return content_html.get_text(strip=True)
    
    # Join the text of all paragraphs with a newline
    return '\n'.join([p.get_text(strip=True) for p in paragraphs if p.get_text(strip=True)])

def create_excerpt(content, length=150):
    """Creates a short excerpt from the content."""
    if not content:
        return ""
    return (content[:length] + '...') if len(content) > length else content

def get_publish_date(soup):
    """Tries to find the publish date from various common tags."""
    # Try <meta property="article:published_time">
    meta_date = soup.find('meta', property='article:published_time')
    if meta_date and meta_date.has_attr('content'):
        return datetime.fromisoformat(meta_date['content'].rstrip('Z')).strftime('%Y-%m-%d')

    # Try <time datetime="...">
    time_tag = soup.find('time')
    if time_tag and time_tag.has_attr('datetime'):
        return datetime.fromisoformat(time_tag['datetime'].rstrip('Z')).strftime('%Y-%m-%d')
    
    # If all else fails, use today's date
    return datetime.now().strftime('%Y-%m-%d')


# --- THIS IS THE FUNCTION FLASK IS LOOKING FOR ---
def scrape_article_data(url):
    """
    Scrapes a single article from a URL and returns a dictionary of its data.
    """
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        response = requests.get(url, headers=headers, timeout=10)
        response.raise_for_status() # Raise an error for bad responses (4xx, 5xx)

        soup = BeautifulSoup(response.text, 'html.parser')

        # --- Data Extraction ---
        # These selectors are generic and might need tuning for specific sites.
        
        # Title
        title = get_safe_text(soup.find('h1'), 'Title not found')
        
        # Content (look for common article body containers)
        content_body = (
            soup.find('div', class_=re.compile(r'content|body|article-body')) or
            soup.find('article') or
            soup
        )
        content = str(content_body)

        # Image (OpenGraph image is usually best)
        og_image = soup.find('meta', property='og:image')
        image_url = get_safe_attr(og_image, 'content')
        if not image_url:
            # Fallback to finding the first large image
            first_img = content_body.find('img')
            if first_img:
                image_url = urljoin(url, get_safe_attr(first_img, 'src'))

        # Date
        publish_date = get_publish_date(soup)

        # Excerpt
        excerpt = create_excerpt(content_body.get_text(strip=True))

        # Category
        full_text_for_category = title + ' ' + content
        category = auto_categorize(full_text_for_category)

        # --- Assemble Data ---
        article_data = {
            'title': title,
            'content': content,
            'excerpt': excerpt,
            'image_url': image_url,
            'category': category,
            'publish_date': publish_date,
            'source_url': url # Use the original URL as the source
        }
        
        return article_data

    except requests.RequestException as e:
        print(f"Error fetching URL {url}: {e}")
        return None
    except Exception as e:
        print(f"Error scraping article: {e}")
        return None


# --- This part is for testing the scraper directly from the command line ---
# It will not run when imported by flask_app.py
if __name__ == "__main__":
    print("--- Testing Scraper ---")
    
    # --- !! IMPORTANT !! ---
    # This test block now uses the database config just for testing.
    # Flask (flask_app.py) handles its own database connection.
    # Make sure this config is correct for your test.
    
    db_config = {
        'host': 'localhost',
        'user': 'pulsetech_user',
        'password': 'pulsetech123', 
        'database': 'pulsetech_news'
    }
    
    test_url = 'https://techcrunch.com/2025/10/31/meta-bought-1-gw-of-solar-this-week/'
    
    scraped_data = scrape_article_data(test_url)
    
    if scraped_data:
        print("Scraping successful:")
        print(f"Title: {scraped_data['title']}")
        print(f"Category: {scraped_data['category']}")
        print(f"Date: {scraped_data['publish_date']}")
        print(f"Image: {scraped_data['image_url']}")
        print(f"Excerpt: {scraped_data['excerpt']}")
    else:
        print("Scraping failed.")