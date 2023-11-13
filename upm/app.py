import sqlite3
import time

import httpx
import schedule

from loguru import logger

PAGINATION_LIMIT = 100

API = 'https://www.uniqlo.com/jp/api/commerce/v5/ja/products'
API_GU = 'https://www.gu-global.com/jp/api/commerce/v5/ja/products'

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


def compare_prices(item):
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

    if old_price > new_price:
        logger.success(
            f'↓↓↓[{product_id}/{price_group}][{gender}]{name} price decreased from {old_price} to {new_price}'
        )
    else:
        logger.success(
            f'↑↑↑[{product_id}/{price_group}][{gender}]{name} price increased from {old_price} to {new_price}'
        )


def write_data(items):
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

        compare_prices(item)


def fetch_data(api):
    logger.info(f'Fetching data from {api}')

    offset = 0
    while True:
        res = httpx.get(api, params={'limit': PAGINATION_LIMIT, 'offset': offset})

        assert res.status_code == 200, f'API returned {res.status_code}'

        try:
            res = res.json()
        except ValueError:
            print(res.text)
            raise

        assert res['status'] == 'ok', f'API returned {res}'

        write_data(res['result']['items'])

        pagination = res['result']['pagination']
        total = pagination['total']

        offset += PAGINATION_LIMIT

        if offset > total:
            break


def main():
    schedule.every(8).hours.do(fetch_data, API)
    schedule.every(8).hours.do(fetch_data, API_GU)

    while True:
        schedule.run_pending()
        time.sleep(1)
