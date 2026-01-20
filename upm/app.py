import sqlite3
import sys
import time

import httpx
import schedule
import tenacity

from loguru import logger

log_format = ('<green>{time:YYYY-MM-DD HH:mm:ss.SSS}</green> | <level>{level: <8}{level.icon}</level>'
              ' | <cyan>{name}</cyan>:<cyan>{function}</cyan> | <level>{message}</level>')

logger.remove()
logger.level('rise', no=38, color='<red><bold>', icon='📈')
logger.level('fall', no=39, color='<green><bold>', icon='📉')
logger.add('upm.log', format=log_format)
logger.add(sys.stdout, format=log_format)

PAGINATION_LIMIT = 100

API = {
    'UNIQLO': 'https://www.uniqlo.com/jp/api/commerce/v5/ja/products?storeId=126608',
    'GU': 'https://www.gu-global.com/jp/api/commerce/v5/ja/products?storeId=126608',
}

PRODUCTS = {
    'UNIQLO': 'https://www.uniqlo.com/jp/ja/products/{product_id}/{price_group}',
    'GU': 'https://www.gu-global.com/jp/ja/products/{product_id}/{price_group}',
}

con = sqlite3.connect('upm.db')
cur = con.cursor()
cur.execute('''
    CREATE TABLE IF NOT EXISTS price_history (
        id INTEGER PRIMARY KEY,
        productId TEXT NOT NULL,
        priceGroup TEXT NOT NULL,
        price INTEGER NOT NULL,
        datetime timestamp NOT NULL
    );
    CREATE TABLE IF NOT EXISTS products (
        productId TEXT PRIMARY KEY,
        name TEXT,
        genderCategory TEXT,
        image TEXT
    );
''')

html_template = '''
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>UPM Report {api_type} {date}</title>
    <style>
        :root { --primary-color: #007bff; --bg-color: #f8f9fa; --card-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; padding: 20px; background-color: var(--bg-color); color: #333; }
        h1 { margin-bottom: 30px; color: #2c3e50; font-weight: 600; }
        
        /* Filter Styles */
        .filter-container { margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .filter-label { font-weight: 500; margin-right: 10px; color: #555; }
        .btn-filter { border-radius: 20px; padding: 6px 16px; font-weight: 500; transition: all 0.2s; }
        
        /* Desktop Table Styles */
        .table-container { background: white; border-radius: 12px; box-shadow: var(--card-shadow); padding: 5px; overflow: hidden; }
        table.dataTable { width: 100% !important; margin: 0 !important; border-collapse: separate; border-spacing: 0; }
        table.dataTable thead th { background-color: var(--primary-color); color: white; border: none; padding: 15px; font-weight: 600; }
        table.dataTable tbody td { padding: 12px 15px; vertical-align: middle; border-bottom: 1px solid #f0f0f0; }
        table.dataTable tbody tr:hover { background-color: #f8fbff; }
        .img-thumbnail { border-radius: 8px; border: 1px solid #eee; object-fit: contain; background: #fff; }
        
        /* Mobile / Responsive Styles */
        @media (max-width: 768px) {
            body { padding: 10px; }
            h1 { font-size: 1.5rem; margin-bottom: 20px; }
            
            /* Filter Mobile */
            .filter-container { gap: 5px; }
            .filter-label { width: 100%; margin-bottom: 5px; }
            .btn-filter { flex: 1; text-align: center; padding: 5px 10px; font-size: 0.9rem; }

            /* Scrollable Table View */
            .table-container { 
                overflow-x: auto; 
                -webkit-overflow-scrolling: touch; 
            }
            
            /* Adjust table for small screens */
            table.dataTable { white-space: nowrap; }
            
            table.dataTable tbody td { padding: 8px 5px; font-size: 0.9rem; }
            table.dataTable thead th { padding: 10px 5px; font-size: 0.9rem; }
            
            /* New Product Column Style */
            .col-product { white-space: normal; min-width: 150px; text-align: center; }
            .col-product img { width: 80px !important; height: 80px !important; margin-bottom: 5px; display: block; margin-left: auto; margin-right: auto; }
            .col-product a { display: block; font-size: 0.9rem; }
        }
        
        /* Mobile-first hover: disable hover style by default (fix sticky hover) */
        .btn-filter.btn-outline-primary:hover {
            background-color: transparent;
            color: var(--bs-primary);
        }

        /* Enable hover style only for devices that support hover */
        @media (hover: hover) {
            .btn-filter.btn-outline-primary:hover {
                background-color: var(--bs-primary);
                color: #fff;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1>UPM Report {api_type} {date}</h1>
        
        <div class="filter-container" id="genderFilters">
            <span class="filter-label">Filter by Gender:</span>
            <button class="btn btn-primary btn-filter active" data-gender="" onclick="filterGender(this, '')">ALL</button>
            <button class="btn btn-outline-primary btn-filter" data-gender="WOMEN" onclick="filterGender(this, 'WOMEN')">WOMEN</button>
            <button class="btn btn-outline-primary btn-filter" data-gender="MEN" onclick="filterGender(this, 'MEN')">MEN</button>
            <button class="btn btn-outline-primary btn-filter" data-gender="KIDS" onclick="filterGender(this, 'KIDS')">KIDS</button>
            <button class="btn btn-outline-primary btn-filter" data-gender="BABY" onclick="filterGender(this, 'BABY')">BABY</button>
            <button class="btn btn-outline-primary btn-filter" data-gender="UNISEX" onclick="filterGender(this, 'UNISEX')">UNISEX</button>
        </div>

        <div class="table-container">
            <table id="myTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>New Price</th>
                        <th>Old Price</th>
                        <th>Lowest Price</th>
                        <th>Gender</th>
                        <th>Code</th>
                    </tr>
                </thead>
                <tbody>
                    {content}
                </tbody>
            </table>
        </div>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
<script>
    $(document).ready(function() {
        var table = $('#myTable').DataTable({
            "paging": false,
            "order": [[1, "asc"]], // Default sort by New Price
            "fixedHeader": true
        });
    
        var selectedGenders = new Set();
    
        window.filterGender = function(btn, gender) {
            var $btn = $(btn);
            var $allBtn = $('#genderFilters button[data-gender=""]');
            
            if (gender === '') {
                // Clicked ALL
                selectedGenders.clear();
                $('#genderFilters button').removeClass('btn-primary active').addClass('btn-outline-primary');
                $allBtn.addClass('btn-primary active').removeClass('btn-outline-primary');
                table.column(4).search('').draw();
            } else {
                // Clicked specific gender
                if (selectedGenders.has(gender)) {
                    selectedGenders.delete(gender);
                    $btn.removeClass('btn-primary active').addClass('btn-outline-primary');
                } else {
                    selectedGenders.add(gender);
                    $btn.addClass('btn-primary active').removeClass('btn-outline-primary');
                }
                
                if (selectedGenders.size === 0) {
                    $allBtn.addClass('btn-primary active').removeClass('btn-outline-primary');
                    table.column(4).search('').draw();
                } else {
                    $allBtn.removeClass('btn-primary active').addClass('btn-outline-primary');
                    // Build regex: ^(GENDER1|GENDER2)$
                    var regex = '^(' + Array.from(selectedGenders).join('|') + ')$';
                    table.column(4).search(regex, true, false).draw();
                }
            }
            $btn.blur();
        };
    });
</script>
</html>
'''
html_folder = '/var/www/upm.php.yokohama'


def write_report(messages, api_type):
    content = ''
    date = time.strftime('%Y-%m-%d_%H-%M-%S')

    for message in messages:
        status = message['status']
        old_price = message['old_price']
        new_price = message['new_price']
        lowest_price = message['lowest_price']

        item = message['item']
        product_id = item['productId']
        price_group = item['priceGroup']
        name = item['name']
        gender = item['genderCategory']
        image = list(item['images']['main'].values())[0]['image']

        code = f'{product_id}/{price_group}'
        url = PRODUCTS[api_type].format(product_id=product_id, price_group=price_group)

        price_color = 'red' if status == 'rise' else 'green'

        content += f'''
            <tr>
                <td class="col-product" data-label="Product">
                    <img alt="main_image" src="{image}" width="100" height="100" class="img-thumbnail">
                    <br>
                    <a href="{url}" target="_blank">{name}</a>
                </td>
                <td class="col-new-price" data-label="New Price" style="color: {price_color}; font-weight: bold;">{new_price}</td>
                <td class="col-old-price" data-label="Old Price">{old_price}</td>
                <td class="col-lowest-price" data-label="Lowest Price">{lowest_price}</td>
                <td class="col-gender" data-label="Gender">{gender}</td>
                <td class="col-code" data-label="Code">{code}</td>
            </tr>
        '''

    html = html_template.replace('{date}', date).replace('{content}', content).replace('{api_type}', api_type)
    open(f'{html_folder}/{api_type}_{date}.html', 'w').write(html)


def compare_prices(item, api_type):
    product_id = item['productId']
    price_group = item['priceGroup']
    name = item['name']
    gender = item['genderCategory']

    cur.execute('''
        SELECT price, datetime FROM price_history
        WHERE productId = ? AND priceGroup = ?
        ORDER BY datetime DESC
        LIMIT 2
    ''', (product_id, price_group))
    rows = cur.fetchall()

    if len(rows) < 2:
        return

    old_price, old_datetime = rows[1]
    new_price, new_datetime = rows[0]

    # ignore the same and prices that haven't dropped
    if new_price >= old_price:
        return

    # query the lowest historical price
    cur.execute('''
        SELECT price FROM price_history
        WHERE productId = ? AND priceGroup = ?
        ORDER BY price
        LIMIT 1
    ''', (product_id, price_group))
    lowest_price = cur.fetchone()[0]

    logger.log(
        'rise' if old_price < new_price else 'fall',
        f'[{api_type}][{old_price} -> {new_price}({lowest_price})][{product_id}/{price_group}][{gender}]{name}'
    )

    return {
        'status': 'rise' if old_price < new_price else 'fall',
        'old_price': old_price,
        'old_datetime': old_datetime,
        'new_price': new_price,
        'new_datetime': new_datetime,
        'lowest_price': lowest_price,
    }


def write_data(items, api_type):
    local_cur = con.cursor()
    messages = []
    for item in items:
        product_id = item['productId']
        price_group = item['priceGroup']
        price = item['prices']['base']['value']

        local_cur.execute('''
            INSERT INTO price_history (productId, priceGroup, price, datetime)
            VALUES (?, ?, ?, datetime('now', 'localtime'))
        ''', (product_id, price_group, price))

        name = item.get('name', '')
        gender = item.get('genderCategory', '')
        image = ''
        try:
            image = list(item['images']['main'].values())[0]['image']
        except (KeyError, IndexError, AttributeError):
            pass

        local_cur.execute('''
            INSERT OR REPLACE INTO products (productId, name, genderCategory, image)
            VALUES (?, ?, ?, ?)
        ''', (product_id, name, gender, image))

        con.commit()

        message = compare_prices(item, api_type)
        if message:
            message.update({
                'item': item,
            })
            messages.append(message)

    return messages


@tenacity.retry(
    wait=tenacity.wait_random_exponential(multiplier=1, min=4, max=60),
    stop=tenacity.stop_after_attempt(4),
    before_sleep=tenacity.before_sleep_log(logger, 30),  # 30 for "Warning"
)
def http_request(method, url, **kwargs):
    return httpx.request(method, url, **kwargs)


def fetch_data(api_type):
    logger.info(f'Fetching data from {api_type}')

    offset = 0
    messages = []
    while True:
        res = http_request('GET', API.get(api_type), params={'limit': PAGINATION_LIMIT, 'offset': offset})

        assert res.status_code == 200, f'API returned {res.status_code}'

        try:
            res = res.json()
        except ValueError:
            print(res.text)
            raise

        assert res['status'] == 'ok', f'API returned {res}'

        message = write_data(res['result']['items'], api_type)
        if message:
            messages.extend(message)

        pagination = res['result']['pagination']
        total = pagination['total']

        offset += PAGINATION_LIMIT

        if offset > total:
            break

    if messages:
        write_report(messages, api_type)


def main():
    # every day at 4:00 AM
    schedule.every().day.at('04:00').do(fetch_data, 'UNIQLO')
    schedule.every().day.at('04:00').do(fetch_data, 'GU')

    schedule.run_all()  # Run once at start

    while True:
        schedule.run_pending()
        time.sleep(1)
