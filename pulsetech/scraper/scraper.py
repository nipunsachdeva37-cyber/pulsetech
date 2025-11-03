import requests
from bs4 import BeautifulSoup
from urllib.parse import urljoin
from datetime import datetime
import re

# CATEGORY_KEYWORDS dictionary (with all your new keywords)
CATEGORY_KEYWORDS = {
    'AI': [
        'ai', 'artificial intelligence', 'machine learning', 'neural network', 'gpt', 'openai',
        'deep learning', 'large language model', 'llm', 'natural language processing', 'nlp',
        'computer vision', 'generative ai', 'robotics', 'algorithm', 'data science',
        'agi', 'deepmind', 'anthropic', 'claude', 'llama', 'midjourney',
        'tensorflow', 'pytorch', 'transformer', 'chatbot'
    ],
    'Mobile': [
        'iphone', 'android', 'samsung', 'google pixel', 'mobile', 'smartphone',
        'ios', '5g', 'app', 'tablet', 'foldable', 'wearable', 'smartwatch',
        'app store', 'google play', 'apple', 'google', 'qualcomm', 'snapdragon',
        'mediatek', 'huawei', 'xiaomi', 'oneplus', 'ipad', 'galaxy', 'mobile app'
    ],
    'Security': [
        'cybersecurity', 'hack', 'breach', 'vulnerability', 'malware', 'phishing', 'ransomware', 'hacker',
        'data breach', 'privacy', 'encryption', 'firewall', 'antivirus', 'virus',
        'trojan', 'spyware', 'zero-day', 'exploit', 'social engineering', 'ddos',
        'vpn', 'authentication', '2fa', 'infosec', 'cisa', 'dark web',
        'threat actor', 'data leak'
    ],
    'Hardware': [
        'nvidia', 'amd', 'intel', 'cpu', 'gpu', 'graphics card', 'laptop', 'hardware',
        'pc', 'processor', 'semiconductor', 'chip', 'motherboard', 'ram', 'memory',
        'ssd', 'hard drive', 'monitor', 'peripheral', 'keyboard', 'mouse',
        'chipset', 'fabrication', 'tsmc', 'server', 'data center',
        'quantum computing', 'arm'
    ],
    'Startups': [
        'startup', 'funding', 'vc', 'venture capital', 'seed round',
        'angel investor', 'accelerator', 'incubator', 'series a', 'series b', 'series c',
        'pre-seed', 'unicorn', 'valuation', 'acquisition', 'ipo', 'exit',
        'pitch deck', 'entrepreneur', 'founder', 'bootstrap', 'saas',
        'fintech', 'biotech', 'y combinator'
    ]
}

def auto_categorize(text):
    text_lower = text.lower()
    for category, keywords in CATEGORY_KEYWORDS.items():
        if any(re.search(r'\b' + re.escape(keyword) + r'\b', text_lower) for keyword in keywords):
            return category
    return 'Tech'

def get_safe_text(element, default=''):
    return element.get_text(strip=True) if element else default

def get_safe_attr(element, attr, default=''):
    return element[attr] if element and element.has_attr(attr) else default

def create_excerpt(text_content, length=150):
    if not text_content:
        return ""
    return (text_content[:length] + '...') if len(text_content) > length else text_content

def get_publish_date(soup):
    meta_date = soup.find('meta', property='article:published_time')
    if meta_date and meta_date.has_attr('content'):
        return datetime.fromisoformat(meta_date['content'].rstrip('Z')).strftime('%Y-%m-%d')
    time_tag = soup.find('time')
    if time_tag and time_tag.has_attr('datetime'):
        return datetime.fromisoformat(time_tag['datetime'].rstrip('Z')).strftime('%Y-%m-%d')
    return datetime.now().strftime('%Y-%m-%d')


# --- THIS IS THE NEW, SMARTER SCRAPING FUNCTION ---
def scrape_article_data(url):
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        response = requests.get(url, headers=headers, timeout=10)
        response.raise_for_status() 

        soup = BeautifulSoup(response.text, 'html.parser')

        # --- Data Extraction ---
        
        # 1. Title
        title_block = soup.find('h1')
        title = get_safe_text(title_block, 'Title not found')
        
        # 2. Find the main content area to avoid headers/footers
        # We look for <article>, <main>, or <div class="article-body">
        main_content_area = (
            soup.find('article') or 
            soup.find('main') or
            soup.find('div', class_=re.compile(r'content|body|article-body|main'))
        )
        
        # If we can't find a specific area, fall back to the whole body
        if not main_content_area:
            main_content_area = soup.find('body')

        # 3. Find ALL text elements *within* that main area
        content_elements = main_content_area.find_all(['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote'])
        
        if not content_elements:
            # Fallback if no specific tags are found (just get all text)
            content_html = main_content_area.get_text(separator='\n')
            # Convert plain text newlines into <p> tags
            content_html = ''.join([f'<p>{line}</p>' for line in content_html.split('\n') if line.strip()])
        else:
            # Stitch the HTML of all found elements together
            content_html = ''.join([str(el) for el in content_elements])

        # 4. Get plain text for excerpt and category
        plain_text_content = main_content_area.get_text(strip=True)
        excerpt = create_excerpt(plain_text_content)
        category = auto_categorize(title + ' ' + plain_text_content)

        # 5. Image
        og_image = soup.find('meta', property='og:image')
        image_url = get_safe_attr(og_image, 'content')
        if not image_url:
            # Fallback to finding the first image in the main content area
            first_img = main_content_area.find('img')
            if first_img:
                image_url = urljoin(url, get_safe_attr(first_img, 'src'))

        # 6. Date
        publish_date = get_publish_date(soup)

        # --- Assemble Data ---
        article_data = {
            'title': title,
            'content': content_html,  # <-- This is the stitched HTML of all paragraphs/headings
            'excerpt': excerpt,
            'image_url': image_url,
            'category': category,
            'publish_date': publish_date,
            'source_url': url 
        }
        
        return article_data

    except requests.RequestException as e:
        print(f"Error fetching URL {url}: {e}")
        return None
    except Exception as e:
        print(f"Error scraping article: {e}")
        return None


# --- This part is for testing the scraper directly ---
if __name__ == "__main__":
    print("--- Testing Scraper ---")
    
    db_config = {
        'host': 'localhost',
        'user': 'pulsetech_user',
        'password': 'pulsetech123', # <-- Make sure this is your correct password
        'database': 'pulsetech_news'
    }
    
    test_url = 'https://techcrunch.com/2025/10/31/meta-bought-1-gw-of-solar-this-week/'
    
    scraped_data = scrape_article_data(test_url)
    
    if scraped_data:
        print("Scraping successful:")
        print(f"Title: {scraped_data['title']}")
        print(f"Content (first 250 chars): {scraped_data['content'][:250]}...")
    else:
        print("Scraping failed.")