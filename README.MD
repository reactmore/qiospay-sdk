# Qiospay SDK for CodeIgniter 4

This library provides **QiosPay** integration with the **CodeIgniter 4** framework and standalone PHP projects.
It helps manage data and APIs easily through Service Facade.

---

## 📑 Table of Contents
- [📦 Installation](#-installation)
- [⚙️ ENV Configuration](#️-env-configuration)
- [🚀 Usage](#-usage)
  - [Products Service](#products-service)
    - [Get Products](#get-products)
    - [Get Products with Filters](#get-products-with-filters)
    - [Get Products with Callback](#get-products-with-callback)
    - [Get Categories](#get-categories)
    - [Other Methods](#other-methods)
  - [💳 Qris Service](#-qris-service)
    - [Get Qris Mutation](#get-qris-mutation)
    - [Get Qris Mutation with Filters](#get-qris-mutation-with-filters)
    - [Generate Dynamic Qris](#generate-dynamic-qris)
    - [Accessing Qris Response](#accessing-qris-response)
- [🌐 Usage in PHP (non CodeIgniter 4)](#-usage-in-php-non-codeigniter-4)

---

## 📦 Installation

1. **Install via Composer**
```bash
composer require reactmore/qiospay-sdk
```

## ⚙️ ENV Configuration
```env
Qiospay.merchantCode = ''
Qiospay.apiKey = ''
Qiospay.qrisString = ''
# TODO For H2H:
Qiospay.memberId = ''
Qiospay.memberPin = ''
Qiospay.memberPassword = ''
```

---

## 🚀 Usage

### Get the service instance
```php
$qiospay = service('qiospay');
$productsService = $qiospay->products();
```

---

## Products Service

### Get Products
```php
$response = $productsService->getProduct([], 1);
$data = $response->getData();
```

Example response:
```
array(
  'kode' => 'BYRTSELQM',
  'produk' => 'Telkomsel Omni',
  'keterangan' => 'Bayar Telkomsel Combo Sakti',
  'harga' => '2050',
  'status' => '1',
  ...
)
```

### Get Products with Filters
```php
$filters = [
    'produk' => 'Telkomsel Omni',
];

$response = $productsService->getProduct($filters, 1);
$data = $response->getData();
```

Example response:

| kode       | produk         | keterangan                      | harga | status |
|------------|----------------|---------------------------------|-------|--------|
| BYRTSELQM  | Telkomsel Omni | Bayar Telkomsel Combo Sakti     | 2050  | 1      |
| CEKTSELQM  | Telkomsel Omni | Cek Harga Telkomsel Combo Sakti | 0     | 1      |
| LISTTSELQM | Telkomsel Omni | Cek List Telkomsel Combo Sakti  | 0     | 1      |

### Get Products with Callback
```php
$dataFilter = function(array $products) {
    return array_filter($products, fn($item) => $item['harga'] > 0);
};

$filters = [
    'produk' => 'Telkomsel Omni',
];

$response = $productsService->getProduct($filters, 1, $dataFilter);
$data = $response->getData();
```

Filtered response:

| kode       | produk         | keterangan                  | harga | status |
|------------|----------------|-----------------------------|-------|--------|
| BYRTSELQM  | Telkomsel Omni | Bayar Telkomsel Combo Sakti | 2050  | 1      |

### Get Categories
```php
$categoriesResponse = $productsService->getCategories();
$categories = $categoriesResponse->getData();
```

### Other Methods
```php
$response = $productsService->getProduct();   // Get product with optional page
$response = $productsService->getAll();       // Get all products
$response = $productsService->getCategories();// Get all categories
```

---

## 💳 Qris Service

### Get Qris Mutation
```php
$qrisService = $qiospay->qris();
$response = $qrisService->getMutation([], 1);
$data = $response->getData();
```

### Get Qris Mutation with Filters
```php
$filters = [
    'amount' => 10000,
];

$response = $qrisService->getMutation($filters, 1);
$data = $response->getData();
```

### Generate Dynamic Qris
```php
$response = $qrisService->generateDynamicQris([
    'amount' => 15000,
    'note'   => 'Order #12345'
]);
$qrisData = $response->getData();
```

### Accessing Qris Response
```php
$data = $response->getData();
$fullArray = $response->toArray();
$asObject = $response->toObject();
$json = $response->toJson();
```

---

## 🌐 Usage in PHP (non CodeIgniter 4)

```php
$qios = new QiosPayFacade([
    'merchantCode' => '',
    'memberId' => '',
    'memberPin' => '',
    'memberPassword' => '',
    'apiKey' => '',
    'qrisString' => '',
]);

$response = $qios->products()->getProduct([
    'produk' => 'Telkomsel Omni',
], 1);
```
