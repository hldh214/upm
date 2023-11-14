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


def compare_prices(item, api_type):
    product_id = item['productId']
    price_group = item['priceGroup']
    name = item['name']
    gender = item['genderCategory']

    cur.execute('''
        SELECT price, datetime FROM price_history
        WHERE productId = ? AND priceGroup = ?
        ORDER BY datetime
        LIMIT 2
    ''', (product_id, price_group))

    rows = cur.fetchall()

    if len(rows) < 2:
        return

    old_price, old_datetime = rows[0]
    new_price, new_datetime = rows[1]

    if old_price == new_price:
        return

    log_message = f'[{api_type}][{old_price} -> {new_price}][{product_id}/{price_group}][{gender}]{name}'
    if old_price > new_price:
        logger.log('fall', log_message)
    else:
        logger.log('rise', log_message)


def write_data(items, api_type):
    local_cur = con.cursor()
    for item in items:
        product_id = item['productId']
        price_group = item['priceGroup']
        price = item['prices']['base']['value']

        local_cur.execute('''
            INSERT INTO price_history (productId, priceGroup, price, datetime)
            VALUES (?, ?, ?, datetime('now'))
        ''', (product_id, price_group, price))

        con.commit()

        compare_prices(item, api_type)


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
    while True:
        res = http_request('GET', API.get(api_type), params={'limit': PAGINATION_LIMIT, 'offset': offset})

        assert res.status_code == 200, f'API returned {res.status_code}'

        try:
            res = res.json()
        except ValueError:
            print(res.text)
            raise

        assert res['status'] == 'ok', f'API returned {res}'

        write_data(res['result']['items'], api_type)

        pagination = res['result']['pagination']
        total = pagination['total']

        offset += PAGINATION_LIMIT

        if offset > total:
            break


def main():
    schedule.every(8).hours.do(fetch_data, 'UNIQLO')
    schedule.every(8).hours.do(fetch_data, 'GU')

    while True:
        schedule.run_pending()
        time.sleep(1)
