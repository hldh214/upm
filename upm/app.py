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
    'UNIQLO': 'https://www.uniqlo.com/jp/api/commerce/v5/ja/products',
    'GU': 'https://www.gu-global.com/jp/api/commerce/v5/ja/products',
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
''')

html_template = '''
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>UPM Report {api_type} {date}</title>
</head>
<body>
    <h1>UPM Report {api_type} {date}</h1>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Product</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            {content}
        </tbody>
</body>
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

        item = message['item']
        product_id = item['productId']
        price_group = item['priceGroup']
        name = item['name']
        image = item['images']['sub'][0]['image']
        url = PRODUCTS[api_type].format(product_id=product_id, price_group=price_group)

        price_color = 'red' if status == 'rise' else 'green'

        content += f'''
            <tr>
                <td><img src="{image}" width="100" height="100"></td>
                <td><a href="{url}" target="_blank">{name}</a></td>
                <td style="color: {price_color}">{old_price} -> {new_price}</td>
            </tr>
        '''

    html = html_template.format(date=date, content=content, api_type=api_type)
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

    if old_price == new_price:
        return

    logger.log(
        'rise' if old_price < new_price else 'fall',
        f'[{api_type}][{old_price} -> {new_price}][{product_id}/{price_group}][{gender}]{name}'
    )

    return {
        'status': 'rise' if old_price < new_price else 'fall',
        'old_price': old_price,
        'old_datetime': old_datetime,
        'new_price': new_price,
        'new_datetime': new_datetime,
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
            VALUES (?, ?, ?, datetime('now'))
        ''', (product_id, price_group, price))

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
    schedule.every(8).hours.do(fetch_data, 'UNIQLO')
    schedule.every(8).hours.do(fetch_data, 'GU')

    while True:
        schedule.run_pending()
        time.sleep(1)
