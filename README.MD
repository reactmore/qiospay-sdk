# Qiospay SDK for CodeIgniter 4

Library ini menyediakan integrasi **QiosPay** dengan framework **CodeIgniter 4**.  
Membantu developer mengelola transaksi, channel pembayaran, dan kalkulasi biaya dengan mudah melalui Service Facade.

---

## 📦 Installation

1. **Install via Composer**
```bash
composer require reactmore/qiospay-sdk
```

## 🚀 Usage

### Get the service instance: 
```php
$qiospay = service('qiospay');
$productsService = $qiospay->products();
```

### Get a single page of products
```php
// Retrieve page 1 without filters
$response = $productsService->getProduct([], 1);
$data = $response->getData();
```

Response array of page 1: 
```
array(
kode => string (9) "BYRTSELQM"
produk => string (14) "Telkomsel Omni"
keterangan => string (27) "Bayar Telkomsel Combo Sakti"
harga => string (4) "2050"
status => string (1) "1"

...
...
...
)
```

### Get a single page of products with filters
```php

$filters = [
    'produk' => 'Telkomsel Omni',
];

$response = $productsService->getProduct($filters, 1);
$data = $response->getData();

```
Response array : 

| kode       | produk         | keterangan                      | harga | status |
|------------|----------------|---------------------------------|-------|--------|
| BYRTSELQM  | Telkomsel Omni | Bayar Telkomsel Combo Sakti     | 2050  | 1      |
| CEKTSELQM  | Telkomsel Omni | Cek Harga Telkomsel Combo Sakti | 0     | 1      |
| LISTTSELQM | Telkomsel Omni | Cek List Telkomsel Combo Sakti  | 0     | 1      |

### Get products with callback (dataFilter)

You can transform or filter API data on the fly using a callback:

```php
$dataFilter = function(array $products) {
    // Keep only products with price > 0
    return array_filter($products, fn($item) => $item['harga'] > 0);
};

$filters = [
    'produk' => 'Telkomsel Omni',
];

$response = $productsService->getProduct($filters, 1, $dataFilter);
$data = $response->getData();
```
Response array : 

| kode       | produk         | keterangan                      | harga | status |
|------------|----------------|---------------------------------|-------|--------|
| BYRTSELQM  | Telkomsel Omni | Bayar Telkomsel Combo Sakti     | 2050  | 1      |


### Get unique product categories
```php
$categoriesResponse = $productsService->getCategories();
$categories = $categoriesResponse->getData();
```

### other method (all method support filter)
```php
// Get product with optional page
$response = $productsService->getProduct();
// Get all product
$response = $productsService->getAll();
// Get all Categories (Produk)
$response = $productsService->getCategories();
```

### Accessing response
```php
$data = $response->getData();        // Only the 'data' field
$fullArray = $response->toArray();   // Full response including success, status_code, message
$asObject = $response->toObject();   // Full response as object
$json = $response->toJson();         // Full response as JSON string
```
