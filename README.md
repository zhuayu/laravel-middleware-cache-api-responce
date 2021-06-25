# Laravel-middleware-cache-api-responce

## 背景

有某些业务需求设计到的查询比较大，有时候网速慢的情况还会有 1 ～ 2 秒，业务来了个需求希望我们加快一下速度，不要每次都这么慢。因此我们第一想到的就是数据缓存，但是如果每个方法都加一个缓存的话有点太冗余了，因为以后可能还有很多的 API 也会遇到这个情况，因此结合了社区的一些方法优化了一个自己的 API 缓存返回。

## 逻辑

1. 以 MD5 FULL_URL 作为缓存的 Key
2. API Request 时候读取 Cache
3. 如果有直接返回，如果没有获取数据放到 Cache 里再返回数据

## 升级
支持 Tag 缓存但是要在 Redis 环境下使用，这样便利于之后主动清除缓存。

## 用法

1. 创建 Laravel MiddleWare

```
php artisan make:middleware CacheMinutes
```

2. 配置 Kernel.php 的 $routeMiddleware

```
protected $routeMiddleware = [
    ...,

    'cache.minutes' => \App\Http\Middleware\CacheMinutes::class,
];
```

3. 在路由中使用

```
$router->get('/camp/{id}', 'Camp\CampController@show')->middleware('cache.minutes:60');
```

## 代码参考

[CacheMinutes.php](./Middleware/CacheMinutes.php)

## License

- MIT
