from flask import Flask, request, jsonify
from flask_cors import CORS
import pymysql  # <--- 1. IMPORT PyMySQL
from scraper import scrape_article_data 

app = Flask(__name__)
CORS(app) 

# This is the password you set in phpMyAdmin (e.g., 'pulsetech123')
db_config = {
    'host': 'localhost',
    'user': 'pulsetech_user',
    'password': 'pulsetech123', # <-- MAKE SURE THIS IS YOUR REAL PASSWORD
    'database': 'pulsetech_news',
    'cursorclass': pymysql.cursors.DictCursor  # <--- 2. ADD THIS for easy data handling
}

def get_db_connection():
    """Helper function to get a database connection."""
    try:
        # 3. Use PyMySQL's connect method
        conn = pymysql.connect(**db_config)
        return conn
    except pymysql.Error as err: # <--- 4. Use PyMySQL's error type
        # Check for the specific 'Access denied' error
        if err.args[0] == 1045: # Access denied error code
            print("Database Access Denied. Check 'user' and 'password' in db_config.")
        else:
            print(f"Error connecting to database: {err}")
        return None

@app.route('/scrape', methods=['POST'])
def scrape_and_store():
    data = request.get_json()
    url = data.get('url')
    if not url:
        return jsonify({'status': 'error', 'message': 'No URL provided'}), 400

    try:
        # 1. Scrape the article
        article_data = scrape_article_data(url)
        if not article_data:
            return jsonify({'status': 'error', 'message': 'Scraping failed. Could not extract data.'}), 500

        # 2. Get DB connection
        conn = get_db_connection()
        if not conn:
            return jsonify({'status': 'error', 'message': 'Database connection failed. Check server logs.'}), 500
        
        # 5. PyMySQL uses a 'with' block for cursors
        with conn.cursor() as cursor:
            # 3. Check for duplicates
            cursor.execute("SELECT id FROM articles WHERE source_url = %s", (article_data['source_url'],))
            if cursor.fetchone():
                conn.close()
                return jsonify({'status': 'error', 'message': 'Article already exists in the database.'}), 409

            # 4. Insert into database
            sql = """
            INSERT INTO articles 
            (title, content, excerpt, image_url, category, publish_date, source_url) 
            VALUES (%s, %s, %s, %s, %s, %s, %s)
            """
            values = (
                article_data['title'],
                article_data['content'],
                article_data['excerpt'],
                article_data['image_url'],
                article_data['category'],
                article_data['publish_date'],
                article_data['source_url']
            )
            
            cursor.execute(sql, values)
        
        conn.commit() # Commit the changes
        conn.close() # Close the connection
        
        return jsonify({'status': 'success', 'message': 'Article scraped and saved successfully!'}), 201

    except Exception as e:
        print(f"Error during scraping/DB insert: {e}")
        if 'conn' in locals():
             conn.close() # Make sure to close connection on error
        return jsonify({'status': 'error', 'message': f'An internal server error occurred: {e}'}), 500

# --- THIS IS THE FUNCTION FOR YOUR "DELETE" BUTTON ---
@app.route('/article/<int:article_id>', methods=['DELETE'])
def delete_article_by_id(article_id):
    try:
        conn = get_db_connection()
        if not conn:
            return jsonify({'status': 'error', 'message': 'Database connection failed.'}), 500
        
        with conn.cursor() as cursor:
            # Check if article exists before deleting
            cursor.execute("SELECT id FROM articles WHERE id = %s", (article_id,))
            if not cursor.fetchone():
                conn.close()
                return jsonify({'status': 'error', 'message': 'Article not found.'}), 404

            # Delete the article
            cursor.execute("DELETE FROM articles WHERE id = %s", (article_id,))
        
        conn.commit()
        conn.close()
        
        return jsonify({'status': 'success', 'message': 'Article deleted successfully.'}), 200

    except Exception as e:
        print(f"Error during deletion: {e}")
        if 'conn' in locals():
            conn.close()
        return jsonify({'status': 'error', 'message': f'An internal server error occurred: {e}'}), 500

if __name__ == '__main__':
    app.run(debug=True)