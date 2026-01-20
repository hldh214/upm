import sqlite3
import os
from flask import Flask, render_template, jsonify, request, g

app = Flask(__name__)
# Assume upm.db is in the parent directory or same directory. 
# app.py uses 'upm.db', so it assumes CWD is C:\git\upm. 
# If I run server.py from C:\git\upm\upm, I might need '..\upm.db'.
# But typically one runs `python -m upm.server` from root.
# Let's make it robust.
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

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/api/history/<product_id>')
def get_history(product_id):
    db = get_db()
    cur = db.cursor()
    
    # Fetch data
    cur.execute('''
        SELECT datetime, price, priceGroup 
        FROM price_history 
        WHERE productId = ? 
        ORDER BY datetime ASC
    ''', (product_id,))
    
    rows = cur.fetchall()
    
    data = {}
    for row in rows:
        pg = row['priceGroup']
        if pg not in data:
            data[pg] = []
        data[pg].append({
            'date': row['datetime'],
            'price': row['price']
        })
        
    return jsonify(data)

if __name__ == '__main__':
    # Run on port 5000 by default, or 8000
    app.run(debug=True, host='0.0.0.0', port=5000)
