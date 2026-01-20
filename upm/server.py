import sqlite3
import os
from flask import Flask, render_template, jsonify, request, g

app = Flask(__name__)
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(BASE_DIR)
DB_PATH = os.path.join(PROJECT_ROOT, 'upm.db')

def get_db():
    db = getattr(g, '_database', None)
    if db is None:
        db = g._database = sqlite3.connect(DB_PATH)
        db.row_factory = sqlite3.Row
    return db

@app.teardown_appcontext
def close_connection(exception):
    db = getattr(g, '_database', None)
    if db is not None:
        db.close()

def init_products_table(db):
    cur = db.cursor()
    cur.execute('''
        CREATE TABLE IF NOT EXISTS products (
            productId TEXT PRIMARY KEY,
            name TEXT,
            genderCategory TEXT,
            image TEXT
        )
    ''')
    db.commit()

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/api/search')
def search_products():
    q = request.args.get('q', '').strip()
    if not q:
        return jsonify([])

    db = get_db()
    # Ensure table exists (in case app.py hasn't run yet)
    init_products_table(db)

    cur = db.cursor()
    wildcard = f'%{q}%'

    # 1. Search in products table
    cur.execute('''
        SELECT productId, name, genderCategory, image 
        FROM products 
        WHERE productId LIKE ? OR name LIKE ?
        LIMIT 50
    ''', (wildcard, wildcard))
    product_rows = cur.fetchall()

    results = []
    found_ids = set()

    for row in product_rows:
        res = dict(row)
        results.append(res)
        found_ids.add(res['productId'])

    # 2. Fallback: Search in price_history for IDs we missed (if any)
    # This covers old data that isn't in 'products' table yet.
    cur.execute('''
        SELECT DISTINCT productId 
        FROM price_history 
        WHERE productId LIKE ?
        LIMIT 50
    ''', (wildcard,))
    history_rows = cur.fetchall()

    for row in history_rows:
        pid = row['productId']
        if pid not in found_ids:
            results.append({
                'productId': pid,
                'name': pid, # No name available
                'genderCategory': '',
                'image': ''
            })
            found_ids.add(pid)

    return jsonify(results)

@app.route('/product/<path:product_id>')
def product_page(product_id):
    return render_template('index.html')

@app.route('/api/history/<product_id>')
def get_history(product_id):
    db = get_db()
    cur = db.cursor()

    # 1. Fetch product info
    cur.execute('SELECT name, image, genderCategory FROM products WHERE productId = ?', (product_id,))
    product_row = cur.fetchone()
    product_info = dict(product_row) if product_row else {'name': product_id, 'image': '', 'genderCategory': ''}

    # 2. Fetch history data
    cur.execute('''
        SELECT datetime, price, priceGroup 
        FROM price_history 
        WHERE productId = ? 
        ORDER BY datetime ASC
    ''', (product_id,))

    rows = cur.fetchall()

    if not rows:
        return jsonify({
            'product': product_info,
            'stats': None,
            'history': {},
            'timeline': []
        })

    # Calculate stats
    prices = [r['price'] for r in rows]
    stats = {
        'min': min(prices),
        'max': max(prices),
        'current': prices[-1]
    }

    # Prepare data for Chart (grouped) and Table (filtered timeline)
    chart_data = {}
    timeline = []
    last_price = None

    for row in rows:
        # Chart Data
        pg = row['priceGroup']
        if pg not in chart_data:
            chart_data[pg] = []
        chart_data[pg].append({
            'date': row['datetime'],
            'price': row['price']
        })

        # Timeline Data (Filtered: only show if price changes)
        # We also treat the very first record as a change/start point
        if row['price'] != last_price:
            timeline.append({
                'date': row['datetime'],
                'price': row['price'],
                'priceGroup': row['priceGroup']
            })
            last_price = row['price']

    # Reverse timeline for display (newest first) usually preferred, 
    # but the original code didn't specify. The DB sort is ASC. 
    # Let's keep it ASC or DESC? 
    # "History" usually implies newest on top in tables? 
    # The original index.html just appended to table body.
    # Let's reverse the timeline for the table so users see latest changes first.
    timeline.reverse()

    return jsonify({
        'product': product_info,
        'stats': stats,
        'history': chart_data,
        'timeline': timeline
    })

if __name__ == '__main__':
    app.run(debug=False, host='0.0.0.0', port=5000)
