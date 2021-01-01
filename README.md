# Asyncronious Magento 2 GraphQl endpoint
**25 times faster GraphQl endpoint than Magento built-in endpoint.**
## Requirements
- PHP 7.2+
- pcntl extension installed
## Installation
```bash
# Install dependencies via composer
$ composer require yourborjia/namphp-magento-graphql
```
## Usage
```bash
# Run server from magento root
$ vendor/bin/graphql-async-server
```
## Benchmarks
Sample GraphQl query for testing purposes:
 ```bash
{
  products(search: "Yoga", pageSize: 30) {
    total_count
    items {
      name
      sku
      price_range {
        minimum_price {
          regular_price {
            value
            currency
          }
        }
      }
    }
    page_info {
      page_size
      current_page
    }
  }
}
 ```
Platform: NVMe solid drive + Debian 8 + PHP 7.4 + Magento 2.4.1 EE + ElasticSearch 7.4 + Disabled xdebug

Apache benchmark preset: `ab -k -n 1 -c 100 -t 20 <query url>`

| Requests count      | amphp GraphQL   | Magento GraphQL |
|:------------------- |:--------------- |:--------------- |
| Requests per second | 1442.99 req/sec | 57.12 req/sec   |
| Time per request    | 69.301 ms       | 1750.759 ms     |
| Longest Request     | 113 ms          | 2146 ms         |

## ToDo
- Add profiler to find out bottlenecks.
- Add production mode with less IO operations per request.
- Add complex benchmark.
- Add env configuration file.
- Fix magento-related memory leaks issues.
- Check Varnish compatibility.
- Integration with [Roadrunner](https://github.com/spiral/roadrunner).